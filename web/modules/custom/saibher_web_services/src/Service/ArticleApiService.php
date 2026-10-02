<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Service;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\File\FileUrlGeneratorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;

/**
 * Implements the read/write operations over the Article content type.
 *
 * This is the single gateway for external integrations: all payloads are
 * validated and normalized here before touching entity storage.
 */
class ArticleApiService {

  /**
   * The node bundle this API operates on.
   */
  public const BUNDLE = 'article';

  /**
   * Allowed remote image extensions.
   */
  public const IMAGE_EXTENSIONS = ['png', 'gif', 'jpg', 'jpeg'];

  /**
   * Default pagination limits.
   */
  public const DEFAULT_LIMIT = 10;
  public const MAX_LIMIT = 50;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelInterface $logger,
    protected FileRepositoryInterface $fileRepository,
    protected FileSystemInterface $fileSystem,
    protected FileValidatorInterface $fileValidator,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected ClientInterface $httpClient,
    protected TimeInterface $time,
    protected AliasManagerInterface $pathAliasManager,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Lists articles.
   *
   * @param array $params
   *   Query parameters: limit, offset, title, status, sort, dir.
   *
   * @return array{items: array, total: int}
   *   Normalized articles plus the total count of matching items.
   */
  public function list(array $params): array {
    $settings = $this->settings();

    $limit = isset($params['limit']) ? (int) $params['limit'] : self::DEFAULT_LIMIT;
    $limit = max(1, min(self::MAX_LIMIT, $limit));
    $offset = isset($params['offset']) ? max(0, (int) $params['offset']) : 0;

    $storage = $this->nodeStorage();
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('type', self::BUNDLE);

    if (isset($params['title']) && is_string($params['title']) && ($title = trim($params['title'])) !== '') {
      $query->condition('title', '%' . $title . '%', 'LIKE');
    }

    // Status filter. When omitted, the "only published" setting applies.
    if (isset($params['status'])) {
      $query->condition('status', (int) filter_var($params['status'], FILTER_VALIDATE_BOOLEAN));
    }
    elseif ($settings->get('only_published_in_lists')) {
      $query->condition('status', 1);
    }

    $countQuery = clone $query;
    $total = (int) $countQuery->count()->execute();

    $sortField = 'created';
    if (isset($params['sort']) && in_array($params['sort'], ['nid', 'title', 'created', 'changed', 'status'], TRUE)) {
      $sortField = $params['sort'];
    }
    $sortDirection = 'desc';
    if (isset($params['dir']) && in_array(strtolower((string) $params['dir']), ['asc', 'desc'], TRUE)) {
      $sortDirection = strtolower((string) $params['dir']);
    }
    elseif (in_array($sortField, ['title'], TRUE)) {
      $sortDirection = 'asc';
    }

    $query->sort($sortField, $sortDirection);
    $query->sort('nid', 'asc');
    $query->range($offset, $limit);

    $ids = array_values($query->execute());
    $nodes = $storage->loadMultiple($ids);

    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->normalize($node, FALSE);
    }

