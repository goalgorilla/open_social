<?php

namespace Drupal\social_demo;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drush\Log\LogLevel;

abstract class DemoTaxonomyTerm extends DemoContent {

  /**
   * DemoTaxonomyTerm constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
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

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        drush_log(dt("Term with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether node with same uuid already exists.
      $terms = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($terms) {
        drush_log(dt("Term with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[ $entity->id() ] = $entity;
      }
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry($item) {
    $entry = [
      'uuid' => $item['uuid'],
      'name' => $item['name'],
      'vid' => $item['vid'],
      'created' => REQUEST_TIME,
    ];

    return $entry;
  }

}
