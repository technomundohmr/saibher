<?php

namespace Drupal\saibher_user_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\saibher_user_management\Service\AffiliateManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the Saibher user profile page.
 */
class UserProfileController extends ControllerBase {

  /**
   * The affiliate manager service.
   *
   * @var \Drupal\saibher_user_management\Service\AffiliateManagerInterface
   */
  protected AffiliateManagerInterface $affiliateManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected CountryManagerInterface $countryManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\saibher_user_management\Service\AffiliateManagerInterface $affiliate_manager
   *   The affiliate manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct(
    AffiliateManagerInterface $affiliate_manager,
    RendererInterface $renderer,
    CountryManagerInterface $country_manager
  ) {
    $this->affiliateManager = $affiliate_manager;
    $this->renderer = $renderer;
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saibher_user_management.affiliate_manager'),
      $container->get('renderer'),
      $container->get('country_manager')
    );
  }

  /**
   * Builds the render array for the user profile page.
   *
   * @return array
   *   A render array using the saibher_user_profile theme hook.
   */
  public function view(): array {
    /** @var \Drupal\user\UserInterface $user_entity */
    $user_entity = $this->entityTypeManager()
      ->getStorage('user')
      ->load($this->currentUser()->id());

    return [
      '#theme' => 'saibher_user_profile',
      '#account' => $user_entity,
      '#affiliate_link' => $this->affiliateManager->getAffiliateLink($user_entity),
      '#country_name' => $this->getCountryName($user_entity),
      '#picture' => $this->getPictureRender($user_entity),
      '#edit_url' => Url::fromRoute('saibher_user_management.profile_edit'),
      '#balances_endpoint' => Url::fromRoute('saibher_user_management.affiliate_balances')->toString(),
      '#show_balances' => $this->currentUser()->hasPermission('saibher_user_management.view_own_affiliate_panel'),
      '#attached' => [
        'library' => ['saibher_user_management/saibher_profile'],
      ],
    ];
  }

  /**
   * Resolves the human-readable country name for the user, if any.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return string|null
   *   The localized country name, or NULL when not set.
   */
  protected function getCountryName(UserInterface $account): ?string {
    $code = $account->hasField('field_country') ? $account->get('field_country')->value : NULL;

    if (!$code) {
      return NULL;
    }

    $countries = $this->countryManager->getList();
    return $countries[$code] ?? $code;
  }

  /**
   * Builds a friendly render array for the user's profile picture.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return array|null
   *   A render array for the picture field, or NULL when empty.
   */
  protected function getPictureRender(UserInterface $account): ?array {
    if (!$account->hasField('user_picture') || $account->get('user_picture')->isEmpty()) {
      return NULL;
    }

    return [
      '#theme' => 'image_formatter',
      '#item' => $account->get('user_picture')->first(),
      '#image_style' => 'thumbnail',
      '#alt' => $this->t('Foto de perfil de @name', ['@name' => $account->label()]),
    ];
  }

}