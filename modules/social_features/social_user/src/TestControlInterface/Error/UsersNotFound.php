<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when one or more requested names have no matching user.
 */
final readonly class UsersNotFound extends OperationErrorBase {

  /**
   * Constructs a UsersNotFound error.
   *
   * @param list<string> $names
   *   The names that had no matching user.
   */
  public function __construct(array $names) {
    parent::__construct(sprintf(
      count($names) === 1 ? "The user '%s' was not found." : "The users '%s' were not found.",
      implode("', '", $names),
    ));
  }

}
