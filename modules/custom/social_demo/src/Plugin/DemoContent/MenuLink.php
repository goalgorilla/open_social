<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoEntity;

/**
 * @DemoContent(
 *   id = "link",
 *   label = @Translation("Menu link"),
 *   source = "content/entity/menu-link.yml",
 *   entity_type = "menu_link_content"
 * )
 */
class MenuLink extends DemoEntity {

  /**
   * {@inheritdoc}
   */
  public function getEntry($item) {
    $entry = parent::getEntry($item);

    return $entry + [
      'title' => $item['title'],
      'link' => [
        'uri' => $item['link'],
      ],
      'menu_name' => $item['menu_name'],
      'expanded' => $item['expanded'],
    ];
  }

}