    return [
      'items' => $items,
      'total' => $total,
    ];
  }

  /**
   * Loads a single article by numeric ID or UUID.
   *
   * @param string $articleKey
   *   A numeric node ID or a UUID.
   *
   * @return \Drupal\node\NodeInterface
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get(string $articleKey): NodeInterface {
    $node = $this->loadArticle($articleKey);
    if (!$node instanceof NodeInterface) {
      throw new NotFoundHttpException('El artículo solicitado no existe.');
    }
    return $node;
  }

  /**
   * Creates a new article.
   *
   * @param array $payload
   *   The payload from the client.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The authenticated account (recorded as the author).
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  public function create(array $payload, AccountInterface $account): NodeInterface {
    $node = Node::create([
      'type' => self::BUNDLE,
      'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
    ]);

    // Author, dates and revision metadata are never taken from the client.
    $node->setOwnerId($account->id());
    $node->setRevisionUserId($account->id());

    if (!$node->access('create', $account)) {
      throw new AccessDeniedHttpException('La cuenta autenticada no tiene permiso para crear artículos.');
    }

    $this->applyFields($node, $payload, FALSE);
    $node->save();
    $this->registerImageUsage($node);

    $this->logger->info('Article @nid created via API by user @uid.', [
      '@nid' => $node->id(),
      '@uid' => $account->id(),
    ]);

    return $node;
  }

  /**
   * Updates an existing article (partial update).
   *
   * @param string $articleKey
   *   Numeric ID or UUID.
   * @param array $payload
   *   Only the provided keys are updated.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The authenticated account.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  public function update(string $articleKey, array $payload, AccountInterface $account): NodeInterface {
    $node = $this->get($articleKey);

    if (!$node->access('update', $account)) {
      throw new AccessDeniedHttpException('La cuenta autenticada no tiene permiso para actualizar este artículo.');
    }

    $this->applyFields($node, $payload, TRUE);

    // Editing via API always creates a new revision with a clear log entry.
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Actualizado a través de la API de Saibher Web Services.');
    $node->setRevisionUserId($account->id());

    $node->save();
    $this->registerImageUsage($node);

    $this->logger->info('Article @nid updated via API by user @uid.', [
      '@nid' => $node->id(),
      '@uid' => $account->id(),
    ]);

    return $node;
  }

  /**
   * Applies validated payload fields onto a node.
   *
   * Only a fixed set of writable keys is accepted. Unknown keys are logged
   * and ignored; autogenerated fields (uid, created, uuid, revisions) are
   * never accepted from the client.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being built/updated.
   * @param array $payload
   *   The client payload.
   * @param bool $partial
   *   Whether missing keys should be left untouched (PATCH semantics).
   *
   * @throws \Drupal\saibher_web_services\Service\ApiValidationException
   */
  protected function applyFields(NodeInterface $node, array $payload, bool $partial): void {
    $errors = [];
    $safeKeys = ['title', 'body', 'field_category', 'field_tags', 'image', 'status'];

    // Accept friendly JSON names and map them to the field machine names.
    $aliases = [
      'category' => 'field_category',
      'tags' => 'field_tags',
    ];
    foreach ($aliases as $external => $machine) {
      if (array_key_exists($external, $payload)) {
        if (array_key_exists($machine, $payload) && $payload[$machine] !== $payload[$external]) {
          $errors[$external] = sprintf('No mezcles "%s" y "%s": usa solo uno de los dos.', $external, $machine);
        }
        else {
          $payload[$machine] = $payload[$external];
        }
        unset($payload[$external]);
      }
    }

    foreach (array_keys($payload) as $key) {
      if (!in_array($key, $safeKeys, TRUE)) {
        $this->logger->warning('Ignored unknown field "%field" in article payload.', ['%field' => (string) $key]);
      }
    }

    // Title.
    if (array_key_exists('title', $payload)) {
      if (!is_string($payload['title']) || ($title = $this->cleanString($payload['title'])) === '') {
        $errors['title'] = 'El campo "title" es obligatorio y no puede quedar vacío.';
      }
      elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'El campo "title" no puede superar los 255 caracteres.';
      }
      else {
        $node->setTitle($title);
      }
    }
    elseif (!$partial) {
      $errors['title'] = 'El campo "title" es obligatorio.';
    }

    // Body.
    if (array_key_exists('body', $payload)) {
      $body = $payload['body'];

      if (is_string($body)) {
        $node->body->setValue($this->setBody($node, $this->cleanString($body), NULL, NULL));
      }
      elseif (is_array($body)) {
        $format = $body['format'] ?? NULL;
        if ($format !== NULL && (!is_string($format) || !$this->isAllowedFormat($format))) {
          $errors['body.format'] = sprintf('El formato de texto "%s" no está permitido para la API.', (string) $format);
        }
        else {
          $value = isset($body['value']) ? (string) $body['value'] : '';
          $summary = isset($body['summary']) ? (string) $body['summary'] : NULL;
          $node->body->setValue($this->setBody($node, $this->cleanString($value), $summary !== NULL ? $this->cleanString($summary) : NULL, $format));
        }
      }
      else {
        $errors['body'] = 'El campo "body" debe ser un texto o un objeto {value, summary, format}.';
      }
    }

    // Categories (existing terms only).
    if (array_key_exists('field_category', $payload)) {
      $tids = $this->resolveTerms('field_category', $payload['field_category'], 'article_category', FALSE, $errors);
      $node->set('field_category', array_map(static fn(int $tid): array => ['target_id' => $tid], $tids));
    }

    // Tags (auto-create enabled).
    if (array_key_exists('field_tags', $payload)) {
      $tids = $this->resolveTerms('field_tags', $payload['field_tags'], 'tags', TRUE, $errors);
      $node->set('field_tags', array_map(static fn(int $tid): array => ['target_id' => $tid], $tids));
    }

    // Image (optional remote image).
    if (array_key_exists('image', $payload)) {
      if ($payload['image'] === NULL) {
        $node->field_image = NULL;
      }
      else {
        $alt = is_array($payload['image']) && isset($payload['image']['alt'])
          ? $this->cleanString((string) $payload['image']['alt'])
          : '';
        $fid = $this->resolveImage($payload['image'], $alt, $errors);
        if ($fid !== NULL) {
          $node->field_image->setValue(['target_id' => $fid, 'alt' => $alt]);
        }
      }
    }

    // Status (publishing).
    if (array_key_exists('status', $payload)) {
      if (!is_bool($payload['status']) && !in_array($payload['status'], [0, 1, '0', '1'], TRUE)) {
        $errors['status'] = 'El campo "status" debe ser un booleano.';
      }
      else {
        $node->setPublished((bool) $payload['status']);
      }
    }
    elseif (!$partial) {
      $node->setPublished((bool) $this->settings()->get('article_status_default'));
    }

    if ($errors !== []) {
      throw new ApiValidationException($errors);
    }
  }

  /**
   * Builds the value array for the body field.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $value
   *   Sanitized body value.
   * @param string|null $summary
   *   Sanitized summary, or NULL.
   * @param string|null $format
   *   Allowed format id, or NULL to use the default.
   *
   * @return array{value: string, summary: ?string, format: string}
   *   The field value.
   */
  protected function setBody(NodeInterface $node, string $value, ?string $summary, ?string $format): array {
    return $node->body->isEmpty() ? [
      'value' => $value,
      'summary' => $summary,
      'format' => $format ?? $this->defaultFormat(),
    ] : ['value' => $value, 'summary' => $summary !== NULL ? $summary : ($node->body->summary ?? NULL), 'format' => $format ?? ($node->body->format ?? $this->defaultFormat())];
  }

  /**
   * Resolves taxonomy term IDs from an array of {id|name} entries.
   *
   * @param string $fieldName
   *   Field name for error reporting.
   * @param mixed $entries
   *   Client-supplied value.
   * @param string $vid
   *   Vocabulary to resolve against.
   * @param bool $autoCreate
   *   Whether unknown term names may be created (tags).
   * @param array $errors
   *   Accumulated validation errors (passed by reference).
   *
   * @return int[]
   *   Resolved term IDs.
   */
  protected function resolveTerms(string $fieldName, mixed $entries, string $vid, bool $autoCreate, array &$errors): array {
    if (!is_array($entries)) {
      $errors[$fieldName] = sprintf('El campo "%s" debe ser un arreglo de términos.', $fieldName);
      return [];
    }

    $storage = $this->terminStorage();
    $result = [];

    foreach ($entries as $index => $entry) {
      $label = sprintf('%s[%d]', $fieldName, $index);
      $id = NULL;
      $name = NULL;

      if (is_int($entry) || (is_string($entry) && ctype_digit((string) $entry))) {
        $id = (int) $entry;
      }
      elseif (is_string($entry)) {
        $name = $this->cleanString($entry);
      }
      elseif (is_array($entry)) {
        if (isset($entry['id'])) {
          $id = (int) $entry['id'];
        }
        elseif (isset($entry['name'])) {
          $name = $this->cleanString((string) $entry['name']);
        }
        else {
          $errors[$label] = 'Cada término debe contener un "id" o un "name".';
          continue;
        }
      }
      else {
        $errors[$label] = 'Formato de término no válido.';
        continue;
      }

      if ($id !== NULL) {
        $term = $storage->load($id);
        if (!$term instanceof Term || $term->bundle() !== $vid) {
          $errors[$label] = sprintf('El término con id %d no existe en el vocabulario "%s".', $id, $vid);
          continue;
        }
        $result[] = (int) $term->id();
        continue;
      }

      if ($name === '') {
        $errors[$label] = 'El nombre del término no puede quedar vacío.';
        continue;
      }

      $term = $this->loadTermByName($vid, $name);
      if ($term instanceof Term) {
        $result[] = (int) $term->id();
        continue;
      }

      if ($autoCreate && mb_strlen($name) <= 255) {
        $term = Term::create([
          'vid' => $vid,
          'name' => $name,
          'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
        ]);
        $term->save();
        $result[] = (int) $term->id();
      }
      else {
        $errors[$label] = sprintf('El término "%s" no existe en el vocabulario "%s".', $name, $vid);
      }
    }

    return $result;
  }

  /**
   * Resolves an optional remote image into a managed file ID.
   *
   * @param mixed $value
   *   The client-supplied image value.
   * @param string $alt
   *   The sanitized alt text (empty when not supplied).
   * @param array $errors
   *   Accumulated validation errors (passed by reference).
   *
   * @return int|null
   *   The managed file ID, or NULL when invalid.
   */
  protected function resolveImage(mixed $value, string $alt, array &$errors): ?int {
    if (!is_array($value) || empty($value['url']) || !is_string($value['url'])) {
      $errors['image'] = 'El campo "image" debe ser un objeto {url, alt}.';
      return NULL;
    }

    $url = trim($value['url']);
    $parts = parse_url($url);
    if ($parts === FALSE || !isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], TRUE) || !isset($parts['host'])) {
      $errors['image'] = 'La URL de la imagen no es válida (solo se aceptan http/https).';
      return NULL;
    }

    $maxBytes = (int) $this->settings()->get('image_max_bytes');

    try {
      $response = $this->httpClient->get($url, [
        'timeout' => 15,
        'allow_redirects' => ['max' => 3],
        'verify' => TRUE,
      ]);
      $data = (string) $response->getBody();

      $contentLength = $response->getHeader('Content-Length');
      $size = $contentLength !== [] ? (int) $contentLength[0] : strlen($data);
      if ($maxBytes > 0 && $size > $maxBytes) {
        $errors['image'] = sprintf('La imagen supera el tamaño máximo permitido (%d bytes).', $maxBytes);
        return NULL;
      }

      if (strlen($data) === 0) {
        $errors['image'] = 'No se pudo descargar la imagen (respuesta vacía).';
        return NULL;
      }
    }
    catch (GuzzleException | \Exception $e) {
      $errors['image'] = 'No se pudo descargar la imagen desde la URL indicada.';
      $this->logger->warning('Image download failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }

    // Determine a safe extension from the real content, not the URL.
    $extension = $this->detectImageExtension($data);
    if ($extension === NULL) {
      $errors['image'] = 'El contenido descargado no es una imagen PNG, GIF, JPG o JPEG válida.';
      return NULL;
    }

    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $base = preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo(explode('?', $path)[0], PATHINFO_FILENAME)) ?: 'image';
    $filename = substr($base, 0, 180) . '.' . $extension;
    $directory = 'public://' . gmdate('Y-m', $this->time->getRequestTime());
    $destination = $directory . '/' . $filename;

    try {
      // FileRepository::writeData() no crea el directorio de destino: su
      // prepareDestination() llama a prepareDirectory() sin CREATE_DIRECTORY y
      // falla si la carpeta no existe. Sin esto, la primera imagen de cada mes
      // (public://AAAA-MM) devuelve 422.
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      $file = $this->fileRepository->writeData($data, $destination, FileExists::Replace);
    }
    catch (\Exception $e) {
      $errors['image'] = 'No se pudo guardar la imagen descargada.';
      $this->logger->error('Failed to save remote image from @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    // Final defense: validate the managed file (extension + real size) and
    // that its content is actually an image.
    $violations = $this->fileValidator->validate($file, [
      'FileExtension' => ['extensions' => implode(' ', self::IMAGE_EXTENSIONS)],
      'FileSizeLimit' => ['fileLimit' => $maxBytes],
      'FileIsImage' => [],
    ]);
    if ($violations->count() > 0) {
      $file->delete();
      $errors['image'] = 'La imagen descargada no superó la validación de seguridad.';
      return NULL;
    }

    return (int) $file->id();
  }

  /**
   * Detects the image extension from the actual content.
   *
   * @param string $data
   *   Binary content.
   *
   * @return string|null
   *   One of png/gif/jpg, or NULL when not an accepted image.
   */
  protected function detectImageExtension(string $data): ?string {
    $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->buffer($data);
    $map = [
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/jpeg' => 'jpg',
    ];
    return $map[$mime] ?? NULL;
  }

  /**
   * Returns whether a text format is allowed for API writes.
   */
  protected function isAllowedFormat(string $format): bool {
    if (!in_array($format, $this->settings()->get('allowed_formats') ?? [], TRUE)) {
      return FALSE;
    }
    $storage = $this->entityTypeManager->getStorage('filter_format');
    return $storage->load($format) !== NULL;
  }

  /**
   * Default format used when the client does not specify one.
   */
  protected function defaultFormat(): string {
    $allowed = $this->settings()->get('allowed_formats') ?? ['basic_html'];
    return $allowed[0] ?? 'basic_html';
  }

  /**
   * Loads a taxonomy term by name within a vocabulary.
   */
  protected function loadTermByName(string $vid, string $name): ?Term {
    $ids = $this->terminStorage()->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', $vid)
      ->condition('name', $name)
      ->range(0, 1)
      ->execute();
    if ($ids === []) {
      return NULL;
    }
    $term = $this->terminStorage()->load(reset($ids));
    return $term instanceof Term ? $term : NULL;
  }

  /**
   * Resolves a numeric node ID or UUID to a node.
   */
  protected function loadArticle(string $articleKey): ?NodeInterface {
    $storage = $this->nodeStorage();
    if (ctype_digit($articleKey)) {
      $node = $storage->load((int) $articleKey);
    }
    else {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', self::BUNDLE)
        ->condition('uuid', $articleKey)
        ->range(0, 1)
        ->execute();
      $node = $ids !== [] ? $storage->load(reset($ids)) : NULL;
    }
    if ($node instanceof NodeInterface && $node->bundle() === self::BUNDLE) {
      return $node;
    }
    return NULL;
  }

  /**
   * Normalizes a node into the public API representation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param bool $includeBody
   *   Whether to include the full body (used by item responses).
   *
   * @return array
   */
  public function normalize(NodeInterface $node, bool $includeBody = TRUE): array {
    $data = [
      'id' => (int) $node->id(),
      'uuid' => $node->uuid(),
      'type' => $node->bundle(),
      'title' => $node->label(),
      'status' => (bool) $node->isPublished(),
      'created' => (int) $node->getCreatedTime(),
      'changed' => (int) $node->getChangedTime(),
      'author' => $this->normalizeAuthor($node),
      'url' => $this->canonicalUrl($node),
      'alias' => $this->pathAliasManager->getAliasByPath('/node/' . $node->id()) ?: NULL,
      'category' => $this->normalizeTerms($node, 'field_category'),
      'tags' => $this->normalizeTerms($node, 'field_tags'),
      'image' => $this->normalizeImage($node),
    ];

    if ($includeBody) {
      $data['body'] = [
        'value' => $node->body->value ?? NULL,
        'summary' => $node->body->summary ?? NULL,
        'format' => $node->body->format ?? NULL,
      ];
    }
    else {
      $data['summary'] = $this->buildSummary($node);
    }

    return $data;
  }

  /**
   * Builds a short plain-text summary for list responses.
   */
  protected function buildSummary(NodeInterface $node): ?string {
    if (!$node->hasField('body') || $node->body->isEmpty()) {
      return NULL;
    }
    $summary = trim((string) $node->body->summary);
    if ($summary !== '') {
      return $summary;
    }
    $text = trim(strip_tags((string) $node->body->value));
    return mb_strlen($text) > 255 ? mb_substr($text, 0, 252) . '...' : $text;
  }

  /**
   * Normalizes taxonomy references.
   */
  protected function normalizeTerms(NodeInterface $node, string $field): array {
    if (!$node->hasField($field)) {
      return [];
    }
    $items = [];
    foreach ($node->get($field) as $item) {
      if ($item->isEmpty() || $item->entity === NULL) {
        continue;
      }
      $items[] = [
        'id' => (int) $item->entity->id(),
        'name' => $item->entity->label(),
      ];
    }
    return $items;
  }

  /**
   * Normalizes the image reference.
   */
  protected function normalizeImage(NodeInterface $node): ?array {
    if (!$node->hasField('field_image') || $node->field_image->isEmpty()) {
      return NULL;
    }
    $item = $node->field_image->first();
    if ($item === NULL || $item->entity === NULL) {
      return NULL;
    }
    return [
      'fid' => (int) $item->entity->id(),
      'url' => $this->fileUrlGenerator->generateAbsoluteString($item->entity->getFileUri()),
      'alt' => $item->get('alt')->getValue() ?: NULL,
    ];
  }

  /**
   * Normalizes the author reference.
   */
  protected function normalizeAuthor(NodeInterface $node): array {
    $owner = $node->getOwner();
    return [
      'id' => (int) $node->getOwnerId(),
      'name' => $owner !== NULL ? $owner->getDisplayName() : t('Anonymous'),
    ];
  }

  /**
   * Returns the absolute canonical URL of a node.
   */
  protected function canonicalUrl(NodeInterface $node): string {
    return Url::fromRoute('entity.node.canonical', ['node' => $node->id()])
      ->setAbsolute()
      ->toString();
  }

  /**
   * Cleans a user-supplied string.
   */
  protected function cleanString(string $value): string {
    return trim(str_replace("\0", '', $value));
  }

  /**
   * Registers file usage so managed images are not garbage-collected.
   */
  protected function registerImageUsage(NodeInterface $node): void {
    if (!$node->hasField('field_image') || $node->field_image->isEmpty()) {
      return;
    }
    $file = $node->field_image->entity;
    if ($file instanceof FileInterface) {
      \Drupal::service('file.usage')->add($file, 'node', 'node', $node->id());
    }
  }

  /**
   * Returns the node storage.
   */
  protected function nodeStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('node');
  }

  /**
   * Returns the taxonomy term storage.
   */
  protected function terminStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Returns the module settings config object.
   */
  protected function settings(): Config {
    return $this->configFactory->get('saibher_web_services.settings');
  }

}