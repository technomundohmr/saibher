<?php

namespace Drupal\saibher_wompi_gateway\Plugin\Commerce\PaymentGateway;

use GuzzleHttp\Client;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
use Drupal\commerce_payment\Entity\Payment;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "wompi_offsite_checkout",
 *   label = "Wompi (Off-site checkout)",
 *   display_label = "Wompi off-site",
 *   forms = {
 *     "offsite-payment" =
 *   "Drupal\saibher_wompi_gateway\PluginForm\OffsiteCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard",
 *   "visa",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class WompiOffsiteCheckout extends OffsitePaymentGatewayBase {

  protected $currentUser;

  protected $messenger;

  const WOMPI_PROD_ENDPOINT = 'https://production.wompi.co/';

  const WOMPI_TEST_ENDPOINT = 'https://sandbox.wompi.co/';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'redirect_method' => 'post',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $public_key = $this->configuration['public_key'];
    $private_key = $this->configuration['private_key'];
    $integrity = $this->configuration['integrity'];
    $production_endpoint = $this->configuration['production_endpoint'] ? $this->configuration['production_endpoint'] : 'https://production.wompi.co/';
    $test_endpoint = $this->configuration['test_endpoint'] ? $this->configuration['test_endpoint'] : 'https://sandbox.wompi.co/';

    $form['production_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('production_endpoint'),
      '#default_value' => $production_endpoint,
      '#description' => $this->t('https://production.wompi.co/'),
      '#required' => TRUE,
    ];

    $form['test_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('test_endpoint'),
      '#default_value' => $test_endpoint,
      '#description' => $this->t('https://sandbox.wompi.co/'),
      '#required' => TRUE,
    ];

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $public_key,
      '#description' => $this->t('This information is provided by your Wompi contact.'),
      '#required' => TRUE,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $private_key,
      '#description' => $this->t('This information is provided by your Wompi contact.'),
      '#required' => TRUE,
    ];

    $form['integrity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Integridad'),
      '#default_value' => $integrity,
      '#description' => $this->t('This information is provided by your Wompi contact.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['integrity'] = $values['integrity'];
      $this->configuration['test_endpoint'] = $values['test_endpoint'];
      $this->configuration['production_endpoint'] = $values['production_endpoint'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $mode = $this->configuration['mode'];
    $payment_state = '';
    $payment_remote_state = FALSE;
    $transaction_id = $request->get('id');
    try {

      $base_url = $mode === 'test' ? self::WOMPI_TEST_ENDPOINT : self::WOMPI_PROD_ENDPOINT;
      $client = new Client(['base_uri' => $base_url]);
      $wompi_response = $client->request('GET', 'v1/transactions/' . $transaction_id);
      $body = $wompi_response->getBody();

      $response = json_decode($body, TRUE);

      if (isset($response['data']) && isset($response['data']['status'])) {
        switch ($response['data']['status']) {
          case 'APPROVED':
            $payment_state = 'completed';
            break;
          case 'DECLINED':
            $payment_state = 'rejected';
            throw new DeclineException($this->t('The transaction No %t was declined.', ['%t' => $transaction_id]));
            break;
          case 'ERROR':
            $payment_state = 'error';
            throw new PaymentGatewayException($this->t('There is an error on the transaction No %t', ['%t' => $transaction_id]));
            break;
        }
      }
      $payment_remote_state = $response['data']['status'];
    }
    catch (ClientException $e) {
      $this->messenger->addError($this->t('There was an error on the Payment process.'));
      $response = $e->getResponse();
      $responseBodyAsString = $response->getBody()->getContents();
      $this->messenger->addError($responseBodyAsString);
      $payment_state = 'error';
    }
    
    $payment = Payment::create([
      'type' => 'payment_default',
      'order_id' => $order->id(),
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->parentEntity->id(),
      'payment_gateway_mode' => $mode,
      'state' => $payment_state,
      'remote_state' => $payment_remote_state,
      'completed' => time(),
      'remote_id' => $transaction_id,
    ]);
    $payment->save();
  }
}