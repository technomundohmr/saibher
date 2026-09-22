<?php

namespace Drupal\saibher_user_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handles the public affiliate link (/r/{code}).
 *
 * The actual cookie writing happens in AffiliateReferralSubscriber, which
 * reacts to the {code} route parameter on the kernel response event. This
 * controller is only responsible for redirecting the visitor to the front
 * page once the request has been processed.
 */
class AffiliateRedirectController extends ControllerBase {

  /**
   * Redirects the visitor to the site's front page.
   *
   * @param string $code
   *   The affiliate code from the URL. Not used directly here; it is read
   *   from the route parameters by the referral event subscriber.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the front page.
   */
  public function handleRedirect(string $code): RedirectResponse {
    return new RedirectResponse('/');
  }

}