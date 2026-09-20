<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when a user's affiliation state doesn't match expectations.
 */
final readonly class AffiliationStateMismatch extends OperationErrorBase {

  /**
   * Constructs an AffiliationStateMismatch.
   *
   * @param list<string> $expected
   *   Expected group labels, in order.
   * @param list<string> $actual
   *   Actual group labels, in order.
   */
  public function __construct(array $expected, array $actual) {
    parent::__construct(sprintf(
      'Expected affiliations (exact match): [%s], but got: [%s].',
      implode(', ', $expected),
      implode(', ', $actual),
    ));
  }

}
