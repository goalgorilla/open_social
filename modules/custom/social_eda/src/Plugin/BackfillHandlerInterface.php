<?php

declare(strict_types=1);

namespace Drupal\social_eda\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for BackfillHandler plugins.
 */
interface BackfillHandlerInterface extends PluginInspectionInterface {

  /**
   * Get entity IDs to backfill.
   *
   * @param int|null $from
   *   Unix timestamp - entities created on or after this time.
   * @param int|null $to
   *   Unix timestamp - entities created on or before this time.
   *
   * @return array<int|string>
   *   Array of entity IDs (int|string).
   */
  public function getEntityIds(?int $from = NULL, ?int $to = NULL): array;

  /**
   * Process a single entity for backfill.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   */
  public function process(EntityInterface $entity): void;

}
