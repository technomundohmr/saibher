<?php

namespace Drupal\saibher_user_management\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\saibher_user_management\Entity\AffiliateReferral;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles the affiliate referral business logic.
 */
class AffiliateManager implements AffiliateManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The affiliate code generator service.
   *
   * @var \Drupal\saibher_user_management\Service\AffiliateCodeGeneratorInterface
   */
  protected AffiliateCodeGeneratorInterface $codeGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the affiliate manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saibher_user_management\Service\AffiliateCodeGeneratorInterface $code_generator
   *   The affiliate code generator service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AffiliateCodeGeneratorInterface $code_generator,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->codeGenerator = $code_generator;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferralCodeFromRequest(): ?string {
    $request = $this->requestStack->getCurrentRequest();

    if (!$request) {
      return NULL;
    }

    // Support both a dedicated route parameter (/r/{code}) and a query
    // string parameter (?ref=code) used anywhere else on the site.
    $code = $request->attributes->get('code') ?? $request->query->get('ref');

    return $code ? (string) $code : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasReferralCookie(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request instanceof \Symfony\Component\HttpFoundation\Request
      && $request->cookies->has(self::COOKIE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserByAffiliateCode(string $code): ?UserInterface {
    $storage = $this->entityTypeManager->getStorage('user');
    $uids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_affiliate_code', $code)
      ->range(0, 1)
      ->execute();

    if (empty($uids)) {
      return NULL;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->load(reset($uids));
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAffiliateLink(UserInterface $account): string {
    $code = $account->get('field_affiliate_code')->value;
    $url = \Drupal\Core\Url::fromRoute(
      'saibher_user_management.referral_redirect',
      ['code' => $code],
      ['absolute' => TRUE]
    );

    return $url->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function linkReferralFromCookie(int $referred_uid): void {
    $request = $this->requestStack->getCurrentRequest();

    if (!$request || !$request->cookies->has(self::COOKIE_NAME)) {
      return;
    }

    $code = $request->cookies->get(self::COOKIE_NAME);
    $referrer = $this->getUserByAffiliateCode($code);

    // Ignore invalid codes and prevent users from referring themselves.
    if (!$referrer || (int) $referrer->id() === $referred_uid) {
      return;
    }

    // Respect a single referral per referred user.
    $storage = $this->entityTypeManager->getStorage('affiliate_referral');
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referred', $referred_uid)
      ->range(0, 1)
      ->execute();

    if (!empty($existing)) {
      return;
    }

    $referral = AffiliateReferral::create([
      'referrer' => $referrer->id(),
      'referred' => $referred_uid,
    ]);
    $referral->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getReferralsForUser(int $referrer_uid): array {
    $referral_storage = $this->entityTypeManager->getStorage('affiliate_referral');
    $ids = $referral_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referrer', $referrer_uid)
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\saibher_user_management\Entity\AffiliateReferral[] $referrals */
    $referrals = $referral_storage->loadMultiple($ids);
    $user_storage = $this->entityTypeManager->getStorage('user');

    $referred_uids = array_map(
      fn(AffiliateReferral $referral) => $referral->getReferredId(),
      $referrals
    );

    return $user_storage->loadMultiple($referred_uids);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommissionRate(): float {
    return (float) $this->configFactory
      ->get('saibher_user_management.settings')
      ->get('commission_rate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrency(): string {
    $currency = $this->configFactory
      ->get('saibher_user_management.settings')
      ->get('default_currency');

    return $currency ?: 'COP';
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieLifetime(): int {
    return (int) $this->configFactory
      ->get('saibher_user_management.settings')
      ->get('cookie_lifetime');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCommissionStatus(): string {
    $status = $this->configFactory
      ->get('saibher_user_management.settings')
      ->get('default_status');

    return $status ?: \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface::STATUS_PENDING;
  }

}
