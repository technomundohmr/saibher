<?php

namespace Drupal\saibher_prompt_generator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for prompt request.
 */
class PromptResultController extends ControllerBase {
 
  protected $requestStack;

  protected $renderer;

  protected $responseFactory;
  
  public function __construct(RequestStack $request_stack, Renderer $renderer) {
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('renderer'),
    );
  }
  
  public function handlePostRequest() {
    $request = $this->requestStack->getCurrentRequest();
    $response = $request->query->get('response');
    $response = unserialize($response);
    if(empty($response)){
      $response = new RedirectResponse('/prompt_generator');
      return $response;
    }
    switch ($response['form_id']) {
      case 'article_prompt_generator_form':
        $context = '';
        $limit = '';
        $output = '';
        $focus = '';
        $seo = '';
        $tone = '';
        if(!empty($response['context'])){
          $context = 'Actua como un experto en '. $response['context']. '. ';
        }
        if(!empty($response['tone'])){
          $tone = 'Utiliza un lenguage '. $response['tone']. '. ';
        }
        if(!empty($response['limit'])){
          $limit = 'Genera aproximadamente '. $response['limit']. '. ';
        }
        if(!empty($response['output'])){
          $output = 'Por favor entrega el resultado en formato "'. $response['output']. '". ';
        }
        if(!empty($response['focus'])){
          $focus = 'Enfoca tu respuesta en '. $response['focus']. '. ';
        }
        if($response['use_seo'] != 0){
          $seo = 'Genera metaetiquetas, palabras clave y un título muy llamativo para este artículo';
        }
        $prompt = $context . 'Genera un artículo completo acerca de ' . $response['input'] . '. ' . $tone . $limit . $focus . $output . $seo;
        break;
      case 'text_prompt_generator_form':
        $context = '';
        $limit = '';
        $output = '';
        $focus = '';
        $tone = '';
        $tesis = '';
        if(!empty($response['context'])){
          $context = 'Redacta un texto acerca de '. $response['context']. '. ';
        }
        if(!empty($response['tone'])){
          $tone = 'Utiliza un lenguage '. $response['tone']. '. ';
        }
        if(!empty($response['tesis'])){
          $tesis = 'Argumenta el texto en lo siguiente :  "'. $response['tesis']. '". ';
        }
        if(!empty($response['limit'])){
          $limit = 'Genera aproximadamente '. $response['limit']. '. ';
        }
        if(!empty($response['output'])){
          $output = 'Por favor entrega el resultado en formato "'. $response['output']. '". ';
        }
        if(!empty($response['focus'])){
          $focus = 'Enfoca el texto en '. $response['focus']. '. ';
        }

        $prompt = $context . 'Cuya idea central es "' . $response['input'] . '". ' . $tesis . $tone . $limit . $focus . $output;
        break;
      case 'letter_prompt_generator_form':
        $from = '';
        $id_data = '';
        $aditional = '';
        if(!empty($response['from'])){
          $from = 'Escribe esta carta utilizando como remitente a: "' . $response['from'] . '". ';
        }
        if(!empty($response['id_data'])){
          $id_data = 'enfoca la solicitud de la carta para ' . $response['id_data'];
        }
        if(!empty($response['aditional'])){
          $aditional = 'Adicional mente en la carta solicita ' . $response['aditional'];
        }
        $prompt = 'Redacta una carta formal para "' . $response['to'] . '" con el objetivo de ' . $response['goal'] . ' utilizando como asunto "' . $response['subject'] . '". ' . $from . $id_data . $aditional;
        break;
      
      case 'code_prompt_generator_form':
        $output = '' ;
        $functionality = '';
        $accuracy = '';
        $context = '';
        if(!empty($response['output'])){
          $output = 'Por favor genera el código en ' . $response['output'] . '. ';
        }
        if(!empty($response['functionality'])){
          $functionality = '. Ademas, El codigo generado debe ' . $response['functionality'] . '. ';
        }
        if(!empty($response['accuracy'])){
          $accuracy = 'Por favor genera el código teniendo en cuenta ' . $response['accuracy'] . '. ';
        }
        if(!empty($response['context'])){
          $context = 'Genera el codigo especializado en ' . $response['context'] . '. ';
        }
        $prompt = 'Por favor genera el código para crear ' . $response['input'] . $context . '. La respuesta debe estar en lenguage: "' . $response['lenguages'] . $functionality . $accuracy . $output;
        break;
      case 'professional_profile_prompt_generator_form':
        $offert = '';
        $fit = '';
        if(!empty($response['fit'])){
          $fit = 'Por favor, ten en cuenta que ' . $response['fit'] . '. ';
        }
        if(!empty($response['offert'])){
          $offert = 'Para aplicar y ser contratado para esta oferta de empleo: "' . $response['offert'] . '". ';
        }
        $prompt = 'Genera un perfil profesional formal para ' . $response['profile'] . $offert . $fit;
        break;
      default:
        return [
          '#markup'=>'prueba',
        ];
        break;
    }
    $build = [
      '#theme' => 'prompt_response',
      '#prompt' => $prompt,
    ];
    //$render = $this->renderer->render($build);

    return $build;
  }

}
