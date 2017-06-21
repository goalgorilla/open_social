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
 * @DemoContent(
 *   id = "event",
 *   label = @Translation("Event"),
 *   source = "content/entity/event.yml",
 *   entity_type = "node"
 * )
 */
class Event extends DemoNode {

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
   * SocialDemoEvent constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\file\FileStorageInterface $file_storage
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_storage
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
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
  protected function getEntry($item) {
    $entry = parent::getEntry($item);

    $entry['field_event_address'] = $item['field_event_address'];
    $entry['field_event_date'] = $this->createDate($item['field_event_date']);
    $entry['field_event_date_end'] = $this->createDate($item['field_event_date_end']);
    $entry['field_event_location'] = $item['field_event_location'];
    $entry['field_content_visibility'] = $item['field_content_visibility'];

    // Load image by uuid and set to node.
    if (!empty($item['field_event_image'])) {
      $entry['field_event_image'] = $this->prepareImage($item['field_event_image']);
    }

    if (\Drupal::moduleHandler()->moduleExists('social_event_type') && !empty($item['field_event_type'])) {
      $entry['field_event_type'] = $this->prepareEventType($item['field_event_type']);
    }

    return $entry;
  }

  /**
   * Function to calculate the date.
   */
  protected function createDate($date_string) {
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date('Y-m-d', $date) . 'T' . $timestamp[1] . ':00';

    return $date;

  }

  /**
   * Prepares data about an image of node.
   *
   * @param $uuid
   * @return array|null
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
   * @param $uuid
   * @return array|null
   */
  protected function prepareEventType($uuid) {
    $value = NULL;
    $terms = $this->termStorage->loadByProperties([
      'uuid' => $uuid,
    ]);

    if ($terms) {
      $value = [
        [
          'target_id' => current($terms)->id(),
        ]
      ];
    }

    return $value;
  }

}
