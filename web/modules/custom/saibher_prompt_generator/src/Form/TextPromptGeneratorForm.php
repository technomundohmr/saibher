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
class TextPromptGeneratorForm extends FormBase {

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
    return 'text_prompt_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Aquí se puede construir el formulario, utilizando la instancia del
    // administrador de tipos de entidad si es necesario.

    $form['#attached']['library'][] = 'saibher_prompt_generator/prompt_generator'; 
    $form['#prefix'] = ' <div class="prompt-generator-form"> <h1 class="w-full text-center faqs-title">Prompt generator de texto sin formato para chat GPT</h1> ';
    $form['#suffix'] = '</div>';


    $form['context'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tema de tu texto'),
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
      '#title' => $this->t('*Idea central de tu texto'),
      '#description' => $this->t('Ejemplo: &quot  &quot , &quot La guerra de los mil días en colombia fue una masacre &quot , &quot Los derechos humanos son fundamentales &quot '),
      '#attributes' => ['placeholder'=> 'Drupal es el mejor CMS de PHP'],
      '#required' => True
    ];

    $form['tesis'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Argumentos que sustenten tu idea central'),
      '#description' => $this->t('Ejemplo: &quot Debido a su flexibilidadd y escalabiliad &quot , &quot La guerra de los mil días en colombia &quot , &quot partes de un computador &quot '),
      '#attributes' => ['placeholder'=> 'Debido a su flexibilidadd y escalabiliad'],
      '#required' => True
    ];
    
    $form['limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Límite para tu texto'),
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
      '#attributes' => ['placeholder'=> 'El crecimiento de drupal a lo largo del tiempo'],
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
    // Aquí se pueden procesar los datos del formulario.
    $this->TrackingService->newTracking('Texto', 'prompt_generator');
    $response = $form_state->getValues();
    $serialize_response = serialize($response);
    $form_state->setRedirect('saibher_prompt_generator.result', [], ['query' => ['response' => $serialize_response]]);
  }

}