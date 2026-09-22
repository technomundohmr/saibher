<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the affiliate marketing system.
 */
class AffiliateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saibher_user_management.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saibher_affiliate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saibher_user_management.settings');

    $form['commission_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Comisión porcentual'),
      '#description' => $this->t('Porcentaje del total del pedido que se acredita al afiliado (ej. 10 = 10%%).'),
      '#min' => 0,
      '#max' => 100,
      '#step' => '0.01',
      '#default_value' => $config->get('commission_rate'),
      '#required' => TRUE,
    ];

    $form['default_currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moneda por defecto'),
      '#description' => $this->t('Código ISO de tres letras para las comisiones (ej. COP).'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $config->get('default_currency'),
      '#required' => TRUE,
    ];

    $form['default_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Estado inicial de la comisión'),
      '#description' => $this->t('Estado con el que se crean las comisiones automáticas.'),
      '#options' => [
        'pending' => $this->t('Pendiente'),
        'approved' => $this->t('Aprobada'),
        'paid' => $this->t('Pagada'),
      ],
      '#default_value' => $config->get('default_status'),
      '#required' => TRUE,
    ];

    $form['cookie_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Duración de la cookie de referido'),
      '#description' => $this->t('En días (cómo el sistema recuerda quién te refirió). Se recomienda un valor alto para respetar la atribución de primer contacto.'),
      '#min' => 1,
      '#default_value' => (int) ($config->get('cookie_lifetime') / 86400),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $currency = mb_strtoupper(trim($form_state->getValue('default_currency')));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
      $form_state->setErrorByName(
        'default_currency',
        $this->t('La moneda debe ser un código ISO de tres letras (ej. COP).')
      );
    }
    $form_state->setValue('default_currency', $currency);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('saibher_user_management.settings')
      ->set('commission_rate', $form_state->getValue('commission_rate'))
      ->set('default_currency', $form_state->getValue('default_currency'))
      ->set('default_status', $form_state->getValue('default_status'))
      ->set('cookie_lifetime', (int) $form_state->getValue('cookie_lifetime') * 86400)
      ->save();

    parent::submitForm($form, $form_state);
  }

}