<?php

namespace Drupal\social_demo;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drush\Log\LogLevel;

abstract class DemoEntity extends DemoContent {

  /**
   * DemoEntity constructor.
   * @param array $configuration
   * @param mixed $plugin_id
   * @param $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_demo.yaml_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createContent() {
    $data = $this->fetchData();
    $entity_type_id = $this->entityStorage->getEntityTypeId();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        drush_log(dt("{$entity_type_id} with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether comment with same uuid already exists.
      $entities = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($entities) {
        drush_log(dt("{$entity_type_id} with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[ $entity->id() ] = $entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry($item) {
    $entry = [
      'uuid' => $item['uuid'],
    ];

    return $entry;
  }

}
