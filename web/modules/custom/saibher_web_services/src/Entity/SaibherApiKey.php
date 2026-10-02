<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a Saibher API key.
 *
 * A key is what an external consumer sends in the Authorization header using
 * the scheme "Saibher". The value sent on the wire is "{id}.{secret}". Only a
 * salted SHA-256 hash of the secret is stored, so a leaked database does not
 * leak usable secrets.
 *
 * @ConfigEntityType(
 *   id = "saibher_api_key",
 *   label = @Translation("Saibher API key"),
 *   label_collection = @Translation("Saibher API keys"),
 *   plural_label = @Translation("Saibher API keys"),
 *   handlers = {
 *     "list_builder" = "Drupal\saibher_web_services\Entity\ApiKeyListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\saibher_web_services\Form\ApiKeyForm",
 *       "edit" = "Drupal\saibher_web_services\Form\ApiKeyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer saibher web services",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   config_prefix = "key",
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "userId",
 *     "salt",
 *     "hash",
 *     "status",
 *     "expires",
 *     "created",
 *     "lastUsed"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/saibher-web-services/keys",
 *     "add-form" = "/admin/config/services/saibher-web-services/keys/add",
 *     "edit-form" = "/admin/config/services/saibher-web-services/keys/{saibher_api_key}/edit",
 *     "delete-form" = "/admin/config/services/saibher-web-services/keys/{saibher_api_key}/delete"
 *   }
 * )
 */
class SaibherApiKey extends ConfigEntityBase implements SaibherApiKeyInterface {

  /**
   * The key ID. Also the lookup segment sent by clients ("{id}.{secret}").
   *
   * @var string
   */
  protected $id;

  /**
   * The entity UUID.
   *
   * @var string|null
   */
  protected $uuid;

  /**
   * A human-readable label for this key.
   *
   * @var string
   */
  protected $label;

  /**
   * The ID of the user account this key authenticates as.
   *
   * @var int
   */
  protected $userId = 0;

  /**
   * Random salt used to hash the secret.
   *
   * @var string
   */
  protected $salt = '';

  /**
   * SHA-256 hash of the secret, salted.
   *
   * @var string
   */
  protected $hash = '';

  /**
   * Whether the key is currently active.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * UNIX timestamp after which the key is no longer valid (0 = never).
   *
   * @var int
   */
  protected $expires = 0;

  /**
   * UNIX timestamp of key creation.
   *
   * @var int
   */
  protected $created = 0;

  /**
   * UNIX timestamp of the last successful use (0 = never used).
   *
   * @var int
   */
  protected $lastUsed = 0;

  /**
   * {@inheritdoc}
   */
  public function getUserId(): int {
    return (int) $this->userId;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId(int $userId): static {
    $this->userId = $userId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSalt(): string {
    return $this->salt;
  }

  /**
   * {@inheritdoc}
   */
  public function setSalt(string $salt): static {
    $this->salt = $salt;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash(): string {
    return $this->hash;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash(string $hash): static {
    $this->hash = $hash;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpires(): int {
    return (int) $this->expires;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpires(int $expires): static {
    $this->expires = $expires;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated(): int {
    return (int) $this->created;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreated(int $created): static {
    $this->created = $created;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastUsed(): int {
    return (int) $this->lastUsed;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastUsed(int $lastUsed): static {
    $this->lastUsed = $lastUsed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(): bool {
    return $this->status();
  }

}