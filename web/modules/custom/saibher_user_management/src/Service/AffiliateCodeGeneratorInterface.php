<?php

namespace Drupal\saibher_user_management\Service;

/**
 * Generates unique affiliate codes for users.
 */
interface AffiliateCodeGeneratorInterface {

  /**
   * Generates a random alphanumeric code guaranteed to be unique.
   *
   * @param int $length
   *   The desired code length. Defaults to 8 characters.
   *
   * @return string
   *   A unique affiliate code, not currently assigned to any user.
   */
  public function generateUniqueCode(int $length = 8): string;

}
