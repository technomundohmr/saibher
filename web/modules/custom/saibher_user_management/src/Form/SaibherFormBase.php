<?php

namespace Drupal\saibher_user_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for all Saibher user management forms.
 *
 * Centralizes shared behaviour: attaching the module's design system
 * library and common field validation helpers, so every form built on
 * top of it stays visually and functionally consistent.
 */
abstract class SaibherFormBase extends FormBase {

  /**
   * A short, curated list of phone country codes for the target market.
   *
   * This can be extended over time without affecting the rest of the forms.
   */
  protected const PHONE_COUNTRY_CODES = [
    '+57' => '+57 (Colombia)',
    '+52' => '+52 (México)',
    '+54' => '+54 (Argentina)',
    '+56' => '+56 (Chile)',
    '+51' => '+51 (Perú)',
    '+593' => '+593 (Ecuador)',
    '+1' => '+1 (Estados Unidos / Canadá)',
  ];

  /**
   * Attaches the Saibher form library to a given form array.
   *
   * @param array $form
   *   The form render array, passed by reference.
   */
  protected function attachFormLibrary(array &$form): void {
    $form['#attached']['library'][] = 'saibher_user_management/saibher_form';
    $form['#attributes']['class'][] = 'saibher-form';
  }

  /**
   * Validates that two password fields match.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $password_field
   *   The machine name of the password field.
   * @param string $confirm_field
   *   The machine name of the confirmation field.
   */
  protected function validatePasswordsMatch(
    FormStateInterface $form_state,
    string $password_field,
    string $confirm_field
  ): void {
    $password = $form_state->getValue($password_field);
    $confirm = $form_state->getValue($confirm_field);

    if ($password !== $confirm) {
      $form_state->setErrorByName(
        $confirm_field,
        $this->t('Las contraseñas no coinciden. Revisa e inténtalo de nuevo.')
      );
    }
  }

  /**
   * Validates that the terms and conditions checkbox has been accepted.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $terms_field
   *   The machine name of the terms checkbox field.
   */
  protected function validateTermsAccepted(
    FormStateInterface $form_state,
    string $terms_field
  ): void {
    if (empty($form_state->getValue($terms_field))) {
      $form_state->setErrorByName(
        $terms_field,
        $this->t('Debes aceptar los términos y condiciones para continuar.')
      );
    }
  }

}
