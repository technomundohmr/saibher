<?php

namespace Drupal\saibher_seo\EventSubscriber;

use Drupal\saibher_seo\ImageOptimizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Aplica el optimizador de imagenes sobre la respuesta final.
 */
class ImageOptimizerSubscriber implements EventSubscriberInterface {

  /**
   * El optimizador.
   *
   * @var \Drupal\saibher_seo\ImageOptimizer
   */
  protected $optimizer;

  /**
   * Construye el subscriber.
   *
   * @param \Drupal\saibher_seo\ImageOptimizer $optimizer
   *   El optimizador.
   */
  public function __construct(ImageOptimizer $optimizer) {
    $this->optimizer = $optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Prioridad baja para correr despues de los filtros de HTML de core y no
    // pelear con ellos por el contenido.
    return [
      KernelEvents::RESPONSE => [['onResponse', -1000]],
    ];
  }

  /**
   * Optimiza las imagenes del cuerpo de la respuesta.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   El evento de respuesta.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();

    if (!$response->isSuccessful() || !is_string($response->getContent())) {
      return;
    }

    $content_type = (string) $response->headers->get('Content-Type');
    if (!str_contains($content_type, 'text/html')) {
      return;
    }

    // Sin etag ni cache propio: la respuesta se sirve con el HTML ya optimizado
    // y el page cache de Drupal guarda ese mismo resultado.
    $optimized = $this->optimizer->optimize($response->getContent());
    if ($optimized !== $response->getContent()) {
      $response->setContent($optimized);
    }
  }

}