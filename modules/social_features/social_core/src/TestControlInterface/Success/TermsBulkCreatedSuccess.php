<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for term_create_bulk.
 */
final readonly class TermsBulkCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $tids
   *   Created taxonomy term IDs.
   */
  public function __construct(
    public array $tids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'tids' => $this->tids,
    ]);
  }

}
