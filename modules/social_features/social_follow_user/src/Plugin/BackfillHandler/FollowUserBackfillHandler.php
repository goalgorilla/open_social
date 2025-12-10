<?php

declare(strict_types=1);

namespace Drupal\social_follow_user\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for follow user flagging entities.
 *
 * @BackfillHandler(
 *   id = "follow_user",
 *   label = @Translation("Follow User"),
 *   entity_type = "flagging",
 *   bundle = "follow_user",
 *   handler_service = "social_follow_user.eda_handler",
 *   handler_method = "followUserCreate"
 * )
 */
final class FollowUserBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Overrides parent to filter by flag_id since flagging entities don't use
   * bundles. The base class would skip bundle filtering for flagging entities,
   * but we need to filter by flag_id to only get follow_user flaggings.
   */
  public function getEntityIds(?int $from = NULL, ?int $to = NULL): array {
    $this->validatePluginDefinitionIsArray();
    assert(is_array($this->pluginDefinition), 'Plugin definition must be an array.');

    $entity_type = $this->pluginDefinition['entity_type'];
    $storage = $this->entityTypeManager->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);

    // Filter by flag_id since flagging entities don't use bundles.
    $query->condition('flag_id', $this->pluginDefinition['bundle']);

    // Apply date range filtering on 'created' field (flagging entities use
    // 'created').
    if ($from !== NULL || $to !== NULL) {
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
      if (!isset($field_storage_definitions['created'])) {
        throw new \RuntimeException(sprintf(
          'Entity type "%s" does not have a "created" field. Date range filtering requires entities with a "created" timestamp field.',
          $entity_type
        ));
      }

      if ($from !== NULL) {
        $query->condition('created', $from, '>=');
      }
      if ($to !== NULL) {
        $query->condition('created', $to, '<=');
      }
    }

    $result = $query->execute();
    if (!is_array($result)) {
      throw new \RuntimeException(sprintf(
        'Entity query execute() must return an array for entity type "%s", got %s.',
        $entity_type,
        gettype($result)
      ));
    }
    return $result;
  }

}
