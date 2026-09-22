<?php

namespace Drupal\saibher_user_management\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Generates unique, random alphanumeric affiliate codes for users.
 */
class AffiliateCodeGenerator implements AffiliateCodeGeneratorInterface {

  /**
   * Characters allowed in a generated code (avoids ambiguous characters).
   */
  private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the affiliate code generator service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateUniqueCode(int $length = 8): string {
    do {
      $code = $this->generateRandomCode($length);
    } while ($this->codeExists($code));

    return $code;
  }

  /**
   * Generates a random alphanumeric string of the given length.
   *
   * @param int $length
   *   The desired string length.
   *
   * @return string
   *   A random string built from the allowed alphabet.
   */
  protected function generateRandomCode(int $length): string {
    $alphabet_length = strlen(self::ALPHABET);
    $code = '';

    for ($i = 0; $i < $length; $i++) {
      $code .= self::ALPHABET[random_int(0, $alphabet_length - 1)];
    }

    return $code;
  }

  /**
   * Checks whether a code is already assigned to an existing user.
   *
   * @param string $code
   *   The candidate affiliate code.
   *
   * @return bool
   *   TRUE if the code is already in use, FALSE otherwise.
   */
  protected function codeExists(string $code): bool {
    $storage = $this->entityTypeManager->getStorage('user');
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_affiliate_code', $code)
      ->range(0, 1)
      ->execute();

    return !empty($existing);
  }

}
