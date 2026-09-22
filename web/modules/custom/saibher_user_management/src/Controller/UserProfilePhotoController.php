<?php

namespace Drupal\saibher_user_management\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the AJAX profile photo upload.
 *
 * The request is expected to POST a single file field named "file". On
 * success the file is attached to the current user's user_picture field and
 * the JSON response contains the new avatar URL ready to be swapped in.
 */
class UserProfilePhotoController extends ControllerBase {

  /**
   * Allowed file extensions for profile photos.
   */
  private const ALLOWED_EXTENSIONS = 'png gif jpg jpeg webp';

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * The file validator.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected FileValidatorInterface $fileValidator;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Constructs the photo upload controller.
   *
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   * @param \Drupal\file\Validation\FileValidatorInterface $file_validator
   *   The file validator.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(
    FileRepositoryInterface $file_repository,
    FileValidatorInterface $file_validator,
    FileSystemInterface $file_system
  ) {
    $this->fileRepository = $file_repository;
    $this->fileValidator = $file_validator;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.repository'),
      $container->get('file.validator'),
      $container->get('file_system')
    );
  }

  /**
   * Uploads and attaches a profile photo to the current user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON with the new avatar URL, or an error payload.
   */
  public function upload(Request $request): CacheableJsonResponse {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager()
      ->getStorage('user')
      ->load($this->currentUser()->id());

    if (!$account instanceof UserInterface) {
      return $this->error('No se pudo cargar tu cuenta.', 404);
    }

    $uploaded = $request->files->get('file');
    if (!$uploaded || !$uploaded->isValid()) {
      return $this->error('No se recibió ninguna imagen válida.', 422);
    }

    $extension = strtolower($uploaded->getClientOriginalExtension());
    if (!in_array($extension, explode(' ', self::ALLOWED_EXTENSIONS), TRUE)) {
      return $this->error(
        'Formato no permitido. Usa JPG, PNG, GIF o WEBP.',
        422
      );
    }

    $directory = 'public://pictures/avatars/' . date('Y-m');
    if (!$this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY
    )) {
      return $this->error('No se pudo preparar el directorio de imágenes.', 500);
    }

    $destination = $directory . '/' . $this->currentUser()->id() . '-'
      . random_int(1000, 9999) . '-' . time() . '.' . $extension;

    try {
      $file = $this->fileRepository->writeData(
        $uploaded->getContent(),
        $destination,
        FileExists::Rename
      );
    }
    catch (\Exception $e) {
      return $this->error('No se pudo guardar el archivo.', 500);
    }

    $violations = $this->fileValidator->validate($file, [
      'FileExtension' => ['extensions' => self::ALLOWED_EXTENSIONS],
      'FileIsImage' => [],
    ]);

    if (count($violations) > 0) {
      $file->delete();
      return $this->error('El archivo no es una imagen válida.', 422);
    }

    $file->setPermanent();
    $file->save();

    // Replace the previous avatar for this field value if one exists.
    $previous = $account->get('user_picture')->target_id;

    $account->set('user_picture', ['target_id' => $file->id()]);
    $account->save();

    if ($previous && $previous != $file->id()) {
      $this->deleteOrphanedFile($previous, $account);
    }

    return new CacheableJsonResponse([
      'status' => 'ok',
      'file_id' => (int) $file->id(),
      'url' => $this->avatarUrl($file->id()),
      'original' => $file->createFileUrl(),
    ]);
  }

  /**
   * Builds a small JSON error response.
   *
   * @param string $message
   *   The error message.
   * @param int $code
   *   The HTTP status code.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The JSON error response.
   */
  protected function error(string $message, int $code): CacheableJsonResponse {
    return new CacheableJsonResponse([
      'status' => 'error',
      'message' => $message,
    ], $code);
  }

  /**
   * Gets the thumbnail URL of a file, if an image style is available.
   *
   * @param int $fid
   *   The file id.
   *
   * @return string
   *   The generated URL.
   */
  protected function avatarUrl(int $fid): string {
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager()->getStorage('file')->load($fid);
    if (!$file) {
      return '';
    }

    $styles = $this->entityTypeManager()->getStorage('image_style')->loadMultiple();
    $style = $styles['thumbnail'] ?? reset($styles);

    if ($style) {
      try {
        /** @var \Drupal\image\Entity\ImageStyle $style */
        return $style->buildUrl($file->getFileUri());
      }
      catch (\Exception $e) {
        // Fall through to the original file URL.
      }
    }

    return $file->createFileUrl(FALSE);
  }

  /**
   * Deletes a file that stopped being used by the user's picture field.
   *
   * The deletion is best-effort and never cascades from a failure.
   *
   * @param int $fid
   *   The file id to delete.
   * @param \Drupal\user\UserInterface $account
   *   The user that used to reference the file.
   */
  protected function deleteOrphanedFile(int $fid, UserInterface $account): void {
    try {
      $file = $this->entityTypeManager()->getStorage('file')->load($fid);
      if ($file) {
        $usage = \Drupal::service('file.usage');
        $usage->add($file, 'user', 'user', $account->id());
        $file->delete();
      }
    }
    catch (\Exception $e) {
      // Never break the response because of a cleanup failure.
    }
  }

}