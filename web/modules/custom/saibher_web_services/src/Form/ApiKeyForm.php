<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\saibher_web_services\Entity\SaibherApiKey;
use Drupal\saibher_web_services\Entity\SaibherApiKeyInterface;
use Drupal\saibher_web_services\Service\ApiKeyService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating, editing and rotating Saibher API keys.
 */
class ApiKeyForm extends EntityForm {

  /**
   * The private temp store collection used to reveal secrets once.
   */
  protected const TEMPSTORE_COLLECTION = 'saibher_api_key';

  /**
   * The user entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $userStorage;

  /**
   * Constructor.
   */
  public function __construct(
    protected ApiKeyService $apiKeyService,
    protected PrivateTempStoreFactory $tempStoreFactory,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->userStorage = $entityTypeManager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('saibher_web_services.api_key_service'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'saibher_web_services_api_key';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $key = $this->entity;

    // A new key needs its ID up front so it is a valid config entity ID.
    if ($key->isNew()) {
      $key->set('id', $this->apiKeyService->createId());
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Etiqueta'),
      '#description' => $this->t('Nombre descriptivo del consumidor, p. ej. "Sistema de facturación - producción".'),
      '#default_value' => $key->label(),
      '#required' => TRUE,
      '#maxlength' => 128,
    ];

    $userStorage = $this->userStorage;
    $defaultUser = $key->getUserId() ? $userStorage->load($key->getUserId()) : NULL;

    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('Cuenta de usuario vinculada'),
      '#description' => $this->t('La API autentica contra esta cuenta: hereda sus roles y permisos (p. ej. "access saibher web services", "edit any article content"). Cualquier usuario que posea esta clave actúa como esta cuenta.'),
      '#default_value' => $defaultUser instanceof UserInterface ? $defaultUser : NULL,
      '#required' => TRUE,
    ];

    $form['expires'] = [
      '#type' => 'number',
      '#title' => $this->t('Expiración (días)'),
      '#description' => $this->t('Días de validez a partir de hoy. 0 (o vacío) = sin expiración. La rotación manual sigue siendo posible en cualquier momento.'),
      '#default_value' => $this->remainingDays($key),
      '#min' => 0,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clave activa'),
      '#description' => $this->t('Desmarca para revocar la clave de inmediato (las llamadas con esta clave recibirán 401).'),
      '#default_value' => $key->isNew() ? TRUE : $key->status(),
    ];

    $form['actions'] = $this->actionsElement($form, $form_state);

    if (!$key->isNew()) {
      $form['actions']['rotate'] = [
        '#type' => 'submit',
        '#name' => 'rotate',
        '#value' => $this->t('Rotar (regenerar secret)'),
        '#description' => $this->t('Genera un nuevo secret para el mismo ID e invalida el anterior de forma inmediata.'),
        '#submit' => ['::submitForm'],
        '#weight' => 20,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $expires = (int) $form_state->getValue('expires');
    if ($expires < 0) {
      $form_state->setErrorByName('expires', $this->t('El número de días no puede ser negativo.'));
    }

    if ($form_state->getTriggeringElement()['#name'] === 'rotate') {
      return;
    }

    $userValue = $form_state->getValue('user');
    $uid = is_array($userValue) && isset($userValue['target_id']) ? (int) $userValue['target_id'] : (int) $userValue;
    $user = $this->userStorage->load($uid);
    if (!$user instanceof UserInterface || !$user->isActive()) {
      $form_state->setErrorByName('user', $this->t('La cuenta vinculada debe ser un usuario activo existente.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $key = $this->entity;

    if (($form_state->getTriggeringElement()['#name'] ?? '') === 'rotate') {
      $secret = $this->apiKeyService->rotate($key);
      $this->armReveal($key->id(), $secret);
      $this->messenger()->addStatus($this->t('El secret de la clave %label fue regenerado. Copia el nuevo valor antes de cerrar la siguiente página: el anterior ya no funciona.', [
        '%label' => $key->label(),
      ]));
      $form_state->setRedirect('saibher_web_services.key.reveal', ['saibher_api_key' => $key->id()]);
      return;
    }

    $userValue = $form_state->getValue('user');
    $uid = is_array($userValue) && isset($userValue['target_id']) ? (int) $userValue['target_id'] : (int) $userValue;

    $key
      ->set('label', $form_state->getValue('label'))
      ->setUserId($uid)
      ->setExpires($this->daysFromNow((int) $form_state->getValue('expires')))
      ->setStatus((bool) $form_state->getValue('status'));

    $isNew = $key->isNew();
    if ($isNew) {
      $key->setCreated(\Drupal::time()->getRequestTime());
    }

    $secret = NULL;
    if ($isNew) {
      $secret = $this->apiKeyService->generateSecretFor($key);
    }

    $key->save();

    if ($isNew) {
      $this->armReveal($key->id(), $secret);
      $this->messenger()->addStatus($this->t('La API key fue creada. Copia el secret en la siguiente página: no se mostrará de nuevo.'));
      $form_state->setRedirect('saibher_web_services.key.reveal', ['saibher_api_key' => $key->id()]);
    }
    else {
      $this->messenger()->addStatus($this->t('La API key %label fue actualizada.', ['%label' => $key->label()]));
      $form_state->setRedirect('entity.saibher_api_key.collection');
    }
  }

  /**
   * Stores a newly generated secret for one-time display.
   */
  protected function armReveal(string $keyId, string $secret): void {
    $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION)->set($keyId, $secret);
  }

  /**
   * Computes remaining validity days for the expires field.
   */
  protected function remainingDays(SaibherApiKeyInterface $key): int {
    if ($key->getExpires() === 0 || $key->isNew()) {
      return 0;
    }
    $days = (int) ceil(($key->getExpires() - \Drupal::time()->getRequestTime()) / 86400);
    return max(0, $days);
  }

  /**
   * Computes the expiry timestamp for N days from now.
   */
  protected function daysFromNow(int $days): int {
    return $days > 0 ? \Drupal::time()->getRequestTime() + ($days * 86400) : 0;
  }

}