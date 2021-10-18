<?php

namespace Drupal\social_demo;

/**
 * Abstract class for creating demo entity.
 *
 * @package Drupal\social_demo
 */
abstract class DemoEntity extends DemoContent {

  /**
   * {@inheritdoc}
   */
  public function createContent($generate = FALSE, $max = NULL) {
    $data = $this->fetchData();
    if ($generate === TRUE) {
      $data = $this->scrambleData($data, $max);
    }
    $entity_type_id = $this->entityStorage->getEntityTypeId();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        $this->loggerChannelFactory->get('social_demo')->error("{$entity_type_id} with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether comment with same uuid already exists.
      $entities = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($entities) {
        $this->loggerChannelFactory->get('social_demo')->warning("{$entity_type_id} with uuid: {$uuid} already exists.");
        continue;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[$entity->id()] = $entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = [
      'uuid' => $item['uuid'],
    ];

    return $entry;
  }

}
