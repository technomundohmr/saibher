<?php

namespace Drupal\saibher_user_management\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for the Affiliate Referral entity.
 */
interface AffiliateReferralInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the referrer user id (the affiliate who owns the invitation link).
   *
   * @return int
   *   The referrer user id.
   */
  public function getReferrerId(): int;

  /**
   * Gets the referred user id (the newly registered user).
   *
   * @return int
   *   The referred user id.
   */
  public function getReferredId(): int;

}
