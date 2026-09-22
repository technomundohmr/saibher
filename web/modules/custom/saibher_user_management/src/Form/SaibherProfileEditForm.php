<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows a logged-in user to edit their Saibher profile data.
 */
class SaibherProfileEditForm extends SaibherFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The country manager, used to build the country select options.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected CountryManagerInterface $countryManager;

  /**
   * Constructs the profile edit form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CountryManagerInterface $country_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saibher_profile_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->attachFormLibrary($form);

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser()->id());

    $form['names'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['saibher-form__row']],
    ];

    $form['names']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_first_name')->value,
    ];

    $form['names']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Apellido'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_last_name')->value,
    ];

    $form['user_picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Foto de perfil'),
      '#description' => $this->t('Tu foto ayuda a que tus clientes te reconozcan. Formatos permitidos: png, gif, jpg, jpeg, webp.'),
      '#upload_location' => 'public://pictures/avatars',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg webp'],
      ],
      '#default_value' => $account->get('user_picture')->target_id
        ? [$account->get('user_picture')->target_id]
        : NULL,
      '#progress_indicator' => 'throbber',
    ];

    $form['phone'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['saibher-form__row']],
    ];

    $form['phone']['phone_country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Número de teléfono'),
      '#options' => self::PHONE_COUNTRY_CODES,
      '#default_value' => $account->get('field_phone_country_code')->value ?: '+57',
    ];

    $form['phone']['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('&nbsp;'),
      '#title_display' => 'invisible',
      '#default_value' => $account->get('field_phone_number')->value,
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('País'),
      '#options' => $this->countryManager->getList(),
      '#default_value' => $account->get('field_country')->value,
    ];

    $form['website'] = [
      '#type' => 'url',
      '#title' => $this->t('Sitio web'),
      '#default_value' => $account->get('field_website')->value,
    ];

    $form['business_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre de la empresa o negocio'),
      '#default_value' => $account->get('field_business_name')->value,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar cambios'),
      '#attributes' => ['class' => ['saibher-form__submit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser()->id());

    $account->set('field_first_name', $form_state->getValue('first_name'));
    $account->set('field_last_name', $form_state->getValue('last_name'));
    $account->set('field_phone_country_code', $form_state->getValue('phone_country_code'));
    $account->set('field_phone_number', $form_state->getValue('phone_number'));
    $account->set('field_country', $form_state->getValue('country'));
    $account->set('field_website', $form_state->getValue('website'));
    $account->set('field_business_name', $form_state->getValue('business_name'));

    // Set the uploaded file (if any) as the permanent profile picture.
    $fids = $form_state->getValue('user_picture');
    if (!empty($fids)) {
      $fid = reset($fids);
      $account->set('user_picture', ['target_id' => $fid]);

      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($file && !$file->isPermanent()) {
        $file->setPermanent();
        $file->save();
      }
    }
    elseif (empty($form_state->getUserInput()['user_picture'])) {
      // The picture widget was cleared by the user.
      $account->set('user_picture', NULL);
    }

    $account->save();

    $this->messenger()->addStatus($this->t('Tus datos se actualizaron correctamente.'));
    $form_state->setRedirectUrl(Url::fromRoute('saibher_user_management.profile'));
  }

}
