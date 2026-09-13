<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for content_tag_enable_for_all_entities.
 */
final readonly class ContentTagEnabledForAllEntitiesSuccess extends OperationSuccessBase {

  public function __construct(
    public int $termId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'term_id' => $this->termId,
    ]);
  }

}
