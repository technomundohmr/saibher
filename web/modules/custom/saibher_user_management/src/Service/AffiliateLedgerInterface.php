<?php

namespace Drupal\saibher_user_management\Service;

/**
 * Provides aggregated economic data for the affiliate panel/ledger.
 *
 * This service is the backend contract for the balances table rendered in the
 * user profile. All amounts are aggregated from the affiliate_commission
 * entities; every total is recomputed from the source records at query time so
 * the panel always stays consistent with what was registered.
 */
interface AffiliateLedgerInterface {

  /**
   * Gets the list of months that have activity for a given affiliate.
   *
   * "Activity" means either a referral recorded or a commission earned in
   * that month. Used to populate the month filter of the balances table.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   *
   * @return string[]
   *   A list of "Y-m" strings ordered newest first.
   */
  public function getAvailableMonths(int $referrer_uid): array;

  /**
   * Builds the full ledger for an affiliate.
   *
   * The result contains everything the balances table needs: available months,
   * totals (by currency), one row per referred person (privacy-constrained)
   * and pagination metadata.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param array $options
   *   An associative array with any of:
   *   - month: "Y-m" to filter activity to a single month, NULL for all-time.
   *   - page: The page number (1-based).
   *   - limit: Rows per page (default 20, max 100).
   *   - sort: "joined", "earned", "orders" or "name".
   *   - dir: "asc" or "desc".
   *
   * @return array
   *   An associative array with the following keys:
   *   - months: string[] the available months (see getAvailableMonths()).
   *   - totals: array with "referred_count", "buyers_count" and
   *     "by_currency" (money per currency, broken down by status).
   *   - rows: array of per-referred-person lines (see class docs).
   *   - pagination: array with page/limit/total/pages/sort/dir.
   */
  public function getLedger(int $referrer_uid, array $options = []): array;

  /**
   * Gets the earnings of a specific referred person.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param int $referred_uid
   *   The referred user id.
   * @param string|null $month
   *   Optional "Y-m" filter.
   *
   * @return array
   *   Aggregated earnings for that person, keyed by currency.
   */
  public function getEarningsForReferred(int $referrer_uid, int $referred_uid, ?string $month = NULL): array;

  /**
   * Builds a privacy-constrained display name for a referred user.
   *
   * Shows the first name and the initial of the last name (e.g. "María R.")
   * and never exposes the full email. Falls back to a generic label when the
   * profile has no recognizable data.
   *
   * @param \Drupal\user\UserInterface $user
   *   The referred user.
   *
   * @return string
   *   The masked display name.
   */
  public function getMaskedName(\Drupal\user\UserInterface $user): string;

}