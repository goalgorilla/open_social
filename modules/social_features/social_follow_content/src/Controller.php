<?php

namespace Drupal\social_follow_content;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class Controller.
 *
 * @package Drupal\social_follow_content
 */
class Controller extends ControllerBase {

  /**
   * List of followed entities.
   *
   * @return array
   *   The renderable array.
   */
  public function page() {
    $storage = $this->entityTypeManager()->getStorage('flagging');

    $flaggings = $storage->getQuery()
      ->condition('uid', $this->currentUser()->id())
      ->pager(20)
      ->execute();

    $rows = [];

    if (!empty($flaggings)) {
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      foreach ($storage->loadMultiple($flaggings) as $flagging) {
        $row = [];
        $entity = $flagging->getFlaggable();

        if ($flagging->getFlaggableType() == 'node') {
          $row['title'] = $entity->label();
          $row['type'] = $entity->getType();
        }
        else {
          $row['title'] = $entity->id();
          $row['type'] = $this->t('Post');
        }

        $flag = $flagging->getFlag();
        $row['operations'] = $flag->getLinkTypePlugin()->getAsLink($flag, $entity);

        $rows[] = $row;
      }
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => [
          'title' => $this->t('Title'),
          'type' => $this->t('Type'),
          'operations' => $this->t('Operations'),
        ],
        '#rows' => $rows,
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

}
