<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirmation form to delete an affiliate commission.
 */
class AffiliateCommissionDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      '¿Eliminar la comisión #@id por un monto de @amount @currency?',
      [
        '@id' => $this->entity->id(),
        '@amount' => number_format((float) $this->entity->get('amount')->value, 2),
        '@currency' => $this->entity->get('currency')->value,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.affiliate_commission.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addStatus($this->t('La comisión fue eliminada.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}