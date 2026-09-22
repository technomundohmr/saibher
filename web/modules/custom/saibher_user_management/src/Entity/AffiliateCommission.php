<?php

namespace Drupal\saibher_user_management\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Affiliate Commission entity.
 *
 * Represents an economic transaction earned by an affiliate. A commission is
 * registered manually by an administrator.
 *
 * @ContentEntityType(
 *   id = "affiliate_commission",
 *   label = @Translation("Affiliate commission"),
 *   label_singular = @Translation("affiliate commission"),
 *   label_plural = @Translation("affiliate commissions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count affiliate commission",
 *     plural = "@count affiliate commissions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\saibher_user_management\Entity\Controller\AffiliateCommissionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\saibher_user_management\Form\AffiliateCommissionForm",
 *       "add" = "Drupal\saibher_user_management\Form\AffiliateCommissionForm",
 *       "edit" = "Drupal\saibher_user_management\Form\AffiliateCommissionForm",
 *       "delete" = "Drupal\saibher_user_management\Form\AffiliateCommissionDeleteForm",
 *     },
 *   },
 *   admin_permission = "saibher_user_management.manage_affiliate_commissions",
 *   list_cache_contexts = { "user" },
 *   base_table = "saibher_affiliate_commission",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/saibher/affiliate-commissions/add",
 *     "edit-form" = "/admin/saibher/affiliate-commissions/{affiliate_commission}/edit",
 *     "delete-form" = "/admin/saibher/affiliate-commissions/{affiliate_commission}/delete",
 *     "collection" = "/admin/saibher/affiliate-commissions",
 *   },
 * )
 */
class AffiliateCommission extends ContentEntityBase implements AffiliateCommissionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user that created the commission record.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['referrer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Affiliate'))
      ->setDescription(t('The affiliate user that earns this commission.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE);

    $fields['referred'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referred user'))
      ->setDescription(t('The referred customer whose purchase originated this commission.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(FALSE);

    $fields['referral'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referral'))
      ->setDescription(t('The affiliate referral tracking that originated this commission.'))
      ->setSetting('target_type', 'affiliate_referral')
      ->setSetting('handler', 'default')
      ->setRequired(FALSE);

    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Amount'))
      ->setDescription(t('The commission amount earned by the affiliate.'))
      ->setSetting('precision', 16)
      ->setSetting('scale', 2)
      ->setRequired(TRUE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The three-letter currency code of the amount.'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('COP')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The lifecycle status of the commission.'))
      ->setSetting('allowed_values', [
        AffiliateCommissionInterface::STATUS_PENDING => t('Pendiente'),
        AffiliateCommissionInterface::STATUS_APPROVED => t('Aprobada'),
        AffiliateCommissionInterface::STATUS_PAID => t('Pagada'),
        AffiliateCommissionInterface::STATUS_REJECTED => t('Rechazada'),
      ])
      ->setDefaultValue(AffiliateCommissionInterface::STATUS_PENDING)
      ->setRequired(TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes explaining the commission (required for manual entries).'))
      ->setRequired(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the commission was recorded.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the commission was last updated.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
      'created' => \Drupal::time()->getRequestTime(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getReferrerId(): int {
    return (int) $this->get('referrer')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferredId(): ?int {
    return $this->get('referred')->isEmpty()
      ? NULL
      : (int) $this->get('referred')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferralId(): ?int {
    return $this->get('referral')->isEmpty()
      ? NULL
      : (int) $this->get('referral')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount(): string {
    return $this->get('amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(string $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency(): string {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel(): string {
    $allowed = $this->getFieldDefinition('status')
      ->getSetting('allowed_values');

    return $allowed[$this->getStatus()] ?? $this->getStatus();
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes(): string {
    return $this->get('notes')->isEmpty() ? '' : $this->get('notes')->value;
  }

}