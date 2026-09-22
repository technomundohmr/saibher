<?php

namespace Drupal\saibher_user_management\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\saibher_user_management\Service\AffiliateLedgerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Serves the affiliate balances data for the profile panel table.
 *
 * This is the backend contract consumed by the balances table rendered in the
 * user profile. It returns all the information the frontend needs (months,
 * totals, rows and pagination metadata) as JSON.
 */
class AffiliateBalancesController extends ControllerBase {

  /**
   * The affiliate ledger service.
   *
   * @var \Drupal\saibher_user_management\Service\AffiliateLedgerInterface
   */
  protected AffiliateLedgerInterface $ledger;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\saibher_user_management\Service\AffiliateLedgerInterface $ledger
   *   The affiliate ledger service.
   */
  public function __construct(AffiliateLedgerInterface $ledger) {
    $this->ledger = $ledger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saibher_user_management.ledger')
    );
  }

  /**
   * Returns the balances payload for the current user.
   *
   * Supported query parameters:
   * - month: "Y-m" to filter by a single month (optional).
   * - page: 1-based page number (default 1).
   * - limit: rows per page, 10-100 (default 20).
   * - sort: joined|earned|orders|name (default joined).
   * - dir: asc|desc (default desc).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The ledger payload.
   */
  public function balances(Request $request): CacheableJsonResponse {
    $uid = (int) $this->currentUser()->id();

    $month = $request->query->get('month');
    if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
      $month = NULL;
    }

    $options = [
      'month' => $month,
      'page' => (int) $request->query->get('page', 1),
      'limit' => (int) $request->query->get('limit', 20),
      'sort' => $request->query->get('sort', 'joined'),
      'dir' => $request->query->get('dir', 'desc'),
    ];

    $ledger = $this->ledger->getLedger($uid, $options);

    $ledger['meta'] = [
      'currency' => $this->config('saibher_user_management.settings')
        ->get('default_currency') ?: 'COP',
      'user' => $uid,
    ];

    $response = new CacheableJsonResponse($ledger);
    $response->getCacheableMetadata()
      ->addCacheContexts(['user'])
      ->addCacheTags(['saibher_affiliate_' . $uid]);

    return $response;
  }

}