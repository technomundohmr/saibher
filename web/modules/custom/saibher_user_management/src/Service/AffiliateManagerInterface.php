<?php

namespace Drupal\saibher_user_management\Service;

use Drupal\user\UserInterface;

/**
 * Handles the affiliate referral business logic.
 */
interface AffiliateManagerInterface {

  /**
   * The name of the cookie used to store a pending referral code.
   */
  public const COOKIE_NAME = 'saibher_ref';

  /**
   * Extracts a referral code from the current request, if any.
   *
   * Looks for the code in the query string (?ref=CODE) or in a route
   * parameter, depending on how the request reached the site.
   *
   * @return string|null
   *   The referral code found in the request, or NULL if none is present.
   */
  public function getReferralCodeFromRequest(): ?string;

  /**
   * Determines whether a referral cookie is already set on the request.
   *
   * @return bool
   *   TRUE if a referral cookie already exists (first-touch attribution
   *   should be respected and no new cookie should be written).
   */
  public function hasReferralCookie(): bool;

  /**
   * Resolves the user that owns a given affiliate code.
   *
   * @param string $code
   *   The affiliate code to look up.
   *
   * @return \Drupal\user\UserInterface|null
   *   The referrer user, or NULL if the code does not match any user.
   */
  public function getUserByAffiliateCode(string $code): ?UserInterface;

  /**
   * Builds the full public affiliate link for a given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user owning the affiliate code.
   *
   * @return string
   *   The absolute affiliate URL (e.g. https://saibher.com/r/CODE).
   */
  public function getAffiliateLink(UserInterface $account): string;

  /**
   * Links a newly registered user to the referrer stored in the cookie.
   *
   * Reads the referral cookie from the current request and, if a valid
   * referrer is found, creates an AffiliateReferral entity linking both
   * users. Does nothing if no cookie is present or the referred user is
   * already linked to a referrer.
   *
   * @param int $referred_uid
   *   The user id of the newly registered account.
   */
  public function linkReferralFromCookie(int $referred_uid): void;

  /**
   * Gets the list of users referred by a given affiliate.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of users who registered through this affiliate's link.
   */
  public function getReferralsForUser(int $referrer_uid): array;

  /**
   * Gets the configured commission rate (as a percentage of the order total).
   *
   * @return float
   *   A rate between 0 and 100.
   */
  public function getCommissionRate(): float;

  /**
   * Gets the configured default currency code for commissions.
   *
   * @return string
   *   A three-letter currency code.
   */
  public function getDefaultCurrency(): string;

  /**
   * Gets the configured referral cookie lifetime in seconds.
   *
   * @return int
   *   The cookie lifetime in seconds.
   */
  public function getCookieLifetime(): int;

  /**
   * Gets the status automatically assigned to new commissions.
   *
   * @return string
   *   One of the AffiliateCommissionInterface::STATUS_* constants.
   */
  public function getDefaultCommissionStatus(): string;

}
