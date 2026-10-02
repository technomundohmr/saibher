<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Saibher API key entities.
 */
interface SaibherApiKeyInterface extends ConfigEntityInterface {

  /**
   * Returns the ID of the user account this key authenticates as.
   *
   * @return int
   *   The user ID.
   */
  public function getUserId(): int;

  /**
   * Sets the linked user account ID.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return $this
   */
  public function setUserId(int $userId): static;

  /**
   * Returns the salt used to hash the secret.
   *
   * @return string
   */
  public function getSalt(): string;

  /**
   * Sets the salt.
   *
   * @param string $salt
   *   The salt.
   *
   * @return $this
   */
  public function setSalt(string $salt): static;

  /**
   * Returns the stored SHA-256 hash of the secret.
   *
   * @return string
   */
  public function getHash(): string;

  /**
   * Sets the stored hash.
   *
   * @param string $hash
   *   The hash.
   *
   * @return $this
   */
  public function setHash(string $hash): static;

  /**
   * Returns the expiry timestamp, or 0 for no expiration.
   *
   * @return int
   */
  public function getExpires(): int;

  /**
   * Sets the expiry timestamp.
   *
   * @param int $expires
   *   The UNIX timestamp, or 0 for no expiration.
   *
   * @return $this
   */
  public function setExpires(int $expires): static;

  /**
   * Returns the creation timestamp.
   *
   * @return int
   */
  public function getCreated(): int;

  /**
   * Sets the creation timestamp.
   *
   * @param int $created
   *   The UNIX timestamp.
   *
   * @return $this
   */
  public function setCreated(int $created): static;

  /**
   * Returns the timestamp of the last successful use, or 0 if never used.
   *
   * @return int
   */
  public function getLastUsed(): int;

  /**
   * Sets the last used timestamp.
   *
   * @param int $lastUsed
   *   The UNIX timestamp.
   *
   * @return $this
   */
  public function setLastUsed(int $lastUsed): static;

  /**
   * Whether the key is currently valid for authentication.
   *
   * @return bool
   */
  public function isValid(): bool;

}