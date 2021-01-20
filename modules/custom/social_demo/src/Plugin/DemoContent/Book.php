<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\node\Entity\Node;
use Drupal\social_demo\DemoNode;
use Drupal\social_demo\DemoContentParserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\FileStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Book Plugin for demo content.
 *
 * @DemoContent(
 *   id = "book",
 *   label = @Translation("Book page"),
 *   source = "content/entity/book.yml",
 *   entity_type = "node"
 * )
 */
class Book extends DemoNode {

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, UserStorageInterface $user_storage, EntityStorageInterface $group_storage, FileStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $parser, $user_storage, $group_storage);

    $this->fileStorage = $file_storage;
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
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('group'),
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = parent::getEntry($item);
    $entry['field_content_visibility'] = $item['field_content_visibility'];

    // Load image by uuid and set to node.
    if (!empty($item['field_book_image'])) {
      $entry['field_book_image'] = $this->prepareImage($item['image'], $item['image_alt']);
    }

    // Load attachments to node.
    if (!empty($item['field_files'])) {
      $entry['field_files'] = $this->prepareAttachment($item['field_files']);
    }

    if (!empty($item['alias'])) {
      $entry['path'] = [
        'alias' => $item['alias'],
      ];
    }

    if (!empty($item['book'])) {
      // Top level book.
      if ($item['book']['id'] === $item['uuid']) {
        $entry['book']['bid'] = 'new';
        unset($entry['book']['id']);
      }

      $mainbook = $this->entityStorage->loadByProperties(['uuid' => $item['book']['id']]);
      $mainbook = current($mainbook);
      // Must be a valid node.
      if ($mainbook instanceof Node) {
        $entry['book']['bid'] = $mainbook->id();
        $entry['book']['weight'] = $item['book']['weight'];

        unset($entry['book']['id']);

        if (isset($item['book']['parent'])) {
          $parentbook = $this->entityStorage->loadByProperties(['uuid' => $item['book']['parent']]);
          $parentbook = current($parentbook);
          if ($parentbook instanceof Node) {
            $entry['book']['pid'] = $parentbook->id();
          }
        }
        else {
          $entry['book']['pid'] = $mainbook->id();
        }
      }
    }
    return $entry;
  }

  /**
   * Returns reference to attachment, possibly with a description.
   *
   * @param array $files
   *   Array with UUIDs of files.
   *
   * @return array|null
   *   Array containing related files or NULL.
   */
  protected function prepareAttachment(array $files) {
    $attachments = NULL;

    foreach ($files as $file) {
      $description = '';

      // If it is an array, this means we also have a description.
      $uuid = $file;
      if (is_array($file)) {
        $uuid = key($file);
        $description = current($file);
      }

      $object = $this->fileStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($object) {
        $properties = [
          'target_id' => current($object)->id(),
          'description' => $description,
        ];

        $attachments[] = $properties;
      }
    }

    return $attachments;
  }

}
