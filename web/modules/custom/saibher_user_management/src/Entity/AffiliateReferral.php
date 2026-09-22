<?php

namespace Drupal\saibher_user_management\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait; // 1. Importar el trait
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Affiliate Referral entity.
 *
 * Stores the relationship between a referrer (affiliate) and a referred
 * (invited) user, created every time a new account registers through an
 * affiliate link.
 *
 * @ContentEntityType(
 *   id = "affiliate_referral",
 *   label = @Translation("Affiliate referral"),
 *   base_table = "saibher_affiliate_referral",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class AffiliateReferral extends ContentEntityBase implements AffiliateReferralInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['referrer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referrer'))
      ->setDescription(t('The affiliate user who owns the referral link.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['referred'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referred user'))
      ->setDescription(t('The newly registered user who signed up through the affiliate link.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the referral was recorded.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the referral was last updated.'));

    return $fields;
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
  public function getReferredId(): int {
    return (int) $this->get('referred')->target_id;
  }

  // 3. Se eliminan getChangedTime() y setChangedTime() manuales 
  // porque el EntityChangedTrait los implementa junto a getChangedTimeAcrossTranslations()

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += ['created' => \Drupal::time()->getRequestTime()];
  }

}