<?php

namespace Drupal\social_follow_content\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
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

    if ($type === 'All') {
      $type = NULL;
    }

    $titles = $types = [];

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
      $titles[$entity->id()] = $row['title']->getText();

      if ($entity_type === 'node') {
        $row['type'] = $node_types[$bundle]->label();
      }
      else {
        $row['type'] = $this->t('Post');
      }

      $types[$entity->id()] = $row['type'];

      if (!is_string($types[$entity->id()])) {
        $types[$entity->id()] = $types[$entity->id()]->render();
      }

      $flag = $flagging->getFlag();

      $row['operations']['data'] = $flag->getLinkTypePlugin()
        ->getAsLink($flag, $entity)
        ->toRenderable();

      $rows[$entity->id()] = $row;
    }

    if (empty($rows)) {
      return [];
    }

    $sortable_columns = ['title', 'type'];
    $sortable_methods = ['asc' => 'desc', 'desc' => 'asc'];
    $sortable_column = $this->requestStack->getCurrentRequest()->query->get('order');

    if (in_array($sortable_column, $sortable_columns)) {
      $sortable_method = $this->requestStack->getCurrentRequest()->query->get('sort');

      if (!in_array($sortable_method, $sortable_methods)) {
        $sortable_method = 'asc';
      }

      $list_name = $sortable_column . 's';
      $sortable_function = $sortable_method === 'asc' ? 'asort' : 'arsort';

      $sortable_function($$list_name);

      $unsorted_rows = $rows;
      $rows = [];

      foreach (array_keys($$list_name) as $entity_id) {
        $rows[$entity_id] = $unsorted_rows[$entity_id];
      }
    }

    $build = [
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

    foreach ($sortable_columns as $column) {
      if ($is_sortable_column = $sortable_column === $column) {
        $current_sortable_method = $sortable_method;
      }
      else {
        $current_sortable_method = 'desc';
      }

      $current_sortable_method = $sortable_methods[$current_sortable_method];

      if ($is_sortable_column) {
        $build['table']['#header'][$column] = [
          'text' => [
            '#markup' => $build['table']['#header'][$column],
          ],
          'indicator' => [
            '#theme' => 'tablesort_indicator',
            '#style' => $current_sortable_method,
          ],
        ];
      }

      $build['table']['#header'][$column] = [
        'data' => [
          '#type' => 'link',
          '#title' => $build['table']['#header'][$column],
          '#url' => Url::fromRoute('social_follow_content.overview', [], [
            'query' => [
              'type' => $type ?: 'All',
              'order' => $column,
              'sort' => $current_sortable_method,
            ],
          ]),
        ],
      ];
    }

    return $build;
  }

}
