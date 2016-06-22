<?php

/**
 * @file
 * Contains \Drupal\webprofiler\State\StateWrapper.
 */

namespace Drupal\webprofiler\State;

use Drupal\Core\State\StateInterface;
use Drupal\webprofiler\DataCollector\StateDataCollector;

/**
 * Class StateWrapper
 */
class StateWrapper implements StateInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * @var \Drupal\webprofiler\DataCollector\StateDataCollector
   */
  private $dataCollector;

  /**
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\webprofiler\DataCollector\StateDataCollector $dataCollector
   */
  public function __construct(StateInterface $state, StateDataCollector $dataCollector) {
    $this->state = $state;
    $this->dataCollector = $dataCollector;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $this->dataCollector->addState($key);

    return $this->state->get($key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    foreach ($keys as $key) {
      $this->dataCollector->addState($key);
    }

    return $this->state->getMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->state->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    $this->state->setMultiple($data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->state->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    $this->state->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->state->resetCache();
  }
}
