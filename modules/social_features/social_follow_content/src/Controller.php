<?php

namespace Drupal\social_follow_content;

use Drupal\Component\Utility\Unicode;
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
      $node_types = $this->entityTypeManager()
        ->getStorage('node_type')
        ->loadMultiple();

      /** @var \Drupal\flag\FlaggingInterface $flagging */
      foreach ($storage->loadMultiple($flaggings) as $flagging) {
        $row = [];
        $entity = $flagging->getFlaggable();

        if ($flagging->getFlaggableType() === 'node') {
          $title = NULL;
        }
        else {
          $title = Unicode::truncate($entity->field_post->value, 50, TRUE, TRUE, 3);
        }

        $row['title'] = $entity->toLink($title);

        if ($flagging->getFlaggableType() === 'node') {
          $row['type'] = $node_types[$entity->bundle()]->label();
        }
        else {
          $row['type'] = $this->t('Post');
        }

        $flag = $flagging->getFlag();

        $row['operations']['data'] = $flag->getLinkTypePlugin()
          ->getAsLink($flag, $entity)
          ->toRenderable();

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
        '#attributes' => [
          'id' => 'social-follow-content-table',
        ],
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

}
