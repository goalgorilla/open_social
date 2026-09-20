<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for the affiliation-state assertion operations.
 */
final readonly class AffiliationStateMatchedSuccess extends OperationSuccessBase {

  /**
   * Constructs an AffiliationStateMatchedSuccess.
   *
   * @param list<string> $groupLabels
   *   The matched group labels, in order.
   */
  public function __construct(
    public array $groupLabels,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'group_labels' => $this->groupLabels,
    ]);
  }

}
