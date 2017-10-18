<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\CommentInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a node comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_node",
 *   module = "social_core",
 *   label = @Translation("Comment on node list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentNodeFormatter extends CommentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'num_comments' => 2,
      'always_show_all_comments' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();
    $status = $items->status;
    $access_comments_in_group = FALSE;

    // Exclude entities without the set id.
    if (!empty($entity->id())) {
      $group_contents = GroupContent::loadByEntity($entity);
    }

    if (!empty($group_contents)) {
      // Add cache contexts.
      $elements['#cache']['contexts'][] = 'group.type';
      $elements['#cache']['contexts'][] = 'group_membership';

      $account = \Drupal::currentUser();
      $renderer = \Drupal::service('renderer');

      foreach ($group_contents as $group_content) {
        $group = $group_content->getGroup();
        $membership = $group->getMember($account);
        $renderer->addCacheableDependency($elements, $membership);
        if ($group->hasPermission('access comments', $account)) {
          $access_comments_in_group = TRUE;
        }
      }
    }

    $comments_per_page = $this->getSetting('num_comments');

    if ($access_comments_in_group && $status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, ['search_result', 'search_index'])) {
      $comment_settings = $this->getFieldSettings();

      $comment_count = $entity->get($field_name)->comment_count;

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';
      if ($this->currentUser->hasPermission('access comments') || $this->currentUser->hasPermission('administer comments')) {
        $output['comments'] = [];

        if ($comment_count || $this->currentUser->hasPermission('administer comments')) {
          $mode = $comment_settings['default_mode'];
          $comments = $this->loadThread($entity, $field_name, $mode, $comments_per_page, FALSE);
          if ($comments) {
            $build = $this->viewBuilder->viewMultiple($comments);
            $output['comments'] += $build;
          }

        }

        // Prepare the show all comments link.
        $t_args = [':num_comments' => $comment_count];

        // Set link classes to be added to the button.
        $more_link_options = [
          'attributes' => [
            'class' => [
              'btn',
              'btn-flat',
              'brand-text-primary',
            ],
          ],
        ];

        // Set path to node.
        $link_url = $entity->urlInfo('canonical');

        // Attach the attributes.
        $link_url->setOptions($more_link_options);

        if ($comment_count == 0) {
          $more_link = $this->t(':num_comments comments', $t_args);
          $output['more_link'] = $more_link;
        }
        elseif ($comment_count == 1) {
          $more_link = $this->t(':num_comments comment', $t_args);
          $output['more_link'] = $more_link;
        }
        else {
          $more_link = $this->t('Show all :num_comments comments', $t_args);
        }

        // Build the link.
        $more_button = Link::fromTextAndUrl($more_link, $link_url);

        $always_show_all_comments = $this->getSetting('always_show_all_comments');
        if ($always_show_all_comments && $comment_count > 1) {
          $output['more_link'] = $more_button;
        }
        elseif ($comments_per_page && $comment_count > $comments_per_page) {
          $output['more_link'] = $more_button;
        }

      }

      $elements[] = $output + [
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => [],
        'comment_form' => [],
        'more_link' => [],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['num_comments'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 10,
      '#title' => $this->t('Number of comments'),
      '#default_value' => $this->getSetting('num_comments'),
    ];
    $element['always_show_all_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show all comments link'),
      '#description' => $this->t('If selected it will show a "all comments" link if there is at least 1 comment.'),
      '#default_value' => $this->getSetting('always_show_all_comments'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @see Drupal\comment\CommentStorage::loadThead()
   */
  public function loadThread(EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0) {
    // @TODO: Refactor this to use CommentDefaultFormatter->loadThread with dependency injection instead.
    $query = db_select('comment_field_data', 'c');
    $query->addField('c', 'cid');
    $query
      ->condition('c.entity_id', $entity->id())
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.field_name', $field_name)
      ->condition('c.default_langcode', 1)
      ->isNull('c.pid')
      ->addTag('entity_access')
      ->addTag('comment_filter')
      ->addMetaData('base_table', 'comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c.status', CommentInterface::PUBLISHED);
    }
    if ($mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      $query->orderBy('c.cid', 'DESC');
    }
    else {
      // See comment above. Analysis reveals that this doesn't cost too
      // much. It scales much much better than having the whole comment
      // structure.
      $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
      $query->orderBy('torder', 'DESC');
    }

    // Limit The number of results.
    if ($comments_per_page) {
      $query->range(0, $comments_per_page);
    }

    $cids = $query->execute()->fetchCol();

    $comments = [];
    if ($cids) {
      $comments = entity_load_multiple('comment', $cids);
    }

    return $comments;
  }

}
