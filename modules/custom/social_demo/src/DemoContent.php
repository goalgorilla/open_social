<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;

abstract class DemoContent extends PluginBase implements DemoContentInterface {

  /**
   * Contains the created content.
   *
   * @var array
   */
  protected $content = [];

  /**
   * Contains data from a file.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Parser.
   *
   * @var \Drupal\social_demo\DemoContentParserInterface
   */
  protected $parser;

  /**
   * Contains the entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    $definition = $this->getPluginDefinition();
    return isset($definition['source']) ? $definition['source'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    $definition = $this->getPluginDefinition();
    return isset($definition['provider']) ? $definition['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function removeContent() {
    $data = $this->fetchData();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        continue;
      }

      $entities = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->content);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Gets the data from a file.
   */
  protected function fetchData() {
    if (!$this->data) {
      $this->data = $this->parser->parseFile($this->getSource(), $this->getModule());
    }

    return $this->data;
  }

  /**
   * Load entity by uuid.
   *
   * @param string $entity_type_id
   * @param string $uuid
   * @param bool $all
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   */
  protected function loadByUuid($entity_type_id, $uuid, $all = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entities = $storage->loadByProperties([
      'uuid' => $uuid,
    ]);

    if (!$all) {
      return current($entities);
    }

    return $entities;
  }

  /**
   * Makes an array with data of an entity.
   *
   * @param array $item
   * @return array
   */
  abstract protected function getEntry($item);

}
