<?php

namespace Drupal\social_demo;

/**
 * Creates taxonomy terms for demo.
 *
 * @package Drupal\social_demo
 */
abstract class DemoTaxonomyTerm extends DemoContent {

  /**
   * {@inheritdoc}
   */
  public function createContent($generate = FALSE, $max = NULL) {
    $data = $this->fetchData();
    if ($generate === TRUE) {
      $data = $this->scrambleData($data, $max);
    }

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        $this->loggerChannelFactory->get('social_demo')->error("Term with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether node with same uuid already exists.
      $terms = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($terms) {
        $this->loggerChannelFactory->get('social_demo')->warning("Term with uuid: {$uuid} already exists.");
        continue;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[$entity->id()] = $entity;
      }
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = [
      'uuid' => $item['uuid'],
      'name' => $item['name'],
      'vid' => $item['vid'],
      'created' => \Drupal::time()->getRequestTime(),
    ];

    return $entry;
  }

}
