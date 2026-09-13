<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for inform_block_create_bulk.
 */
final readonly class InformBlocksCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $ids
   *   Created inform block IDs.
   */
  public function __construct(
    public array $ids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'ids' => $this->ids,
    ]);
  }

}
