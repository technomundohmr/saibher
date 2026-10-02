<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts exceptions raised on /api/* routes into JSON error responses.
 *
 * This is a safety net for failures thrown before the controller runs, most
 * importantly invalid or missing API keys (401) and missing route
 * permissions (403).
 *
 * The priority (10) runs after the core AuthenticationSubscriber challenge
 * conversion (75), so 403 raised for missing credentials are first turned
 * into the 401 challenge and then rendered here as JSON.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onException', 10],
    ];
  }

  /**
   * Handles exceptions for API routes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event.
   */
  public function onException(ExceptionEvent $event): void {
    $request = $event->getRequest();
    $path = $request->getPathInfo() ?? '';
    if (!str_starts_with($path, '/api/')) {
      return;
    }

    $throwable = $event->getThrowable();
    $headers = [];

    if ($throwable instanceof UnauthorizedHttpException) {
      $status = Response::HTTP_UNAUTHORIZED;
      $code = 'unauthorized';
      $message = 'Se requiere una API key válida. Envía el header "Authorization: Saibher {clave}".';
      $headers['WWW-Authenticate'] = 'Saibher';
    }
    elseif ($throwable instanceof AccessDeniedHttpException) {
      $status = Response::HTTP_FORBIDDEN;
      $code = 'forbidden';
      $message = 'La cuenta autenticada no tiene permiso para realizar esta operación.';
    }
    elseif ($throwable instanceof NotFoundHttpException) {
      $status = Response::HTTP_NOT_FOUND;
      $code = 'not_found';
      $message = 'Recurso no encontrado.';
    }
    elseif ($throwable instanceof HttpException) {
      $status = $throwable->getStatusCode();
      $code = 'request_error';
      $message = $throwable->getMessage();
    }
    else {
      $status = Response::HTTP_INTERNAL_SERVER_ERROR;
      $code = 'internal_error';
      $message = 'Ha ocurrido un error interno.';
    }

    $response = new JsonResponse([
      'error' => [
        'status' => $status,
        'code' => $code,
        'message' => $message,
      ],
    ], $status, $headers);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    $event->setResponse($response);
  }

}