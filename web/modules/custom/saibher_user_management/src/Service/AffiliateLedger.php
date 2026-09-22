<?php

namespace Drupal\saibher_user_management\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\saibher_user_management\Entity\AffiliateCommissionInterface;
use Drupal\user\UserInterface;

/**
 * Aggregates the affiliate ledger from referral and commission records.
 */
class AffiliateLedger implements AffiliateLedgerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the affiliate ledger service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableMonths(int $referrer_uid): array {
    $months = [];

    $referral_storage = $this->entityTypeManager->getStorage('affiliate_referral');
    $commission_storage = $this->entityTypeManager->getStorage('affiliate_commission');

    foreach (['affiliate_referral', 'affiliate_commission'] as $entity_type) {
      $storage = $entity_type === 'affiliate_referral'
        ? $referral_storage
        : $commission_storage;

      $contributing_ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('referrer', $referrer_uid)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($contributing_ids)) {
        continue;
      }

      foreach (array_chunk($contributing_ids, 500) as $chunk) {
        $entities = $storage->loadMultiple($chunk);
        foreach ($entities as $entity) {
          $created = $entity->get('created')->value;
          if ($created) {
            $months[date('Y-m', $created)] = TRUE;
          }
        }
      }
    }

    $months = array_keys($months);
    rsort($months);

    return $months;
  }

  /**
   * {@inheritdoc}
   */
  public function getLedger(int $referrer_uid, array $options = []): array {
    $month = $options['month'] ?? NULL;
    $page = max(1, (int) ($options['page'] ?? 1));
    $limit = min(100, max(10, (int) ($options['limit'] ?? 20)));
    $sort = $options['sort'] ?? 'joined';
    $dir = isset($options['dir']) && strtolower($options['dir']) === 'asc' ? 'asc' : 'desc';

    if (!in_array($sort, ['joined', 'earned', 'orders', 'name'], TRUE)) {
      $sort = 'joined';
    }

    $persona = $this->buildPersona($referrer_uid, $month);

    // Sorting.
    uasort($persona, function (array $a, array $b) use ($sort, $dir) {
      switch ($sort) {
        case 'earned':
          $cmp = $a['earned'] <=> $b['earned'];
          break;

        case 'orders':
          $cmp = $a['orders'] <=> $b['orders'];
          break;

        case 'name':
          $cmp = strcasecmp($a['name'], $b['name']);
          break;

        case 'joined':
        default:
          $cmp = $a['joined'] <=> $b['joined'];
          break;
      }

      return $dir === 'desc' ? -$cmp : $cmp;
    });

    // Pagination.
    $total = count($persona);
    $pages = max(1, (int) ceil($total / $limit));
    $page = min($page, $pages);
    $offset = ($page - 1) * $limit;
    $persona = array_slice(array_values($persona), $offset, $limit, FALSE);

    return [
      'months' => $this->getAvailableMonths($referrer_uid),
      'totals' => $this->getTotals($referrer_uid, $month),
      'rows' => array_values($persona),
      'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => $pages,
        'sort' => $sort,
        'dir' => $dir,
        'offset' => $offset,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEarningsForReferred(int $referrer_uid, int $referred_uid, ?string $month = NULL): array {
    $commissions = $this->loadCommissions($referrer_uid, $month);

    $earned = [];
    foreach ($commissions as $commission) {
      if ($commission->getReferredId() !== $referred_uid) {
        continue;
      }
      $currency = $commission->getCurrency();
      $earned[$currency] = ($earned[$currency] ?? 0) + $this->amountForTotal($commission);
    }

    return $earned;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaskedName(UserInterface $user): string {
    $first = $user->hasField('field_first_name') ? $user->get('field_first_name')->value : NULL;
    $last = $user->hasField('field_last_name') ? $user->get('field_last_name')->value : NULL;
    $business = $user->hasField('field_business_name') ? $user->get('field_business_name')->value : NULL;

    $name = trim((string) $first);
    if ($last) {
      $initials = preg_replace('/[^A-Za-zÀ-ÿ0-9]/u', '', $last);
      $name .= $initials ? ' ' . mb_substr($initials, 0, 1) . '.' : '';
    }

    $fallback = $business ? (string) $business : 'Usuario #' . $user->id();

    return $name !== '' ? $name : $fallback;
  }

  /**
   * Computes the aggregate totals shown above the balances table.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param string|null $month
   *   Optional "Y-m" filter.
   *
   * @return array
   *   Associative array with:
   *   - referred_count: distinct referred people matching the filter.
   *   - buyers_count: distinct referred people with at least one effective
   *     (non-rejected) commission.
   *   - by_currency: money aggregates keyed by currency code:
   *     - total: sum of all non-rejected commissions.
   *     - pending / approved / paid / rejected: sums per status.
   */
  protected function getTotals(int $referrer_uid, ?string $month): array {
    $referrals = $this->loadReferrals($referrer_uid, $month);
    $commissions = $this->loadCommissions($referrer_uid, $month);

    $referred_ids = [];
    foreach ($referrals as $referral) {
      $referred_ids[$referral->getReferredId()] = TRUE;
    }

    $by_currency = [];
    $buyers = [];
    foreach ($commissions as $commission) {
      $currency = $commission->getCurrency();
      $status = $commission->getStatus();
      $amount = (float) $commission->getAmount();

      $by_currency[$currency] = $by_currency[$currency] ?? [
        'total' => 0.0,
        'pending' => 0.0,
        'approved' => 0.0,
        'paid' => 0.0,
        'rejected' => 0.0,
      ];

      $by_currency[$currency][$status] += $amount;
      if ($status !== AffiliateCommissionInterface::STATUS_REJECTED) {
        $by_currency[$currency]['total'] += $amount;
      }

      $referred = $commission->getReferredId();
      if ($status !== AffiliateCommissionInterface::STATUS_REJECTED && $referred) {
        $buyers[$referred] = TRUE;
      }
    }

    return [
      'referred_count' => count($referred_ids),
      'buyers_count' => count($buyers),
      'by_currency' => $by_currency,
    ];
  }

  /**
   * Builds one row per referred person for the given filters.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param string|null $month
   *   Optional "Y-m" filter.
   *
   * @return array
   *   An associative array keyed by referred user id. Each value contains:
   *   - uid, name (masked), joined (timestamp), orders (effective, non-rejected
   *     commissions), has_purchased,
   *   - comms_count, earned, paid/approved/pending/rejected, currency.
   */
  protected function buildPersona(int $referrer_uid, ?string $month): array {
    $referrals = $this->loadReferrals($referrer_uid, $month);
    $commissions = $this->loadCommissions($referrer_uid, $month);

    $referred_ids = [];
    foreach ($referrals as $referral) {
      $referred_id = $referral->getReferredId();
      $referred_ids[$referred_id] = TRUE;
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $by_id = [];

    // Seed one row per referred person (even if they have not bought yet) so
    // the table always shows every affiliate, using the sign-up date as the
    // "joined" moment.
    foreach ($referrals as $referral) {
      $referred = $referral->getReferredId();
      if (!$referred) {
        continue;
      }
      $row = $by_id[$referred] ?? $this->emptyPersona($referred);
      if (!$row['joined']) {
        $row['joined'] = (int) $referral->get('created')->value;
      }
      $by_id[$referred] = $row;
    }

    foreach ($commissions as $commission) {
      $referred = $commission->getReferredId();
      if (!$referred) {
        continue;
      }
      $referred_ids[$referred] = TRUE;

      $row = $by_id[$referred] ?? $this->emptyPersona($referred);
      $row['comms_count']++;

      $status = $commission->getStatus();
      $amount = (float) $commission->getAmount();
      $currency = $commission->getCurrency();

      if (isset($row['status_by_currency'][$currency][$status])) {
        $row['status_by_currency'][$currency][$status] += $amount;
      }
      else {
        $row['status_by_currency'][$currency][$status] = $amount;
      }

      if ($status !== AffiliateCommissionInterface::STATUS_REJECTED) {
        $row['orders']++;
        $row['earned_by_currency'][$currency] = ($row['earned_by_currency'][$currency] ?? 0) + $amount;
        $row['has_purchased'] = TRUE;
      }

      $by_id[$referred] = $row;
    }

    // Derive flat fields (single dominant currency) for the row payload.
    $persona = [];
    foreach ($by_id as $referred_uid => $row) {
      $earned = 0.0;
      $dominant_currency = $this->getSiteCurrency();
      $greatest = -1.0;
      foreach ($row['earned_by_currency'] as $currency => $value) {
        $earned += $value;
        if ($value > $greatest) {
          $greatest = $value;
          $dominant_currency = $currency;
        }
      }

      $row['earned'] = round($earned, 2);
      $row['currency'] = $dominant_currency;
      $row['paid'] = round($row['status_by_currency'][$dominant_currency]['paid'] ?? 0, 2);
      $row['approved'] = round($row['status_by_currency'][$dominant_currency]['approved'] ?? 0, 2);
      $row['pending'] = round($row['status_by_currency'][$dominant_currency]['pending'] ?? 0, 2);
      $row['rejected'] = round($row['status_by_currency'][$dominant_currency]['rejected'] ?? 0, 2);

      unset($row['status_by_currency'], $row['earned_by_currency']);

      $user = $user_storage->load($referred_uid);
      $row['name'] = $user ? $this->getMaskedName($user) : 'Usuario #' . $referred_uid;

      $persona[$referred_uid] = $row;
    }

    return $persona;
  }

  /**
   * Creates an empty row skeleton for a referred user.
   *
   * @param int $referred_uid
   *   The referred user id.
   *
   * @return array
   *   The skeleton row.
   */
  protected function emptyPersona(int $referred_uid): array {
    return [
      'uid' => $referred_uid,
      'name' => '',
      'joined' => 0,
      'orders' => 0,
      'comms_count' => 0,
      'has_purchased' => FALSE,
      'earned' => 0.0,
      'paid' => 0.0,
      'approved' => 0.0,
      'pending' => 0.0,
      'rejected' => 0.0,
      'currency' => $this->getSiteCurrency(),
      'earned_by_currency' => [],
      'status_by_currency' => [],
    ];
  }

  /**
   * Loads the referrals of an affiliate, optionally filtered by month.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param string|null $month
   *   Optional "Y-m" filter.
   *
   * @return \Drupal\saibher_user_management\Entity\AffiliateReferralInterface[]
   *   The matching referrals.
   */
  protected function loadReferrals(int $referrer_uid, ?string $month): array {
    $storage = $this->entityTypeManager->getStorage('affiliate_referral');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referrer', $referrer_uid)
      ->sort('created', 'DESC');

    if ($month) {
      $start = strtotime($month . '-01');
      $end = strtotime('+1 month', $start);
      $query->condition('created', $start, '>=');
      $query->condition('created', $end, '<');
    }

    $ids = $query->execute();
    return empty($ids) ? [] : $storage->loadMultiple($ids);
  }

  /**
   * Loads the commissions of an affiliate, optionally filtered by month.
   *
   * @param int $referrer_uid
   *   The referrer user id.
   * @param string|null $month
   *   Optional "Y-m" filter.
   *
   * @return \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface[]
   *   The matching commissions.
   */
  protected function loadCommissions(int $referrer_uid, ?string $month): array {
    $storage = $this->entityTypeManager->getStorage('affiliate_commission');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referrer', $referrer_uid)
      ->sort('created', 'DESC');

    if ($month) {
      $start = strtotime($month . '-01');
      $end = strtotime('+1 month', $start);
      $query->condition('created', $start, '>=');
      $query->condition('created', $end, '<');
    }

    $ids = $query->execute();
    return empty($ids) ? [] : $storage->loadMultiple($ids);
  }

  /**
   * Amount used for "effective earnings" (ignores rejected commissions).
   *
   * @param \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface $commission
   *   The commission.
   *
   * @return float
   *   The amount, or 0 if the commission was rejected.
   */
  protected function amountForTotal(AffiliateCommissionInterface $commission): float {
    return $commission->getStatus() === AffiliateCommissionInterface::STATUS_REJECTED
      ? 0.0
      : (float) $commission->getAmount();
  }

  /**
   * Gets the default site currency configured for the affiliate system.
   *
   * @return string
   *   A three-letter currency code.
   */
  protected function getSiteCurrency(): string {
    $currency = $this->configFactory
      ->get('saibher_user_management.settings')
      ->get('default_currency');

    return $currency ?: 'COP';
  }

}