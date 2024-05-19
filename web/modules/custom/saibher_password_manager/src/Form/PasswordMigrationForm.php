<?php

namespace Drupal\saibher_password_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de generación de promptpassword para artículos.
 */
class PasswordMigrationForm extends FormBase {

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

    $form['delimiter'] = [
        '#type' => 'select',
        '#title' => 'Delimitador del csv',
        '#options' => [
          ',' => ',',
          ';' => ';',
        ],
      ];
      $form['csv'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Adjuntar archivo csv para migración'),
        '#name' => 'csv_file',
        '#upload_location' => 'public://content/csv_files/',
        '#upload_validators' => [
          'file_validate_extensions' => ['csv'],
        ],
        '#description' => 'Por favor cargar solo archivos con extension .csv',
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
        $fid = $form_state->getValue('csv');
        $destination = $this->entityTypeManager->getStorage('file')->load($fid[0]);
        $uriFile = $destination->getFileUri();
        $file = fopen($uriFile, 'r');
        $delimiter = $form_state->getValue('delimiter');
        $errors = 0;
        $num = 0;
        $success = 0;
        $log[0] = ['Fila', 'Resultado'];
        while (($data = fgetcsv($file, 9999999, $delimiter)) !== false) {
            if ($num >= 1 && $data[3] == 'technomundohmr@gmail.com') {
                $name = $data[1];
                $pass = $data[2];
                $password_entity = $this->entityTypeManager->getStorage('node')->create([
                    'title' => $name,
                    'type'=>'passwords',
                    'field_password' => $pass,
                ]);
                $password_entity->save();
            }
            $num ++;
        }
    }

}