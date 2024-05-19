<?php

namespace Drupal\saibher_prompt_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\saibher_prompt_generator\PromptGeneratorOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saibher_tracking\TrackingService; 

/**
 * Formulario de generación de prompt para artículos.
 */
class ArticlePromptGeneratorForm extends FormBase {

  /**
   * La instancia del administrador de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * La instancia del administrador tracking service.
   *
   * @var \Drupal\saibher_tracking\TrackingService
   */
  protected $TrackingService;

  /**
   * La instancia del administrador de tipos de entidad.
   *
   * @var \Drupal\saibher_prompt_generator\PromptGeneratorOutput
   */
  protected $promptGeneratorOutput;

  /**
   * Constructor para el formulario, que inyecta dependencias.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   La instancia del administrador de tipos de entidad.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PromptGeneratorOutput $promptGeneratorOutput, TrackingService $TrackingService) {
    $this->entityTypeManager = $entity_type_manager;
    $this->promptGeneratorOutput = $promptGeneratorOutput;
    $this->TrackingService = $TrackingService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('saibher_prompt_generator.output'),
      $container->get('saibher_tracking.tracking'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'article_prompt_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Aquí se puede construir el formulario, utilizando la instancia del
    // administrador de tipos de entidad si es necesario.

    $form['#attached']['library'][] = 'saibher_prompt_generator/prompt_generator'; 
    $form['#prefix'] = ' <div class="prompt-generator-form"> <h1 class="w-full text-center faqs-title">Prompt generator de artículos para chat GPT</h1> ';
    $form['#suffix'] = '</div>';

    $form['context'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Categoría de tu artículo'),
      '#description' => $this->t('Ejemplo: &quot Desarrollo web &quot , &quot Drupal &quot , &quot Destinos turísticos &quot '),
      '#attributes' => ['placeholder'=> 'Drupal'],
    ];

    $form['tone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('¿Qué tono debe llevar tu artículo'),
      '#description' => $this->t('Ejemplo: &quot Amable &quot , &quot Informal &quot , &quot Muy formal &quot '),
      '#attributes' => ['placeholder'=> 'Técnico'],
    ];

    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('*Tema central de tu artículo'),
      '#description' => $this->t('Ejemplo: &quot Desarrollo de javascript de los años 2010 a 2018 &quot , &quot La guerra de los mil días en colombia &quot , &quot partes de un computador &quot '),
      '#attributes' => ['placeholder'=> 'Uso de modulos en drupal'],
      '#required' => True
    ];


    $form['limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Límite para tu artículo'),
      '#description' => $this->t('Ejemplo: &quot 100 palabras &quot , &quot 500 caracteres &quot '),
      '#attributes' => ['placeholder'=> '4 parrafos'],
    ];

    $output = $this->promptGeneratorOutput->getOutputGeneralData();
    
    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Formato de salida para tu artículo'),
      '#options' => $output
    ];
    
    
    $form['focus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('En qué se debe enfocar la respuesta'),
      '#description' => $this->t('Ejemplo: &quot La importancia de la Robotica en la industria &quot , &quot el papel de la economia en la segunda guerra mundial &quot '),
      '#attributes' => ['placeholder'=> 'Uso de la inyección de dependencias para drupal'],
    ];
    
    $form['use_seo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generar artículo enfocado al SEO'),
      '#description' => $this->t('marque esta casillla para generar el seo , metaetiquetas y título llamativo'),
      '#attributes' => ['placeholder'=> 'Uso de la inyección de dependencias para drupal'],
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
    $this->TrackingService->newTracking('Artículos', 'prompt_generator');
    $response = $form_state->getValues();
    $serialize_response = serialize($response);
    $form_state->setRedirect('saibher_prompt_generator.result', [], ['query' => ['response' => $serialize_response]]);
  }

}