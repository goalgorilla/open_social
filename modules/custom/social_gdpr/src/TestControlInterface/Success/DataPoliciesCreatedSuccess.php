<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for data_policy_create_bulk.
 */
final readonly class DataPoliciesCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $ids
   *   Created data policy revision IDs.
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
