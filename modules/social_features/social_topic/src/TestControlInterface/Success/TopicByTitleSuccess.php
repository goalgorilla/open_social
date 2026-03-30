<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for topic_by_title.
 */
final readonly class TopicByTitleSuccess extends OperationSuccessBase {

  public function __construct(
    public int $nid,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'nid' => $this->nid,
    ]);
  }

}
