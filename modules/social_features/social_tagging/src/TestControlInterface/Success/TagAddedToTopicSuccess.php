<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for topic_tag_add.
 */
final readonly class TagAddedToTopicSuccess extends OperationSuccessBase {

  public function __construct(
    public int $nid,
    public int $termId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'nid' => $this->nid,
      'term_id' => $this->termId,
    ]);
  }

}
