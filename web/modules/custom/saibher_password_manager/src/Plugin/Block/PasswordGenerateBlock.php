<?php
namespace Drupal\saibher_password_manager\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PasswordGenerateBlock' block.
 *
 * @Block(
 *   id = "PasswordGenerateBlock",
 *   admin_label = @Translation("Bloque de generación de contraseñas"),
 * )
 */
class PasswordGenerateBlock extends BlockBase implements ContainerFactoryPluginInterface{

  /**
   * Constructs a new PasswordGenerateBlock instance.
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
        $form = $this->form_builder->getForm('\Drupal\saibher_password_manager\Form\PasswordGeneratorForm');
        return [
          '#theme' => 'password_generation_form',
          '#form' => $form,
        ];
    }
}