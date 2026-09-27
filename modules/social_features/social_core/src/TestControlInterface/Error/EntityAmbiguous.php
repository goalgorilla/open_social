<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when more than one entity of the bundle has the given label.
 */
final readonly class EntityAmbiguous extends OperationErrorBase {

  /**
   * Create a new operation error instance.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $label
   *   The label that matched.
   * @param list<int> $ids
   *   The IDs of the matching entities.
   */
  public function __construct(string $entityTypeId, string $bundle, string $label, array $ids) {
    parent::__construct(sprintf(
      "%d entities of type %s and bundle '%s' have the label '%s' (IDs %s); a lookup by label needs exactly one.",
      count($ids),
      $entityTypeId,
      $bundle,
      $label,
      implode(', ', $ids),
    ));
  }

}
