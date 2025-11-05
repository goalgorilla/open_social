<?php

namespace Drupal\kpi_analytics;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The BlockContentCreator class.
 *
 * @package Drupal\kpi_analytics
 */
class BlockContentCreator {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The block creator service.
   */
  protected BlockCreator $blockCreator;

  /**
   * The 'block_content' entity.
   */
  protected BlockContent $entity;

  /**
   * Path to directory with the file source.
   */
  protected ?string $path;

  /**
   * Identifier of a block. Should be equal to filename.
   */
  protected ?string $id;

  /**
   * Cache for the parsed data.
   */
  protected ?array $data;

  /**
   * The block content storage.
   */
  protected EntityStorageInterface $blockContentStorage;

  /**
   * BlockContentCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\kpi_analytics\BlockCreator $block_creator
   *   The block creator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BlockCreator $block_creator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->blockCreator = $block_creator;
    $this->blockContentStorage = $entity_type_manager->getStorage('block_content');
  }

  /**
   * Set path to directory with the file source and block ID being created.
   *
   * @param string $path
   *   Path to directory with the file source.
   * @param string $id
   *   Identifier of a block.
   */
  public function setSource(string $path, string $id): void {
    $this->path = $path;
    $this->id = $id;
    $this->data = NULL;
  }

  /**
   * Parse data from a YAML file.
   */
  protected function getData($reset = FALSE): ?array {
    if (!$this->data || $reset) {
      $source = "{$this->path}/{$this->id}.yml";
      $content = file_get_contents($source);
      $this->data = Yaml::parse($content);
    }

    return $this->data;
  }

  /**
   * Get created entity.
   */
  public function getEntity(): BlockContent {
    return $this->entity;
  }

  /**
   * Create entity with values defined in a yaml file.
   */
  public function create(): EntityInterface {
    $data = $this->getData();
    $values = $data['values'];

    if ($block_content = $this->blockContentStorage->loadByProperties(['uuid' => $values['uuid']])) {
      $this->entity = current($block_content);

      return $this->entity;
    }

    // Create base instance of the entity being created.
    $this->entity = $this->blockContentStorage->create($values);

    // Fill fields.
    foreach ($data['fields'] ?? [] as $field_name => $value) {
      $this->entity->get($field_name)->setValue($value);
    }

    $this->entity->save();

    return $this->entity;
  }

  /**
   * Update entity with values defined in a yaml file.
   */
  public function update() {
    $data = $this->getData();
    $values = $data['values'];

    if ($block_content = $this->blockContentStorage->loadByProperties(['uuid' => $values['uuid']])) {
      $this->entity = current($block_content);

      // Fill fields.
      foreach ($data['fields'] ?? [] as $field_name => $value) {
        $this->entity->get($field_name)->setValue($value);
      }

      $this->entity->save();

      return $this->entity;
    }
  }

  /**
   * Delete block content.
   */
  public function delete(): void {
    $data = $this->getData();
    $values = $data['values'];

    if ($block_content = $this->blockContentStorage->loadByProperties(['uuid' => $values['uuid']])) {
      current($block_content)->delete();
    }
  }

  /**
   * Create instance of created block content.
   *
   * @param string $path
   *   Path to directory with the source file.
   * @param string $id
   *   Identifier of block and filename without extension.
   *
   *   The block entity.
   */
  public function createBlockInstance(string $path, string $id): EntityInterface {
    $block_creator = clone $this->blockCreator;
    $block_creator->setSource($path, $id);
    $block_creator->setPluginId('block_content:' . $this->entity->get('uuid')->value);

    return $block_creator->create();
  }

}
