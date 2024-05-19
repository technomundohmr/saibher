<?php

namespace Drupal\saibher_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ThreeColsFeatures' block.
 *
 * @Block(
 *  id = "three_cols_features",
 *  admin_label = @Translation("Bloque para características en 3 columnas"),
 * )
 */

class ThreeColsFeatures extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Function constructor.
   */
  public function __construct(
    array $configuration, 
    $plugin_id, 
    $plugin_definition, 
    protected EntityTypeManagerInterface $entity_type_manager
    ) {
      parent::__construct($configuration, $plugin_id, $plugin_definition);
    }


  /**
   * Function create.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) 
  {
    return new static(    
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('entity_type.manager')
      );
  }

    /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['main_img'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Imagen principal'),
      '#name' => 'main_image',
      '#upload_location' => 'public://content/img/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg svg'],
      ],
      '#description' => 'Imagen principal para el hero banner',
      '#default_value' => $this->configuration['main_img'],
    ];

    $form['header'] = [
      '#type' => 'textfield',
      '#title' => 'Título de la card',
      '#default_value' => $this->configuration['header'],
    ];

    $form['body'] = [
      '#type' => 'textfield',
      '#title' => 'body de la card',
      '#default_value' => $this->configuration['body'],
    ];
    $form['main_img_1'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Imagen principal'),
      '#name' => 'main_image',
      '#upload_location' => 'public://content/img/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg svg'],
      ],
      '#description' => 'Imagen principal para el hero banner',
      '#default_value' => $this->configuration['main_img_1'],
    ];

    $form['header_1'] = [
      '#type' => 'textfield',
      '#title' => 'Título de la card',
      '#default_value' => $this->configuration['header_1'],
    ];

    $form['body_1'] = [
      '#type' => 'textfield',
      '#title' => '',
      '#default_value' => $this->configuration['body_1'],
    ];
    $form['main_img_2'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Imagen principal'),
      '#name' => 'main_image',
      '#upload_location' => 'public://content/img/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg svg'],
      ],
      '#description' => 'Imagen principal para el hero banner',
      '#default_value' => $this->configuration['main_img_2'],
    ];

    $form['header_2'] = [
      '#type' => 'textfield',
      '#title' => 'Título de la card',
      '#default_value' => $this->configuration['header_2'],
    ];

    $form['body_2'] = [
      '#type' => 'textfield',
      '#title' => 'body de la card',
      '#default_value' => $this->configuration['body_2'],
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => 'tema para el hero banner',
      '#options' => [
        '1'=>'1'
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['theme'],
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['header'] = $values['header'];
    $this->configuration['body'] = $values['body'];
    $this->configuration['header_1'] = $values['header_1'];
    $this->configuration['body_1'] = $values['body_1'];
    $this->configuration['header_2'] = $values['header_2'];
    $this->configuration['body_2'] = $values['body_2'];

    $this->configuration['theme'] = $values['theme'];

    if($values['main_img'] != $this->configuration['main_img']){
      if(!empty($values['main_img'][0])){
        $image = $this->entity_type_manager->getStorage('file')->load($values['main_img'][0]);
        dump($image);
        $image->setPermanent();
        $image->save();
      }
    }

    if($values['main_img_1'] != $this->configuration['main_img_1']){
      if(!empty($values['main_img_1'][0])){
        $image = $this->entity_type_manager->getStorage('file')->load($values['main_img_1'][0]);
        dump($image);
        $image->setPermanent();
        $image->save();
      }
    }

    if($values['main_img_2'] != $this->configuration['main_img_2']){
      if(!empty($values['main_img_2'][0])){
        $image = $this->entity_type_manager->getStorage('file')->load($values['main_img_2'][0]);
        dump($image);
        $image->setPermanent();
        $image->save();
      }
    }
    $this->configuration['main_img'] = $values['main_img'];
    $this->configuration['main_img_1'] = $values['main_img_1'];
    $this->configuration['main_img_2'] = $values['main_img_2'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $image = $this->entity_type_manager->getStorage('file')->load($this->configuration['main_img'][0]);
    $image_uri = $image->getFileUri();
    $clean_uri = explode('public:/', $image_uri);
    $image_url = '/sites/default/files' . $clean_uri[1];

    $image_1 = $this->entity_type_manager->getStorage('file')->load($this->configuration['main_img_1'][0]);
    $image_uri_1 = $image_1->getFileUri();
    $clean_uri_1 = explode('public:/', $image_uri_1);
    $image_url_1 = '/sites/default/files' . $clean_uri_1[1];

    $image_2 = $this->entity_type_manager->getStorage('file')->load($this->configuration['main_img_2'][0]);
    $image_uri_2 = $image_2->getFileUri();
    $clean_uri_2 = explode('public:/', $image_uri_2);
    $image_url_2 = '/sites/default/files' . $clean_uri_2[1];
    $themes=['three_cols_feature_one'];
    return [
        '#theme' => $themes[ $this->configuration['theme'] - 1 ],
        '#items' => [
          'cards' => [
            0 => [
              'img'=>$image_url,
              'header'=>$this->configuration['header'],
              'body'=>$this->configuration['body'],
            ],
            1 => [
              'img'=>$image_url_1,
              'header'=>$this->configuration['header_1'],
              'body'=>$this->configuration['body_1'],
            ],
            2 => [
              'img'=>$image_url_2,
              'header'=>$this->configuration['header_2'],
              'body'=>$this->configuration['body_2'],
            ],
          ]
        ]
    ];    
  }
}
