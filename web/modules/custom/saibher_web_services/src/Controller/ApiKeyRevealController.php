<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\saibher_web_services\Authentication\Provider\ApiKeyAuth;
use Drupal\saibher_web_services\Entity\SaibherApiKey;
use Drupal\saibher_web_services\Service\ApiKeyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Displays a newly generated API key secret exactly once.
 */
class ApiKeyRevealController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The private temp store collection used to reveal secrets once.
   */
  protected const TEMPSTORE_COLLECTION = 'saibher_api_key';

  /**
   * Constructor.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected ApiKeyService $apiKeyService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
      $container->get('saibher_web_services.api_key_service'),
    );
  }

  /**
   * Renders the secret and deletes it from the temp store.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\saibher_web_services\Entity\SaibherApiKey $saibher_api_key
   *   The key entity.
   *
   * @return array
   *   A render array.
   */
  public function reveal(Request $request, SaibherApiKey $saibher_api_key): array {
    $store = $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
    $secret = $store->get($saibher_api_key->id());

    if ($secret === NULL || !is_string($secret)) {
      return [
        '#markup' => '<p>' . $this->t('No hay un secret pendiente para esta clave (ya fue mostrado o la clave no fue generada en esta sesión).')
          . ' <a href="' . $saibher_api_key->toUrl('collection')->toString() . '">' . $this->t('Volver a la lista de API keys') . '</a>.</p>',
      ];
    }

    $store->delete($saibher_api_key->id());

    $credential = ApiKeyService::buildCredential($saibher_api_key->id(), $secret);

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['saibher-ws-reveal']],
      'intro' => [
        '#markup' => '<h2>' . $this->t('Guarda esta credencial ahora') . '</h2>'
          . '<p>' . $this->t('Esta es la única vez que se mostrará el secret completo. Úsala en el header <code>Authorization: Saibher {valor}</code> de cada petición. Si la pierdes, rota la clave desde la lista.') . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#rows' => [
          ['key_label', $this->t('Etiqueta'), $saibher_api_key->label()],
          ['key_id', $this->t('Key ID'), $saibher_api_key->id()],
          ['credential', $this->t('Credencial completa (Authorization)'), ['data' => ['#markup' => '<code>' . htmlspecialchars($credential) . '</code>']]],
          ['hint', $this->t('Header HTTP'), ['data' => ['#markup' => '<code>Authorization: ' . htmlspecialchars(ApiKeyAuth::SCHEME . ' ' . $credential) . '</code>']]],
        ],
      ],
      'back' => [
        '#markup' => '<p><a class="button" href="' . $saibher_api_key->toUrl('collection')->toString() . '">' . $this->t('Volver a la lista de API keys') . '</a></p>',
      ],
    ];

    $build['#attached']['library'][] = 'saibher_web_services/admin';
    return $build;
  }

}