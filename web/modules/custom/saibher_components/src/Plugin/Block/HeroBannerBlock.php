<?php

namespace Drupal\saibher_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'HeroBannerBlock' block.
 *
 * @Block(
 *  id = "hero_banner_block",
 *  admin_label = @Translation("Bloque para hero banner"),
 * )
 */

class HeroBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      '#title' => 'Header del hero',
      '#default_value' => $this->configuration['header'],
    ];

    $form['body'] = [
      '#type' => 'textfield',
      '#title' => 'body del hero',
      '#default_value' => $this->configuration['body'],
    ];

    $form['cta_link'] = [
      '#type' => 'textfield',
      '#title' => 'Enlace del boton del hero',
      '#default_value' => $this->configuration['cta_link'],
    ];

    $form['cta_text'] = [
      '#type' => 'textfield',
      '#title' => 'texto para el botón del hero',
      '#default_value' => $this->configuration['cta_text'],
    ];

    $form['hero_theme'] = [
      '#type' => 'select',
      '#title' => 'tema para el hero banner',
      '#options' => [
        '1'=>'1',
        '2'=>'2',
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['hero_theme'],
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
    $this->configuration['cta_link'] = $values['cta_link'];
    $this->configuration['cta_text'] = $values['cta_text'];
    $this->configuration['hero_theme'] = $values['hero_theme'];

    if($values['main_img'] != $this->configuration['main_img']){
      if(!empty($values['main_img'][0])){
        $image = $this->entity_type_manager->getStorage('file')->load($values['main_img'][0]);
        $image->setPermanent();
        $image->save();
      }
    }
    $this->configuration['main_img'] = $values['main_img'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $image = $this->entity_type_manager->getStorage('file')->load($this->configuration['main_img'][0]);
    $image_uri = $image->getFileUri();
    $clean_uri = explode('public:/', $image_uri);
    $image_url = '/sites/default/files' . $clean_uri[1];
    $themes=['hero_banner_one', 'hero_banner_two'];
    return [
        '#theme' => $themes[$this->configuration['hero_theme']-1],
        '#items' => [
          'image_url' =>  $image_url,
          'header' => $this->configuration['header'],
          'body' => $this->configuration['body'],
          'cta_link' => $this->configuration['cta_link'],
          'cta_text' => $this->configuration['cta_text']
        ]
    ];    
  }
}
