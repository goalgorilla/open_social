<?php

namespace Drupal\social_post\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\CommentInterface;
use Drupal\Core\Link;

/**
 * Provides a post comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_post",
 *   module = "social_post",
 *   label = @Translation("Comment on post list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentPostFormatter extends CommentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'num_comments' => 2,
      'order' => 'DESC',
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

    $comments_per_page = $this->getSetting('num_comments');

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
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

          if ($comments_per_page && $comment_count > $comments_per_page) {
            $t_args = [':num_comments' => $comment_count];
            $more_link = $this->t('Show all :num_comments comments', $t_args);

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

            // Set path to post node.
            $link_url = $entity->urlInfo('canonical');

            // Attach the attributes.
            $link_url->setOptions($more_link_options);

            // Build the link.
            $more_button = Link::fromTextAndUrl($more_link, $link_url);
            $output['more_link'] = $more_button;
          }
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity. Do not show the form for the print view mode.
      if ($status == CommentItemInterface::OPEN && $comment_settings['form_location'] == CommentItemInterface::FORM_BELOW && $this->viewMode != 'print') {
        // Only show the add comment form if the user has permission.
        $elements['#cache']['contexts'][] = 'user';
        $add_comment_form = FALSE;
        // Check if the post has been posted in a group.
        $group_id = $entity->field_recipient_group->target_id;
        if ($group_id) {
          /** @var \Drupal\group\Entity\Group $group */
          $group = entity_load('group', $group_id);
          if ($group->hasPermission('add post entities in group', $this->currentUser) && $this->currentUser->hasPermission('post comments')) {
            $add_comment_form = TRUE;
          }
        }
        elseif ($this->currentUser->hasPermission('post comments')) {
          $add_comment_form = TRUE;
        }
        if ($add_comment_form) {
          $output['comment_form'] = [
            '#lazy_builder' => ['comment.lazy_builders:renderForm', [
              $entity->getEntityTypeId(),
              $entity->id(),
              $field_name,
              $this->getFieldSetting('comment_type'),
            ],
            ],
            '#create_placeholder' => TRUE,
          ];
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
    $orders = [
      'ASC' => $this->t('Oldest first'),
      'DESC' => $this->t('Newest first'),
    ];
    $element['order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order'),
      '#description' => $this->t('Select the order used to show the list of comments.'),
      '#default_value' => $this->getSetting('order'),
      '#options' => $orders,
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
      ->addTag('entity_access')
      ->addTag('comment_filter')
      ->addMetaData('base_table', 'comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    $comments_order = $this->getSetting('order');

    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c.status', CommentInterface::PUBLISHED);
    }
    if ($mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      $query->orderBy('c.cid', $comments_order);
    }
    else {
      // See comment above. Analysis reveals that this doesn't cost too
      // much. It scales much much better than having the whole comment
      // structure.
      $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
      $query->orderBy('torder', $comments_order);
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
