<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderChallengeInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\saibher_web_services\Service\ApiKeyService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Authenticates requests using a Saibher API key.
 *
 * The client sends the header:
 *
 *   Authorization: Saibher {keyId}.{secret}
 *
 * On success the linked user account becomes the active user, so Drupal's
 * standard permission system is used to authorize each request.
 */
class ApiKeyAuth implements AuthenticationProviderInterface, AuthenticationProviderChallengeInterface {

  /**
   * The auth scheme used in the Authorization header.
   */
  public const SCHEME = 'Saibher';

  /**
   * Constructor.
   *
   * @param \Drupal\saibher_web_services\Service\ApiKeyService $apiKeyService
   *   The API key service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Saibher web services logger.
   */
  public function __construct(
    protected ApiKeyService $apiKeyService,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request): bool {
    $authorization = $request->headers->get('Authorization', '');
    return str_starts_with(strtolower($authorization), strtolower(self::SCHEME . ' '));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   *   When a Saibher credential is present but it is invalid, expired or
   *   revoked. Returning NULL would leave the anonymous user and result in a
   *   misleading 403, so an explicit 401 is raised instead.
   */
  public function authenticate(Request $request): ?AccountInterface {
    $authorization = $request->headers->get('Authorization', '');
    $credential = trim(substr($authorization, strlen(self::SCHEME)));
    $user = $this->apiKeyService->validate($credential);
    if ($user === NULL) {
      throw new UnauthorizedHttpException(
        'Saibher',
        'La API key proporcionada es inválida, está expirada o fue revocada.'
      );
    }
    return $user;
  }

  /**
   * {@inheritdoc}
   *
   * Converts the 403 raised by the route access checks into a 401 challenge
   * when the request does not carry valid Saibher credentials.
   */
  public function challengeException(Request $request, \Exception $previous): ?\Exception {
    return new UnauthorizedHttpException(
      'Saibher',
      'Se requiere una API key válida. Envía el header "Authorization: Saibher {clave}".',
      $previous
    );
  }

}