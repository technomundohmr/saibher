<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\saibher_web_services\Service\ApiValidationException;
use Drupal\saibher_web_services\Service\ArticleApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * HTTP controllers for the Saibher Articles API.
 */
class ArticleApiController implements ContainerInjectionInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\saibher_web_services\Service\ArticleApiService $api
   *   The article API service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current (API-authenticated) user.
   */
  public function __construct(
    protected ArticleApiService $api,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('saibher_web_services.article_api_service'),
      $container->get('current_user'),
    );
  }

  /**
   * GET /api/v1/articles — list articles.
   */
  public function list(Request $request): JsonResponse {
    return $this->run(function () use ($request): JsonResponse {
      $params = $request->query->all();
      $result = $this->api->list($params);

      return $this->success([
        'data' => $result['items'],
        'meta' => [
          'total' => $result['total'],
          'limit' => isset($params['limit']) ? (int) $params['limit'] : ArticleApiService::DEFAULT_LIMIT,
          'offset' => isset($params['offset']) ? (int) $params['offset'] : 0,
          'sort' => $params['sort'] ?? 'created',
          'dir' => $params['dir'] ?? (($params['sort'] ?? 'created') === 'title' ? 'asc' : 'desc'),
          'language' => 'es',
        ],
      ]);
    });
  }

  /**
   * GET /api/v1/articles/{article_key} — retrieve one article.
   */
  public function get(Request $request, string $article_key): JsonResponse {
    return $this->run(function () use ($article_key): JsonResponse {
      $node = $this->api->get($article_key);
      return $this->success(['data' => $this->api->normalize($node, TRUE)]);
    });
  }

  /**
   * POST /api/v1/articles — create an article.
   */
  public function createArticle(Request $request): JsonResponse {
    return $this->run(function () use ($request): JsonResponse {
      $payload = $this->payload($request, TRUE);
      $node = $this->api->create($payload, $this->currentUser->getAccount());

      return $this->success(
        ['data' => $this->api->normalize($node, TRUE)],
        Response::HTTP_CREATED,
        ['Location' => $this->itemUrl($node)]
      );
    });
  }

  /**
   * PATCH /api/v1/articles/{article_key} — update an article.
   */
  public function update(Request $request, string $article_key): JsonResponse {
    return $this->run(function () use ($request, $article_key): JsonResponse {
      $payload = $this->payload($request, TRUE);
      $node = $this->api->update($article_key, $payload, $this->currentUser->getAccount());
      return $this->success(['data' => $this->api->normalize($node, TRUE)]);
    });
  }

  /**
   * Reads and decodes the JSON payload of a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param bool $required
   *   Whether a payload is mandatory.
   *
   * @return array
   *   The decoded payload.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the content type is not JSON or the payload is malformed.
   */
  protected function payload(Request $request, bool $required = FALSE): array {
    $contentType = strtolower((string) $request->headers->get('Content-Type', ''));
    if ($contentType !== '' && !preg_match('#^application/(?:vnd\.api\+)?json#', $contentType)) {
      throw new HttpException(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Unsupported media type. Use application/json.');
    }

    $content = $request->getContent();
    if ($content === '' || $content === '0') {
      if ($required) {
        throw new HttpException(Response::HTTP_BAD_REQUEST, 'An empty JSON payload is not valid for this request.');
      }
      return [];
    }

    $decoded = json_decode($content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new HttpException(Response::HTTP_BAD_REQUEST, 'Malformed JSON payload: ' . json_last_error_msg());
    }
    if (!is_array($decoded)) {
      throw new HttpException(Response::HTTP_BAD_REQUEST, 'The JSON payload must be an object.');
    }

    return $decoded;
  }

  /**
   * Wraps handler execution and normalizes every error to JSON.
   *
   * @param callable $handler
   *   The handler returning a JsonResponse.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function run(callable $handler): JsonResponse {
    try {
      return $handler();
    }
    catch (ApiValidationException $e) {
      return $this->error('validation_failed', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->getErrors());
    }
    catch (NotFoundHttpException $e) {
      return $this->error('not_found', $e->getMessage(), Response::HTTP_NOT_FOUND);
    }
    catch (AccessDeniedHttpException $e) {
      return $this->error('forbidden', $e->getMessage(), Response::HTTP_FORBIDDEN);
    }
    catch (HttpException $e) {
      return $this->error('request_error', $e->getMessage(), $e->getStatusCode());
    }
    catch (\Throwable $e) {
      \Drupal::logger('saibher_web_services')->error('Unhandled API error: @message', ['@message' => $e->getMessage()]);
      return $this->error('internal_error', 'Ha ocurrido un error interno.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Builds a success response.
   */
  protected function success(array $data, int $status = Response::HTTP_OK, array $headers = []): JsonResponse {
    return (new JsonResponse($data, $status, $headers))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
  }

  /**
   * Builds an error response.
   */
  protected function error(string $code, string $message, int $status, array $fields = []): JsonResponse {
    $body = [
      'error' => [
        'status' => $status,
        'code' => $code,
        'message' => $message,
      ],
    ];
    if ($fields !== []) {
      $body['error']['fields'] = $fields;
    }
    $response = new JsonResponse($body, $status);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    return $response;
  }

  /**
   * Returns the absolute URL of the item endpoint for a node ID.
   */
  protected function itemUrl(\Drupal\node\NodeInterface $node): string {
    $id = $node->id();
    $base = \Drupal::requestStack()->getCurrentRequest()->getSchemeAndHttpHost();
    return $base . '/api/v1/articles/' . $id;
  }

}