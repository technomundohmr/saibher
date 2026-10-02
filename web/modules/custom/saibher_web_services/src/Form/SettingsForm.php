<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Saibher Web Services.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['saibher_web_services.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'saibher_web_services_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('saibher_web_services.settings');
    $storage = $this->entityTypeManager->getStorage('filter_format');
    $formats = [];

    foreach ($storage->loadMultiple() as $format) {
      $formats[$format->id()] = $format->label() . ' (' . $format->id() . ')';
    }

    $form['allowed_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Formats de texto permitidos'),
      '#description' => $this->t('Solo los formatos seleccionados pueden enviarse en el campo "body". Los formatos incluyen filtros de saneamiento sobre el HTML que se muestra.'),
      '#options' => $formats,
      '#default_value' => $config->get('allowed_formats') ?? ['basic_html'],
      '#required' => TRUE,
    ];

    $form['article_status_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Publicar artículos nuevos por defecto'),
      '#description' => $this->t('Cuando el payload no incluye el campo "status", los artículos se crean publicados.'),
      '#default_value' => (bool) $config->get('article_status_default'),
    ];

    $form['only_published_in_lists'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Listar solo artículos publicados'),
      '#description' => $this->t('El endpoint de listado devuelve únicamente artículos publicados cuando no se filtra por "status".'),
      '#default_value' => (bool) $config->get('only_published_in_lists'),
    ];

    $form['image_max_bytes'] = [
      '#type' => 'number',
      '#title' => $this->t('Tamaño máximo de imagen remota (bytes)'),
      '#description' => $this->t('Límite de descarga para el campo opcional "image". 0 desactiva el límite.'),
      '#default_value' => (int) $config->get('image_max_bytes'),
      '#min' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('saibher_web_services.settings');
    $formats = array_values(array_filter($form_state->getValue('allowed_formats')));

    $config
      ->set('allowed_formats', $formats !== [] ? $formats : ['basic_html'])
      ->set('article_status_default', (bool) $form_state->getValue('article_status_default'))
      ->set('only_published_in_lists', (bool) $form_state->getValue('only_published_in_lists'))
      ->set('image_max_bytes', max(0, (int) $form_state->getValue('image_max_bytes')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}