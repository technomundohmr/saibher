<?php

namespace Drupal\saibher_prompt_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\saibher_prompt_generator\PromptGeneratorOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saibher_tracking\TrackingService; 

/**
 * Formulario de generación de prompt para código.
 */
class CodePromptGeneratorForm extends FormBase {

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
    return 'code_prompt_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Aquí se puede construir el formulario, utilizando la instancia del
    // administrador de tipos de entidad si es necesario.

    $form['#attached']['library'][] = 'saibher_prompt_generator/prompt_generator'; 
    $form['#prefix'] = ' <div class="prompt-generator-form"> <h1 class="w-full text-center faqs-title">Prompt generator de codigo para chat GPT</h1><div class="p-5 mx-4 my-10 card">
    <div class="card-body">
        <p class="card-text">
           <strong>Recomendación: </strong> genera porciones de código cortas, tal como componentes de una pagina web, o clases de ciertos lenguajes. Esto ddebido a que el lenguaje es limitado. puede generar errores o mal codigo o inclusocodigo incompleto. por ejemplo no solicites crear una pagina web de ventas de productos. Mas bien ve solicitando por componentes, ejemplo el menu, el carrusel, el hero banner, etc...
        </p>
    </div>
</div> ';
    $form['#suffix'] = '</div>';


    $form['input'] = [
      '#type' => 'textarea',
      '#title' => $this->t('*Qué componente deseas'),
      '#description' => $this->t('Ejemplo: &quot controller de drupal 9 &quot , &quot menú de navegación responsivo &quot , &quot carrusel de imágenesen tres columnas &quot '),
      '#attributes' => ['placeholder'=> 'hero banner responsivo'],
      '#required' => True,
    ];
    
    $output = $this->promptGeneratorOutput->getOutputCode();
    
    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Formato de salida para tu código'),
      '#options' => $output
    ];
    
    $form['functionality'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Funcionalidad de tu código'),
      '#description' => $this->t('Ejemplo: &quot Cada imagen debe enlazar a una pagina del portal &quot , &quot debe guardar un registro en la base de datos &quot, &quot redireccionar al home &quot '),
      '#attributes' => ['placeholder'=> 'debe cambiar de imagen cada 3 segundos'],
    ];
    
    $output2 = $this->promptGeneratorOutput->getOutputCodeAccuracy();
    
    $form['accuracy'] = [
      '#type' => 'select',
      '#title' => $this->t('Como deseas que sea tu código'),
      '#options' => $output2
    ];
        
    $form['lenguages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('*En qué lenguage debe estar escrito tu codigo'),
      '#description' => $this->t('Ejemplo: &quot Python &quot , &quot PHP &quot, &quot JavaScript y typeScript &quot '),
      '#attributes' => ['placeholder'=> 'HTML, CSS y JAVASCRIPT'],
      '#required' => True,
    ];
        
    $form['context'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cual es la tecnologia, framework, librería, cms, etc de tu proyecto'),
      '#description' => $this->t('Ejemplo: &quot FastAPI &quot , &quot Django &quot, &quot React &quot '),
      '#attributes' => ['placeholder'=> 'Drupal 10'],
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
    $this->TrackingService->newTracking('Código', 'prompt_generator');
    $response = $form_state->getValues();
    $serialize_response = serialize($response);
    $form_state->setRedirect('saibher_prompt_generator.result', [], ['query' => ['response' => $serialize_response]]);
  }

}