<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\saibher_web_services\Entity\SaibherApiKey;
use Drupal\user\UserInterface;

/**
 * Handles generation, validation and rotation of Saibher API keys.
 *
 * Keys are config entities that only store a salted SHA-256 hash of the
 * secret. The wire format of a valid credential is "{keyId}.{secret}".
 */
class ApiKeyService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\TimeInterface $time
   *   Date/time helper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Saibher web services logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * Creates a new API key.
   *
   * @param int $userId
   *   The ID of the user account the key will authenticate as.
   * @param string $label
   *   A human-readable label describing the consumer.
   * @param int $expires
   *   UNIX timestamp after which the key expires, or 0 for never.
   *
   * @return array{key: \Drupal\saibher_web_services\Entity\SaibherApiKey, secret: string}
   *   The saved entity and the plain-text secret. The secret is only shown
   *   once; it cannot be recovered from the stored hash.
   */
  public function createKey(int $userId, string $label, int $expires = 0): array {
    $storage = $this->entityTypeManager->getStorage('saibher_api_key');
    $id = $this->createId();

    $key = SaibherApiKey::create([
      'id' => $id,
      'label' => $label,
      'userId' => $userId,
      'status' => TRUE,
      'expires' => $expires,
      'created' => $this->time->getRequestTime(),
    ]);
    $secret = $this->generateSecretFor($key);
    $key->save();

    $this->logger->notice('API key @id created for user @uid ("@label").', [
      '@id' => $id,
      '@uid' => $userId,
      '@label' => $label,
    ]);

    return ['key' => $key, 'secret' => $secret];
  }

  /**
   * Rotates the secret of an existing key, invalidating the previous one.
   *
   * @param \Drupal\saibher_web_services\Entity\SaibherApiKey $key
   *   The key to rotate.
   *
   * @return string
   *   The new plain-text secret. The key ID does not change.
   */
  public function rotate(SaibherApiKey $key): string {
    $secret = $this->generateSecretFor($key);
    $key->save();
    $this->logger->notice('API key @id rotated.', ['@id' => $key->id()]);
    return $secret;
  }

  /**
   * Validates a credential sent by a client and returns the linked account.
   *
   * @param string $credential
   *   The raw Authorization value (without the scheme), e.g. "{id}.{secret}".
   *
   * @return \Drupal\user\UserInterface|null
   *   The user account the key authenticates as, or NULL when invalid.
   */
  public function validate(string $credential): ?UserInterface {
    [$keyId, $secret] = $this->splitCredential($credential);
    if ($keyId === NULL) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('saibher_api_key');
    $key = $storage->load($keyId);
    if (!$key instanceof SaibherApiKey) {
      return NULL;
    }

    if (!$key->isValid()) {
      $this->logger->warning('Rejected request with revoked/inactive API key @id.', ['@id' => $keyId]);
      return NULL;
    }

    if ($key->getExpires() > 0 && $key->getExpires() < $this->time->getRequestTime()) {
      $this->logger->warning('Rejected request with expired API key @id.', ['@id' => $keyId]);
      return NULL;
    }

    // Constant-time comparison of the passed secret against the stored hash.
    $computed = hash('sha256', $key->getSalt() . $secret);
    if (!hash_equals($key->getHash(), $computed)) {
      $this->logger->warning('Rejected request with wrong secret for API key @id.', ['@id' => $keyId]);
      return NULL;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($key->getUserId());
    if (!$user instanceof UserInterface || !$user->isActive()) {
      $this->logger->warning('API key @id is valid but the linked user @uid is missing or blocked.', [
        '@id' => $keyId,
        '@uid' => $key->getUserId(),
      ]);
      return NULL;
    }

    $this->touch($key);
    return $user;
  }

  /**
   * Formats a raw key value for presentation, "{id}.{secret}".
   *
   * @param string $id
   *   The key ID.
   * @param string $secret
   *   The plain-text secret.
   *
   * @return string
   */
  public static function buildCredential(string $id, string $secret): string {
    return $id . '.' . $secret;
  }

  /**
   * Splits a credential into its key ID and secret parts.
   *
   * @return array{0: ?string, 1: ?string}
   *   [keyId, secret], either may be NULL when malformed.
   */
  public static function splitCredential(string $credential): array {
    $credential = trim($credential);
    $parts = explode('.', $credential, 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
      return [NULL, NULL];
    }
    return [$parts[0], $parts[1]];
  }

  /**
   * Regenerates the salt and hash for a key and returns the new secret.
   *
   * @param \Drupal\saibher_web_services\Entity\SaibherApiKey $key
   *   The key entity.
   *
   * @return string
   *   The new plain-text secret.
   */
  public function generateSecretFor(SaibherApiKey $key): string {
    $secret = $this->randomHex(48);
    $salt = $this->randomHex(16);
    $key->setSalt($salt);
    $key->setHash(hash('sha256', $salt . $secret));
    return $secret;
  }

  /**
   * Updates the "last used" timestamp if enough time has passed.
   *
   * @param \Drupal\saibher_web_services\Entity\SaibherApiKey $key
   *   The key entity.
   */
  protected function touch(SaibherApiKey $key): void {
    $now = $this->time->getRequestTime();
    if ($key->getLastUsed() === 0 || ($now - $key->getLastUsed()) > 60) {
      $key->setLastUsed($now);
      $key->save();
    }
  }

  /**
   * Generates a unique, URL-safe ID for a new API key.
   *
   * @return string
   *   The generated ID.
   */
  public function createId(): string {
    $storage = $this->entityTypeManager->getStorage('saibher_api_key');
    do {
      $id = $this->randomHex(12);
    } while ($storage->load($id) !== NULL);
    return $id;
  }

  /**
   * Generates a random string of hex characters.
   *
   * @param int $bytes
   *   Number of random bytes (2 hex chars are generated per byte).
   *
   * @return string
   *   The hex string.
   */
  protected function randomHex(int $bytes): string {
    return bin2hex(random_bytes($bytes));
  }

  /**
   * Returns the entity storage for API keys.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public function getStorage() {
    return $this->entityTypeManager->getStorage('saibher_api_key');
  }

}