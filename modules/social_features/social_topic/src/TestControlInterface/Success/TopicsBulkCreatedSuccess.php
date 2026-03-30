<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for topics_create_bulk.
 */
final readonly class TopicsBulkCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $nids
   *   Created topic node IDs.
   */
  public function __construct(
    public array $nids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'nids' => $this->nids,
    ]);
  }

}
