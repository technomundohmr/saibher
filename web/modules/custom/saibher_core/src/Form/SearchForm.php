<?php

namespace Drupal\saibher_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\saibher_tracking\TrackingService; 


class SearchForm extends FormBase {
    protected $TrackingService;
    protected $url_generator;

  public function __construct(Url $url_generator, TrackingService $TrackingService ) {
    $this->TrackingService = $TrackingService;
    $this->url_generator = $url_generator;
  }
  
  public static function create(ContainerInterface $container) {
    $form = new static(
        $container->get('saibher_core.url'),
        $container->get('saibher_tracking.tracking')
    );
    return $form;
  }
  public function getFormId() {
    return 'saibher_core_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => t('Qué estás buscano Hoy'),
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    $form['submit']['#attributes']['class'][] = 'btn-primary-red btn mb-1';
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keywords = $form_state->getValue('keywords');
    $prueba = $this->TrackingService->newTracking($keywords, 'Búsqueda');
    $url = $this->url_generator->fromRoute('view.search_results.page_1');
    $url->setOption('query', ['body_value' => $keywords]);
    $form_state->setRedirectUrl($url);
  }
}