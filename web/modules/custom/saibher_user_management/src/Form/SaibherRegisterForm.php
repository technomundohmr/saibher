<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registration form for new Saibher users.
 */
class SaibherRegisterForm extends SaibherFormBase {

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
   * Constructs the registration form.
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
    return 'saibher_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->attachFormLibrary($form);

    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Crea tu cuenta') . '</h2>'
        . '<p class="saibher-form__subtitle">'
        . $this->t('Es gratis y toma menos de dos minutos. Sin letra pequeña.')
        . '</p>',
    ];

    $form['names'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['saibher-form__row']],
    ];

    $form['names']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#required' => TRUE,
      '#placeholder' => 'Andrés',
    ];

    $form['names']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Apellido'),
      '#required' => TRUE,
      '#placeholder' => 'Rincón',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Correo electrónico'),
      '#required' => TRUE,
      '#placeholder' => 'tucorreo@ejemplo.com',
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Contraseña'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Mínimo 8 caracteres'),
      '#attributes' => ['minlength' => 8],
    ];

    $form['confirm_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirmar contraseña'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Repite tu contraseña'),
    ];

    $form['phone'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['saibher-form__row']],
    ];

    $form['phone']['phone_country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Número de teléfono'),
      '#options' => self::PHONE_COUNTRY_CODES,
      '#default_value' => '+57',
    ];

    $form['phone']['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('&nbsp;'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#placeholder' => '300 000 0000',
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('País'),
      '#options' => $this->countryManager->getList(),
      '#required' => TRUE,
      '#default_value' => 'CO',
    ];

    $form['website'] = [
      '#type' => 'url',
      '#title' => $this->t('Sitio web (opcional)'),
      '#placeholder' => 'https://tunegocio.com',
    ];

    $form['business_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre de la empresa o negocio (opcional)'),
      '#placeholder' => $this->t('Panadería La Espiga'),
    ];

    $form['terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t(
        'Acepto los @terms y la @privacy.',
        [
          '@terms' => $this->buildLinkMarkup(
            $this->t('términos y condiciones'),
            'internal:/terminos-y-condiciones'
          ),
          '@privacy' => $this->buildLinkMarkup(
            $this->t('política de privacidad'),
            'internal:/politica-de-privacidad'
          ),
        ]
      ),
      '#required' => TRUE,
      '#attributes' => ['class' => ['saibher-form__terms-checkbox']],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Crear mi cuenta'),
      '#attributes' => ['class' => ['saibher-form__submit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validatePasswordsMatch($form_state, 'password', 'confirm_password');
    $this->validateTermsAccepted($form_state, 'terms');

    $email = $form_state->getValue('email');
    $existing = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('mail', $email)
      ->range(0, 1)
      ->execute();

    if (!empty($existing)) {
      $form_state->setErrorByName(
        'email',
        $this->t('Ya existe una cuenta con este correo electrónico.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');

    $account = User::create([
      'name' => $email,
      'mail' => $email,
      'pass' => $form_state->getValue('password'),
      'status' => 1,
      'field_first_name' => $form_state->getValue('first_name'),
      'field_last_name' => $form_state->getValue('last_name'),
      'field_phone_country_code' => $form_state->getValue('phone_country_code'),
      'field_phone_number' => $form_state->getValue('phone_number'),
      'field_country' => $form_state->getValue('country'),
      'field_website' => $form_state->getValue('website'),
      'field_business_name' => $form_state->getValue('business_name'),
    ]);

    // The affiliate code is generated automatically in
    // saibher_user_management_user_presave(), and the referral link is
    // created in saibher_user_management_user_insert() right after save.
    $account->save();

    user_login_finalize($account);

    $form_state->setRedirectUrl(Url::fromRoute('saibher_user_management.profile'));
  }

  /**
   * Builds a link markup string for use inside a translatable string.
   *
   * @param string $text
   *   The link text.
   * @param string $uri
   *   The internal URI the link should point to.
   *
   * @return string
   *   The rendered link markup.
   */
  protected function buildLinkMarkup(string $text, string $uri): string {
    return \Drupal\Core\Link::fromTextAndUrl($text, Url::fromUri($uri))->toString();
  }

}
