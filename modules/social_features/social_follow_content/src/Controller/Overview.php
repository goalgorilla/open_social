<?php

namespace Drupal\social_follow_content\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Overview.
 *
 * @package Drupal\social_follow_content
 */
class Overview extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Overview constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * List of followed entities.
   *
   * @return array
   *   The renderable array.
   */
  public function handle() {
    $storage = $this->entityTypeManager()->getStorage('flagging');

    $flaggings = $storage->getQuery()
      ->condition('uid', $this->currentUser()->id())
      ->pager(20)
      ->execute();

    if (empty($flaggings)) {
      return [];
    }

    $rows = [];

    $node_types = $this->entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $type = $this->requestStack->getCurrentRequest()->query->get('type');

    /** @var \Drupal\flag\FlaggingInterface $flagging */
    foreach ($storage->loadMultiple($flaggings) as $flagging) {
      $entity = $flagging->getFlaggable();
      $entity_type = $flagging->getFlaggableType();
      $bundle = $entity->bundle();

      if ($type && (($type === 'post' && $entity_type !== $type) || ($type !== 'post' && ($entity_type !== 'node' || $bundle !== $type)))) {
        continue;
      }

      if ($entity_type === 'node') {
        $title = NULL;
      }
      else {
        $title = Unicode::truncate($entity->field_post->value, 50, TRUE, TRUE, 3);
      }

      $row = [];

      $row['title'] = $entity->toLink($title);

      if ($entity_type === 'node') {
        $row['type'] = $node_types[$bundle]->label();
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

    if (empty($rows)) {
      return [];
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
