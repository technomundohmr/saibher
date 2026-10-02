<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists Saibher API key entities.
 */
class ApiKeyListBuilder extends ConfigEntityListBuilder {

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    protected EntityStorageInterface $userStorage,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Etiqueta');
    $header['id'] = $this->t('Key ID');
    $header['user'] = $this->t('Cuenta vinculada');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Creada');
    $header['expires'] = $this->t('Expira');
    $header['last_used'] = $this->t('Último uso');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\saibher_web_services\Entity\SaibherApiKey $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['user'] = $this->userLabel($entity->getUserId());
    $row['status'] = $entity->status() ? $this->t('Activa') : $this->t('Revocada');
    $row['created'] = $entity->getCreated() ? $this->t('@date', ['@date' => $this->formatDate($entity->getCreated())]) : '-';
    $row['expires'] = $entity->getExpires() ? $this->t('@date', ['@date' => $this->formatDate($entity->getExpires())]) : $this->t('Nunca');
    $row['last_used'] = $entity->getLastUsed() ? $this->t('@date', ['@date' => $this->formatDate($entity->getLastUsed())]) : '-';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['add_key'] = [
      '#type' => 'link',
      '#title' => $this->t('Nueva API key'),
      '#url' => Url::fromRoute('entity.saibher_api_key.add_form'),
      '#attributes' => ['class' => ['button', 'button--small']],
      '#weight' => -10,
      '#prefix' => '<div class="saibher-ws-add-key">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

  /**
   * Formats a UNIX timestamp for display.
   */
  protected function formatDate(int $timestamp): string {
    return \Drupal::service('date.formatter')->format($timestamp, 'short');
  }

  /**
   * Returns a label for the linked user, or a warning when missing.
   */
  protected function userLabel(int $uid): string {
    $user = $this->userStorage->load($uid);
    if ($user instanceof UserInterface) {
      return $user->getDisplayName() . ' (' . $uid . ')';
    }
    return $this->t('<- Usuario no encontrado (%uid)', ['%uid' => $uid]);
  }

}