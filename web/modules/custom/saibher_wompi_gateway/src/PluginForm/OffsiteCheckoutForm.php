<?php

namespace Drupal\saibher_wompi_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Offsite Redeban Form.
 */
class OffsiteCheckoutForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

    
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected AccountProxy $currentUser;

  public $protected_data;

  /**
   * Constructs a new OffsiteCheckoutForm object.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The entity type manager.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $discount_apply = null;

    $form = parent::buildConfigurationForm($form, $form_state);

    $order_data = $this->get_order_data();
    
    $form['#content']['summary'] = $order_data['summary_items'];

    $form['#attributes'] = [
      'class' => ['wompi-button'],
      'id' => 'payment-method-form-container',
    ];

    $form['payment_form'] = [
      '#type' => 'inline_template',
      '#template' =>
      '<script
          src="https://checkout.wompi.co/widget.js"
          data-render="button"
          data-public-key="' . $order_data['public_key'] . '"
          data-currency="COP"
          data-amount-in-cents="' . intval($order_data['total_order']) . '00"
          data-reference="' . $order_data['order_id']. '"
           data-redirect-url="' . $form['#return_url'] . '"
          data-signature:integrity=' . $order_data['secret_integrity'] .'
          >
        </script>',
    ];
    
    return $form;
  }

  public function get_order_data() {
    $response = NULL;
    /** 
    * @var \Drupal\commerce_payment\Entity\Payment $payment 
    */
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();
    $order_items = $order->order_items->referencedEntities();
    $public_key = $payment_gateway_plugin->getConfiguration()['public_key'];
    $integrity = $payment_gateway_plugin->getConfiguration()['integrity'];
    $summary_items = [];

    foreach ($order_items as $item_id => $item) {
      $product = $item->get('purchased_entity')->entity;
      $product_entity = $this->entityTypeManager->getStorage('commerce_product')->load($product->getProductId());

      $summary_items[] = [
        'title' => $product->getTitle(),
        'price' => number_format($product->getPrice()->getNumber(), 0, '', '.'),
        'quantity' => number_format($item->getQuantity(), 0, '', '.'),
        'total_price' => number_format($item->getQuantity() * $product->getPrice()->getNumber(), 0, '', '.')
      ];
    }

    $total_order_amount = intval($order->getTotalPrice()->getNumber());

    $secret_string = $order->id() . $total_order_amount . '00COP' . $integrity;

    $secret_integrity = hash("sha256", $secret_string);

    $response = [
      'payment_plugin' => $payment_gateway_plugin,
      'public_key' => $public_key,
      'summary_items' => $summary_items,
      'secret_integrity' => $secret_integrity,
      'total_order' => $total_order_amount,
      'host' => $_SERVER['HTTP_HOST'],
      'order_id' => $order->id(),
      'integrity' => $integrity,
    ];

    return $response;
  }
}

