<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\saibher_web_services\Service\ApiKeyService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands to manage Saibher Web Services API keys from the CLI.
 */
final class SaibherApiKeyCommands extends DrushCommands {

  /**
   * Constructor.
   */
  public function __construct(
    protected ApiKeyService $apiKeyService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Creates a new API key and prints the credential.
   */
  #[CLI\Command(name: 'saibher:api-key:add')]
  #[CLI\Argument(name: 'label', description: 'Human-readable label for the consumer.')]
  #[CLI\Option(name: 'uid', description: 'The user account this key will authenticate as.')]
  #[CLI\Option(name: 'days', description: 'Days the key is valid (0 = never expires).')]
  #[CLI\Usage(name: 'drush saibher:api-key:add "Sistema externo" --uid=3 --days=365', description: 'Creates a one-year key linked to user 3.')]
  public function add(string $label, array $options = [
    'uid' => NULL,
    'days' => 0,
  ]): int {
    $uid = (int) $options['uid'];
    if ($uid <= 0) {
      throw new \InvalidArgumentException('The --uid option is required and must point to an active user account.');
    }
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user instanceof \Drupal\user\UserInterface || !$user->isActive()) {
      throw new \InvalidArgumentException(sprintf('User %d does not exist or is not active.', $uid));
    }

    $days = max(0, (int) $options['days']);
    $expires = $days === 0 ? 0 : $days * 86400 + \Drupal::time()->getRequestTime();

    ['key' => $key, 'secret' => $secret] = $this->apiKeyService->createKey($uid, $label, $expires);
    $credential = ApiKeyService::buildCredential($key->id(), $secret);

    $this->io()->success(sprintf('API key "%s" created.', $label));
    $this->io()->writeln('Key ID:    ' . $key->id());
    $this->io()->writeln('Credential (Authorization value):');
    $this->io()->writeln('  ' . $credential);
    $this->io()->note('The secret is shown only once.');
    return 0;
  }

  /**
   * Lists all API keys.
   */
  #[CLI\Command(name: 'saibher:api-key:list')]
  public function listKeys(): int {
    $keys = $this->entityTypeManager->getStorage('saibher_api_key')->loadMultiple();
    if ($keys === []) {
      $this->io()->note('No API keys exist yet. Use "drush saibher:api-key:add".');
      return 0;
    }

    $rows = [];
    foreach ($keys as $key) {
      $states = [
        'revoked' => !$key->status(),
        'expired' => $key->status() && $key->getExpires() > 0 && $key->getExpires() < \Drupal::time()->getRequestTime(),
      ];
      $state = 'active';
      foreach ($states as $label => $bad) {
        if ($bad) {
          $state = $label;
        }
      }
      $rows[] = [
        $key->id(),
        $key->label(),
        $key->getUserId(),
        $state,
        $key->getExpires() ? date('Y-m-d H:i', $key->getExpires()) : 'never',
        $key->getLastUsed() ? date('Y-m-d H:i', $key->getLastUsed()) : '-',
      ];
    }

    $this->io()->table(['ID', 'Label', 'UID', 'State', 'Expires', 'Last used'], $rows);
    return 0;
  }

  /**
   * Revokes an API key immediately.
   */
  #[CLI\Command(name: 'saibher:api-key:revoke')]
  #[CLI\Argument(name: 'id', description: 'The API key ID to revoke.')]
  public function revoke(string $id): int {
    $key = $this->entityTypeManager->getStorage('saibher_api_key')->load($id);
    if ($key === NULL) {
      throw new \InvalidArgumentException(sprintf('API key "%s" not found.', $id));
    }
    $key->setStatus(FALSE);
    $key->save();
    $this->io()->success(sprintf('API key "%s" revoked. Requests using it now return 401.', $id));
    return 0;
  }

  /**
   * Rotates an API key secret.
   */
  #[CLI\Command(name: 'saibher:api-key:rotate')]
  #[CLI\Argument(name: 'id', description: 'The API key ID to rotate.')]
  public function rotate(string $id): int {
    $key = $this->entityTypeManager->getStorage('saibher_api_key')->load($id);
    if ($key === NULL) {
      throw new \InvalidArgumentException(sprintf('API key "%s" not found.', $id));
    }
    $secret = $this->apiKeyService->rotate($key);
    $this->io()->success(sprintf('API key "%s" rotated. The previous secret is no longer valid.', $id));
    $this->io()->writeln('New credential (Authorization value):');
    $this->io()->writeln('  ' . ApiKeyService::buildCredential($id, $secret));
    return 0;
  }

}