<?php

declare(strict_types=1);

namespace Drupal\saibher_web_services\Service;

/**
 * Thrown when an API payload fails input validation.
 *
 * Carries per-field error messages so the controller can return a detailed
 * 422 response.
 */
class ApiValidationException extends \Exception {

  /**
   * Constructor.
   *
   * @param array $errors
   *   Field => message map of validation errors.
   * @param string $message
   *   The generic error message.
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous exception, if any.
   */
  public function __construct(
    protected array $errors,
    string $message = 'The submitted payload did not pass validation.',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns the per-field validation errors.
   *
   * @return array
   *   Field => message map.
   */
  public function getErrors(): array {
    return $this->errors;
  }

}