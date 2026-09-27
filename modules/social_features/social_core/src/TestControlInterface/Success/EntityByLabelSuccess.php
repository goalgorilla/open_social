<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for entity_by_label: the one entity with that label.
 */
final readonly class EntityByLabelSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param int $id
   *   The entity ID.
   * @param string $uuid
   *   The entity UUID.
   * @param string $label
   *   The entity's label as stored.
   */
  public function __construct(
    public int $id,
    public string $uuid,
    public string $label,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'id' => $this->id,
      'uuid' => $this->uuid,
      'label' => $this->label,
    ]);
  }

}
