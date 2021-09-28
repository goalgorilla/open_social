<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\node\Entity\Node;
use Drupal\social_demo\DemoNode;

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
