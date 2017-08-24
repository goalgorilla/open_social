<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoNode;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_demo\DemoContentParserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\file\FileStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Topic Plugin for demo content.
 *
 * @DemoContent(
 *   id = "topic",
 *   label = @Translation("Topic"),
 *   source = "content/entity/topic.yml",
 *   entity_type = "node"
 * )
 */
class Topic extends DemoNode {

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Topic constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, UserStorageInterface $user_storage, EntityStorageInterface $group_storage, FileStorageInterface $file_storage, TermStorageInterface $term_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $parser, $user_storage, $group_storage);

    $this->fileStorage = $file_storage;
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_demo.yaml_parser'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('group'),
      $container->get('entity.manager')->getStorage('file'),
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = parent::getEntry($item);
    $entry['field_content_visibility'] = $item['field_content_visibility'];

    // Load term by name and set to node.
    if (!empty($item['field_topic_type'])) {
      $entry['field_topic_type'] = $this->prepareTopicType($item['field_topic_type']);
    }

    // Load image by uuid and set to node.
    if (!empty($item['field_topic_image'])) {
      $entry['field_topic_image'] = $this->prepareImage($item['field_topic_image']);
    }

    return $entry;
  }

  /**
   * Prepares data about an image of node.
   *
   * @param string $uuid
   *   Type of uuid.
   *
   * @return array|null
   *   Returns array|null
   */
  protected function prepareImage($uuid) {
    $value = NULL;
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $uuid,
    ]);

    if ($files) {
      $value = [
        [
          'target_id' => current($files)->id(),
        ],
      ];
    }

    return $value;
  }

  /**
   * Returns taxonomy term id.
   *
   * @param string $name
   *   Term name.
   *
   * @return array|null
   *   Array containing related terms.
   */
  protected function prepareTopicType($name) {
    $value = NULL;
    $terms = $this->termStorage->loadByProperties([
      'name' => $name,
    ]);

    if ($terms) {
      $value = [
        [
          'target_id' => current($terms)->id(),
        ],
      ];
    }

    return $value;
  }

}
