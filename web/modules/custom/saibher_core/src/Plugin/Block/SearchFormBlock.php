<?php
namespace Drupal\saibher_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SearchFormBlock' block.
 *
 * @Block(
 *   id = "SearchFormBlock",
 *   admin_label = @Translation("Bloque de búsqueda"),
 * )
 */
class SearchFormBlock extends BlockBase implements ContainerFactoryPluginInterface{

  /**
   * Constructs a new SearchFormBlock instance.
   */
  public function __construct(
    array $configuration, 
    $plugin_id, 
    $plugin_definition, 
    protected FormBuilderInterface $form_builder
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->form_builder->getForm('\Drupal\saibher_core\Form\SearchForm');
    return [
      '#theme' => 'search_form',
      '#form' => $form,
    ];
  }
}