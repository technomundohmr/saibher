<?php

namespace Drupal\saibher_user_management\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for the Affiliate Commission entity.
 */
interface AffiliateCommissionInterface extends \Drupal\Core\Entity\ContentEntityInterface, EntityChangedInterface {

  /**
   * Commission status constants.
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_APPROVED = 'approved';
  public const STATUS_PAID = 'paid';
  public const STATUS_REJECTED = 'rejected';

  /**
   * Gets the referrer user id (the affiliate that earns the commission).
   *
   * @return int
   *   The referrer user id.
   */
  public function getReferrerId(): int;

  /**
   * Gets the referred user id, if any.
   *
   * @return int|null
   *   The referred user id, or NULL for commissions not tied to a referred
   *   account (e.g. legacy manual registrations).
   */
  public function getReferredId(): ?int;

  /**
   * Gets the referral entity id that originated this commission, if any.
   *
   * @return int|null
   *   The referral id, or NULL when the commission was registered manually
   *   without a tracking referral.
   */
  public function getReferralId(): ?int;

  /**
   * Gets the commission amount.
   *
   * @return string
   *   The amount as a numeric string.
   */
  public function getAmount(): string;

  /**
   * Sets the commission amount.
   *
   * @param string $amount
   *   The amount as a numeric string.
   *
   * @return $this
   */
  public function setAmount(string $amount);

  /**
   * Gets the currency code of the commission amount.
   *
   * @return string
   *   A three-letter currency code (e.g. "COP").
   */
  public function getCurrency(): string;

  /**
   * Gets the commission status.
   *
   * @return string
   *   One of the STATUS_* constants.
   */
  public function getStatus(): string;

  /**
   * Sets the commission status.
   *
   * @param string $status
   *   One of the STATUS_* constants.
   *
   * @return $this
   */
  public function setStatus(string $status);

  /**
   * Gets a human readable label for the current status.
   *
   * @return string
   *   The translated status label.
   */
  public function getStatusLabel(): string;

  /**
   * Gets the free-text notes of the commission.
   *
   * @return string
   *   The notes, or an empty string.
   */
  public function getNotes(): string;

}