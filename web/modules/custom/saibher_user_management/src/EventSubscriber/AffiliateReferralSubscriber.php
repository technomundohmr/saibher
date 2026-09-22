<?php

namespace Drupal\saibher_user_management\EventSubscriber;

use Drupal\saibher_user_management\Service\AffiliateManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Detects affiliate referral codes and persists them as a cookie.
 *
 * Supports both a dedicated route (/r/{code}) and a query string parameter
 * (?ref=code) present on any page. First-touch attribution is respected:
 * if a referral cookie already exists, it is never overwritten.
 */
class AffiliateReferralSubscriber implements EventSubscriberInterface {

  /**
   * The affiliate manager service.
   *
   * @var \Drupal\saibher_user_management\Service\AffiliateManagerInterface
   */
  protected AffiliateManagerInterface $affiliateManager;

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\saibher_user_management\Service\AffiliateManagerInterface $affiliate_manager
   *   The affiliate manager service.
   */
  public function __construct(AffiliateManagerInterface $affiliate_manager) {
    $this->affiliateManager = $affiliate_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

  /**
   * Attaches the referral cookie to the outgoing response, if applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    // First-touch attribution: never overwrite an existing referral cookie.
    if ($this->affiliateManager->hasReferralCookie()) {
      return;
    }

    $code = $this->affiliateManager->getReferralCodeFromRequest();

    if (!$code) {
      return;
    }

    // Only set the cookie if the code actually matches a real affiliate.
    if (!$this->affiliateManager->getUserByAffiliateCode($code)) {
      return;
    }

    $cookie = Cookie::create(
      AffiliateManagerInterface::COOKIE_NAME,
      $code,
      time() + $this->affiliateManager->getCookieLifetime(),
      '/',
      NULL,
      $event->getRequest()->isSecure(),
      TRUE,
      FALSE,
      Cookie::SAMESITE_LAX
    );

    $event->getResponse()->headers->setCookie($cookie);
  }

}
