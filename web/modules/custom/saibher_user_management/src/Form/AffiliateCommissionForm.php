<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the add/edit form for an affiliate commission.
 */
class AffiliateCommissionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $operation = $this->operation === 'add' ? 'add' : 'edit';
    return 'affiliate_commission_' . $operation . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface $entity */
    $entity = $this->entity;

    $form['referrer'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Afiliado (quien gana la comisión)'),
      '#description' => $this->t('El usuario afiliado al que se le acredita esta comisión.'),
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#default_value' => $entity->get('referrer')->entity,
      '#required' => TRUE,
      '#maxlength' => 128,
    ];

    $form['referred'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Cliente referido (opcional)'),
      '#description' => $this->t('El usuario cuya compra origina la comisión.'),
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#default_value' => $entity->get('referred')->entity,
      '#required' => FALSE,
      '#maxlength' => 128,
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Monto'),
      '#description' => $this->t('Monto de la comisión.'),
      '#min' => 0,
      '#step' => '0.01',
      '#default_value' => $entity->get('amount')->value,
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moneda'),
      '#description' => $this->t('Código ISO de tres letras (ej. COP).'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $entity->get('currency')->value,
      '#required' => TRUE,
    ];

    $status_options = $entity
      ->getFieldDefinition('status')
      ->getSetting('allowed_values');

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Estado'),
      '#options' => $status_options,
      '#default_value' => $entity->get('status')->value,
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notas'),
      '#description' => $this->t('Explica el origen de la comisión.'),
      '#rows' => 3,
      '#default_value' => $entity->get('notes')->value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\saibher_user_management\Entity\AffiliateCommissionInterface $entity */
    $entity = $this->entity;

    $entity->set('referrer', $form_state->getValue('referrer'));
    $entity->set('referred', $form_state->getValue('referred') ?? []);
    $entity->set('amount', $form_state->getValue('amount'));
    $entity->set('currency', mb_strtoupper($form_state->getValue('currency')));
    $entity->set('status', $form_state->getValue('status'));
    $entity->set('notes', $form_state->getValue('notes'));

    $entity->save();

    $this->messenger()->addStatus(
      $this->t('La comisión fue @action correctamente.', [
        '@action' => $this->operation === 'add' ? 'registrada' : 'actualizada',
      ])
    );

    $form_state->setRedirectUrl(
      Url::fromRoute('entity.affiliate_commission.collection')
    );
  }

}