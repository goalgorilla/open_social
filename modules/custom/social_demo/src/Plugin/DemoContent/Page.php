<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoNode;

/**
 * Page Plugin for demo content.
 *
 * @DemoContent(
 *   id = "page",
 *   label = @Translation("Basic page"),
 *   source = "content/entity/page.yml",
 *   entity_type = "node"
 * )
 */
class Page extends DemoNode {

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = parent::getEntry($item);
    $entry['field_content_visibility'] = $item['field_content_visibility'];

    // Load image by uuid and set to node.
    if (!empty($item['image'])) {
      $entry['field_page_image'] = $this->prepareImage($item['image'], $item['image_alt']);
    }

    if (!empty($item['alias'])) {
      $entry['path'] = [
        'alias' => $item['alias'],
      ];
    }

    return $entry;
  }

}
