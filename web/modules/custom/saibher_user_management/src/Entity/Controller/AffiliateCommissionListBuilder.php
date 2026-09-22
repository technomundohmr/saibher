<?php

namespace Drupal\saibher_user_management\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a listing of affiliate commissions for the administrator.
 *
 * @ingroup saibher_user_management
 */
class AffiliateCommissionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['referrer'] = $this->t('Afiliado');
    $header['referred'] = $this->t('Referido');
    $header['amount'] = $this->t('Monto');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface $entity */
    $referrer = $entity->get('referrer')->entity;
    $referred = $entity->get('referred')->entity;

    $row['id'] = $entity->id();
    $row['referrer'] = $referrer
      ? Link::fromTextAndUrl(
        $referrer->label(),
        Url::fromRoute('entity.user.canonical', ['user' => $referrer->id()])
      )
      : $this->t('—');
    $row['referred'] = $referred
      ? Link::fromTextAndUrl(
        $referred->label(),
        Url::fromRoute('entity.user.canonical', ['user' => $referred->id()])
      )
      : $this->t('—');
    $number = number_format((float) $entity->getAmount(), 2);
    $row['amount'] = $number . ' ' . $entity->getCurrency();
    $row['status'] = $entity->getStatusLabel();
    $row['created'] = \Drupal::service('date.formatter')
      ->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Delete only if the current user has the "administer" permission that
    // grants global bypass; parent already respects the entity's
    // admin_permission through the route requirements.
    return $operations;
  }

  /**
   * {@inheritdoc}
   *
   * Keeps the referral/commission tables consistent by deleting the amount of
   * money the form is shown in the local currency. Overriding render() only to
   * annotate the summary — parent::render() already handles pager + table.
   */
  public function render() {
    $build = parent::render();
    return $build;
  }

}