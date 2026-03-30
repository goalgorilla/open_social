<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for topics_create_with_author.
 */
final readonly class TopicsWithNonAnonymousAuthorCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $nids
   *   Created topic node IDs.
   * @param string $author
   *   The username of the created author.
   */
  public function __construct(
    public array $nids,
    public string $author,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'nids' => $this->nids,
      'author' => $this->author,
    ]);
  }

}
