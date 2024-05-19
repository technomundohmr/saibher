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
class LetterPromptGeneratorForm extends FormBase {

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
    return 'letter_prompt_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Aquí se puede construir el formulario, utilizando la instancia del
    // administrador de tipos de entidad si es necesario.

    $form['#attached']['library'][] = 'saibher_prompt_generator/prompt_generator'; 
    $form['#prefix'] = ' <div class="prompt-generator-form"> <h1 class="w-full text-center faqs-title">Prompt generator de cartas para chat GPT</h1> ';
    $form['#suffix'] = '</div>';


    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('*Destinatario'),
      '#description' => $this->t('Ejemplo: &quot Empresa de suscripciones &quot , &quot Banco XXXXX &quot , &quot Saibher &quot '),
      '#attributes' => ['placeholder'=> 'Compañia de internet'],
      '#required' => True
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remitente'),
      '#description' => $this->t('Ejemplo: &quot Selene Delgado &quot , &quot John Smith &quot , &quot Maria la del barrio &quot '),
      '#attributes' => ['placeholder'=> 'Pepito perez'],
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('*Asunto'),
      '#description' => $this->t('Ejemplo: &quot Cambio de fecha de facturación &quot , &quot Solicitud de acceso a sus instalaciones &quot , &quot Creación de cuenta de Ahorros &quot'),
      '#attributes' => ['placeholder'=> 'Cancelacion de suscripción de servicios'],
      '#required' => True
    ];

    $form['goal'] = [
      '#type' => 'textarea',
      '#title' => $this->t('*Objetivo de la carta'),
      '#description' => $this->t('Ejemplo: &quot solicitar el acceso de Pepito perez a sus instalaciones el dia 25 de Abril &quot , &quot Solicitar un crédito de libre inversión de $100.000 &quot , &quot bajar la suscripción del plan gold a regular &quot '),
      '#attributes' => ['placeholder'=> 'Cancelar mi suscripción de su servicio de internet de forma permanente'],
      '#required' => True
    ];

    $form['id_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('datos de identidad'),
      '#description' => $this->t('OJO SOLO PROPORCIONE DATOS PERSONALES QUE SEAN NECESARIOS. SAIBHER NO ALMACENARÁ ESTOS DATOS. Ejemplo: &quot contrato 25365214 &quot , &quot usuario con C. C. 1.234.567.890 &quot , &quot producto con código 1234565789 &quot '),
      '#attributes' => ['placeholder'=> 'Contrato de servicios 789456123'],
    ];

    $form['aditional'] = [
      '#type' => 'textarea',
      '#title' => $this->t('solicitud aicional'),
      '#description' => $this->t('Ejemplo: &quot Generar número de radicado &quot , &quot generar paz y salvo &quot , &quot contactar con un asesor &quot '),
      '#attributes' => ['placeholder'=> 'Generar paz y salvo y radicado'],
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
    $this->TrackingService->newTracking('Carta formal', 'prompt_generator');
    $response = $form_state->getValues();
    $serialize_response = serialize($response);
    $form_state->setRedirect('saibher_prompt_generator.result', [], ['query' => ['response' => $serialize_response]]);
  }

}