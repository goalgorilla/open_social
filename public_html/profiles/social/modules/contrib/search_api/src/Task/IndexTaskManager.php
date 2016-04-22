<?php

namespace Drupal\search_api\Task;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility;

/**
 * Provides a service for managing pending index tasks.
 */
class IndexTaskManager implements IndexTaskManagerInterface {

  use StringTranslationTrait;

  /**
   * The site state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a IndexTaskManager object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The site state.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function startTracking(IndexInterface $index, array $datasource_ids = NULL) {
    $index_state = $this->getIndexState($index);
    if (empty($index_state['status'])) {
      if (!isset($datasource_ids)) {
        $datasource_ids = $index->getDatasourceIds();
      }
      elseif (!$datasource_ids) {
        // Calling with an empty array as $datasource_ids could otherwise break
        // this, so we have to check for this situation.
        return;
      }
      $index_state = array(
        'status' => 1,
        'pages' => array_fill_keys($datasource_ids, 0),
      );
      $this->state->set($this->getIndexStateKey($index), $index_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsOnce(IndexInterface $index) {
    $index_state = $this->getIndexState($index);
    if (!($index_state['status'] && $index_state['pages'])) {
      return NULL;
    }

    if (!$index->hasValidTracker()) {
      return 0;
    }

    // Use this method to automatically circle through the datasources, adding
    // items from each of them in turn.
    $page = reset($index_state['pages']);
    $datasource_id = key($index_state['pages']);
    unset($index_state['pages'][$datasource_id]);

    $added = 0;
    if ($index->isValidDatasource($datasource_id)) {
      $raw_ids = $index->getDatasource($datasource_id)->getItemIds($page);
      if ($raw_ids !== NULL) {
        $index_state['pages'][$datasource_id] = ++$page;
        if ($raw_ids) {
          $item_ids = array();
          foreach ($raw_ids as $raw_id) {
            $item_ids[] = Utility::createCombinedId($datasource_id, $raw_id);
          }
          $added = count($item_ids);
          $index->getTrackerInstance()->trackItemsInserted($item_ids);
        }
      }
    }

    if (empty($index_state['pages'])) {
      $this->state->delete($this->getIndexStateKey($index));
      return NULL;
    }

    $this->state->set($this->getIndexStateKey($index), $index_state);
    return $added;
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsBatch(IndexInterface $index) {
    $index_state = $this->getIndexState($index);
    if (!empty($index_state['status'])) {
      $batch_definition = array(
        'operations' => array(
          array(array($this, 'processBatch'), array($index->id())),
        ),
        'finished' => array($this, 'finishBatch'),
      );
      // Schedule the batch.
      batch_set($batch_definition);
    }
  }

  /**
   * Adds one page of items to the tracker as part of a batch operation.
   *
   * @param string $index_id
   *   The ID of the index on which items should be indexed.
   * @param array $context
   *   The current batch context, as defined in the @link batch Batch operations
   *   @endlink documentation.
   */
  public function processBatch($index_id, array &$context) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
    $added = $this->addItemsOnce($index);
    if (!isset($context['results']['added'])) {
      $context['results']['added'] = 0;
    }
    if ($added === NULL) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = 0;
      $context['results']['added'] += $added;
      $args['%index'] = $index->label();
      $context['message'] = $this->formatPlural($context['results']['added'], 'Tracked 1 item for the %index index.', 'Tracked @count items for the %index index.', $args);
    }
  }

  /**
   * Finishes a "start tracking" batch.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   Results information passed from the processing callback.
   */
  public function finishBatch($success, $results) {
    // Check if the batch job was successful.
    if ($success) {
      // Display the number of items tracked.
      $indexed_message = $this->formatPlural($results['added'], 'Successfully tracked 1 item for this index.', 'Successfully tracked @count items for this index.');
      drupal_set_message($indexed_message);
    }
    else {
      // Notify the user about the batch job failure.
      drupal_set_message($this->t('An error occurred while trying to start tracking. Check the logs for details.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsAll(IndexInterface $index) {
    do {
      $return = $this->addItemsOnce($index);
    } while ($return !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function stopTracking(IndexInterface $index, array $datasource_ids = NULL) {
    $valid_tracker = $index->hasValidTracker();
    if (!isset($datasource_ids)) {
      $this->state->delete($this->getIndexStateKey($index));
      if ($valid_tracker) {
        $index->getTrackerInstance()->trackAllItemsDeleted();
      }
      return;
    }

    // Catch the case of being called with an empty array of datasources.
    if (!$datasource_ids) {
      return;
    }

    // If no state is saved, this will return NULL, making the following unset()
    // statements no-ops.
    $index_state = $this->getIndexState($index, FALSE);
    foreach ($datasource_ids as $datasource_id) {
      unset($index_state['pages'][$datasource_id]);
      if ($valid_tracker) {
        $index->getTrackerInstance()->trackAllItemsDeleted($datasource_id);
      }
    }

    // If we had an index state saved, update it now.
    if (isset($index_state)) {
      if (empty($index_state['pages'])) {
        $this->state->delete($this->getIndexStateKey($index));
      }
      else {
        $this->state->set($this->getIndexStateKey($index), $index_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTrackingComplete(IndexInterface $index) {
    $index_state = $this->getIndexState($index);
    return !$index_state['status'];
  }

  /**
   * Retrieves the current state for a specific index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index whose state should be retrieved.
   * @param bool $provide_default
   *   Whether to provide a default state array if there was no state saved.
   *
   * @return array|null
   *   An associative array with information about the state of the given index:
   *   - status: 0 if no tracking operation is currently in progress, 1 if there
   *     is.
   *   - page: If status is 1, the next page of items to process for tracking.
   *   Or NULL if there was no saved state and $provide_default is FALSE.
   */
  protected function getIndexState(IndexInterface $index, $provide_default = TRUE) {
    $default = array(
      'status' => 0,
      'pages' => array(),
    );
    return $this->state->get($this->getIndexStateKey($index), $provide_default ? $default : NULL);
  }

  /**
   * Retrieves the key of the index's state.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index.
   *
   * @return string
   *   The key which identifies the index's state in the site state.
   */
  protected function getIndexStateKey(IndexInterface $index) {
    return 'search_api.index.' . $index->id() . '.tasks';
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the any properties from being serialized.
   */
  public function __sleep() {
    return array();
  }

  /**
   * Implements the magic __wakeup() method.
   *
   * Reloads the services into this object's properties.
   */
  public function __wakeup() {
    $this->state = \Drupal::state();
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

}
