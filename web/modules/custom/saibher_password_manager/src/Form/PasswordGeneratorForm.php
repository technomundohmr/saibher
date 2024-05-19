<?php

namespace Drupal\saibher_password_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de generación de promptpassword para artículos.
 */
class PasswordGeneratorForm extends FormBase {

  /**
   * La instancia del administrador de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor para el formulario, que inyecta dependencias.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   La instancia del administrador de tipos de entidad.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_migration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Aquí se puede construir el formulario, utilizando la instancia del
    // administrador de tipos de entidad si es necesario.

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
    ];

    $form['enviar'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['btn', 'btn-primary-green']
      ],
      '#value' => $this->t('Generar'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $name = $form_state->getValue('name');
        $salt = '$5$' . $this->randomSalt(16) . '$' ; 
        $pass = crypt($name . time(), $salt );

        $password_entity = $this->entityTypeManager->getStorage('node')->create([
          'title' => $name,
          'type'=>'passwords',
          'field_password' => $pass,
      ]);
      $password_entity->save();
    }

    function randomSalt($len = 8) {
      $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`~!@#$%^&*()-=_+';
      $l = strlen($chars) - 1;
      $str = '';

      for ($i = 0; $i < $len; ++$i) {
        $str .= $chars[rand(0, $l)];
      }

      return $str;
    }

}