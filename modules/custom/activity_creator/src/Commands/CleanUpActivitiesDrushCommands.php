<?php

namespace Drupal\activity_creator\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * A Drush command file.
 *
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a
 * drush.services.yml in root of your module like this module does.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class CleanUpActivitiesDrushCommands extends DrushCommands {

  use StringTranslationTrait;

  const ACTIVITIES_TARGET_TYPE = [
    ['comment', 'cid'],
    ['event_enrollment', 'id'],
    ['groups', 'id'],
    ['mentions', 'mid'],
    ['node', 'nid'],
    ['post', 'id'],
    ['message', 'mid'],
    ['profile', 'profile_id'],
    ['queue_storage_entity', 'id'],
    ['votingapi_vote', 'id'],
  ];

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;


  /**
   * @var \Psr\Log\LoggerInterface
   *
   * The logger channel factory.
   */
  protected ?LoggerInterface $logger;

  /**
   * CleanUpActivitiesDrushCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   The logger channel factory.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactory $loggerChannelFactory,
  ) {
    parent::__construct();
    $this->connection = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('activity_creator');
    assert($this->logger !== NULL, "Our application is misconfigured and Drush has no loggers"); // This only runs in dev/CI.
  }

  /**
   * Command to find and delete orphaned activity entities.
   *
   * @command activity_creator:cleanup-activities
   * @aliases cleanup-activities
   * @description Deletes activity entities that have no attached entities anymore.
   */
  public function cleanupActivities(): void {
    if (!$this->entityTypeManager->hasDefinition('activity')) {
      return;
    }
    $activity_storage = $this->entityTypeManager->getStorage('activity');

    foreach (self::ACTIVITIES_TARGET_TYPE as [$target_type, $column_id]) {
      if ($this->tableExist($target_type) === FALSE) {
        $this->logger()->debug($this->t('Table :table does not exist in the database', [
          ':table' => $target_type,
        ]));
        continue;
      }

      try {
        $activity_ids = $this->getOrphanedActivityIds($target_type, $column_id);
        foreach ($activity_ids as $activity_id) {
          $activity_storage->delete([$activity_storage->loadUnchanged($activity_id)]);
        }

        $this->logger()->debug($this->t(':count orphaned activities with target_type ":type" have been removed', [
          ':count' => count($activity_ids),
          ':type' => $target_type,
        ]));

      }
      catch (EntityStorageException | \Exception $e) {
        $this->logger()->error($e->getMessage());
      }
    }
  }

  /**
   * Return if database table exists.
   *
   * @param string $table
   *   The table name.
   *
   * @return bool
   *   Return if database table exists.
   */
  private function tableExist(string $table):bool {
    return $this->connection->schema()
      ->tableExists($table);
  }

  /**
   * Get orphaned activity ids.
   *
   * @param string $target_type
   *   The database table of the orphan.
   * @param string $column_id
   *   The column-id of the orphan table.
   *
   * @returns {array}
   *   The list of activity ids.
   */
  private function getOrphanedActivityIds(string $target_type, string $column_id): array {
    $query = $this->connection->select('activity__field_activity_entity', 'fae');
    $query->fields('fae', ['entity_id']);
    $query->leftJoin($target_type, 'target_entity', "fae.field_activity_entity_target_id = target_entity.{$column_id}");
    $query->condition('fae.field_activity_entity_target_type', $target_type);
    $query->isNull("target_entity.{$column_id}");

    try {
      $statement = $query->execute();
      if ($statement instanceof StatementInterface) {
        return $statement->fetchCol();
      }
      return [];
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return [];
    }
  }

}
