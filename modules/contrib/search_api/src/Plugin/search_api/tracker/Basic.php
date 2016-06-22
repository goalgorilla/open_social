<?php

namespace Drupal\search_api\Plugin\search_api\tracker;

use Drupal\Core\Database\Connection;
use Drupal\search_api\Tracker\TrackerPluginBase;
use Drupal\search_api\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
 *
 * @SearchApiTracker(
 *   id = "default",
 *   label = @Translation("Default"),
 *   description = @Translation("Index tracker which uses first in/first out for processing pending items.")
 * )
 */
class Basic extends TrackerPluginBase {

  /**
   * Status value that represents items which are indexed in their latest form.
   */
  const STATUS_INDEXED = 0;

  /**
   * Status value that represents items which still need to be indexed.
   */
  const STATUS_NOT_INDEXED = 1;

  /**
   * The database connection used by this plugin.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $tracker */
    $tracker = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $container->get('database');
    $tracker->setDatabaseConnection($connection);

    return $tracker;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection used by this plugin.
   */
  public function getDatabaseConnection() {
    return $this->connection ?: \Drupal::database();
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   *
   * @return $this
   */
  public function setDatabaseConnection(Connection $connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * Creates a SELECT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createSelectStatement() {
    return $this->getDatabaseConnection()->select('search_api_item', 'sai')
      ->condition('index_id', $this->getIndex()->id());
  }

  /**
   * Creates an INSERT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   An INSERT statement.
   */
  protected function createInsertStatement() {
    return $this->getDatabaseConnection()->insert('search_api_item')
      ->fields(array('index_id', 'datasource', 'item_id', 'changed', 'status'));
  }

  /**
   * Creates an UPDATE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   An UPDATE statement.
   */
  protected function createUpdateStatement() {
    return $this->getDatabaseConnection()->update('search_api_item')
      ->condition('index_id', $this->getIndex()->id());
  }

  /**
   * Creates a DELETE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Delete
   *   A DELETE Statement.
   */
  protected function createDeleteStatement() {
    return $this->getDatabaseConnection()->delete('search_api_item')
      ->condition('index_id', $this->getIndex()->id());
  }

  /**
   * Creates a SELECT statement which filters on the not indexed items.
   *
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   retrieved.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createRemainingItemsStatement($datasource_id = NULL) {
    $select = $this->createSelectStatement();
    $select->fields('sai', array('item_id'));
    if ($datasource_id) {
      $select->condition('datasource', $datasource_id);
    }
    $select->condition('sai.status', $this::STATUS_NOT_INDEXED, '=');
    $select->orderBy('sai.changed', 'ASC');
    // Add a secondary sort on item ID to make the order completely predictable.
    $select->orderBy('sai.item_id', 'ASC');

    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted(array $ids) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $index_id = $this->getIndex()->id();
      // Process the IDs in chunks so we don't create an overly large INSERT
      // statement.
      foreach (array_chunk($ids, 1000) as $ids_chunk) {
        $insert = $this->createInsertStatement();
        foreach ($ids_chunk as $item_id) {
          list($datasource_id) = Utility::splitCombinedId($item_id);
          $insert->values(array(
            'index_id' => $index_id,
            'datasource' => $datasource_id,
            'item_id' => $item_id,
            'changed' => REQUEST_TIME,
            'status' => $this::STATUS_NOT_INDEXED,
          ));
        }
        $insert->execute();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated(array $ids = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : array(NULL));
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields(array('changed' => REQUEST_TIME, 'status' => $this::STATUS_NOT_INDEXED));
        if ($ids_chunk) {
          $update->condition('item_id', $ids_chunk, 'IN');
        }
        $update->execute();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsUpdated($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $update = $this->createUpdateStatement();
      $update->fields(array('changed' => REQUEST_TIME, 'status' => $this::STATUS_NOT_INDEXED));
      if ($datasource_id) {
        $update->condition('datasource', $datasource_id);
      }
      $update->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsIndexed(array $ids) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = array_chunk($ids, 1000);
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields(array('status' => $this::STATUS_INDEXED));
        $update->condition('item_id', $ids_chunk, 'IN');
        $update->execute();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted(array $ids = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large DELETE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : array(NULL));
      foreach ($ids_chunks as $ids_chunk) {
        $delete = $this->createDeleteStatement();
        if ($ids_chunk) {
          $delete->condition('item_id', $ids_chunk, 'IN');
        }
        $delete->execute();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsDeleted($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $delete = $this->createDeleteStatement();
      if ($datasource_id) {
        $delete->condition('datasource', $datasource_id);
      }
      $delete->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('search_api', $e);
      $transaction->rollback();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItems($limit = -1, $datasource_id = NULL) {
    $select = $this->createRemainingItemsStatement($datasource_id);
    if ($limit >= 0) {
      $select->range(0, $limit);
    }
    return $select->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalItemsCount($datasource_id = NULL) {
    $select = $this->createSelectStatement();
    if ($datasource_id) {
      $select->condition('datasource', $datasource_id);
    }
    return (int) $select->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexedItemsCount($datasource_id = NULL) {
    $select = $this->createSelectStatement();
    $select->condition('sai.status', $this::STATUS_INDEXED);
    if ($datasource_id) {
      $select->condition('datasource', $datasource_id);
    }
    return (int) $select->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItemsCount($datasource_id = NULL) {
    $select = $this->createRemainingItemsStatement();
    if ($datasource_id) {
      $select->condition('datasource', $datasource_id);
    }
    return (int) $select->countQuery()->execute()->fetchField();
  }

}
