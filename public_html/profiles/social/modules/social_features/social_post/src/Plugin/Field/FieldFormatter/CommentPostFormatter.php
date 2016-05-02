<?php

/**
 * @file
 * Contains \Drupal\social_post\Plugin\Field\FieldFormatter\CommentPostFormatter.
 */

namespace Drupal\social_post\Plugin\Field\FieldFormatter;

use Drupal\comment\CommentStorage;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    return array(
      'num_comments' => 2,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $output = array();

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();

    $status = $items->status;

    $comments_per_page = $this->getSetting('num_comments');

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, array('search_result', 'search_index'))) {
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
            $t_args = array(':num_comments' => $comment_count);
            $more_link = $this->t('Show all :num_comments comments', $t_args);

            $more_button = Link::fromTextAndUrl($more_link, $entity->urlInfo('canonical'));
            $output['more_link'] = $more_button;
          }
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity. Do not show the form for the print view mode.
      if ($status == CommentItemInterface::OPEN && $comment_settings['form_location'] == CommentItemInterface::FORM_BELOW && $this->viewMode != 'print') {
        // Only show the add comment form if the user has permission.
        $elements['#cache']['contexts'][] = 'user.roles';
        if ($this->currentUser->hasPermission('post comments')) {
          $output['comment_form'] = [
            '#lazy_builder' => ['comment.lazy_builders:renderForm', [
              $entity->getEntityTypeId(),
              $entity->id(),
              $field_name,
              $this->getFieldSetting('comment_type'),
            ]],
            '#create_placeholder' => TRUE,
          ];
        }
      }

      $elements[] = $output + array(
          '#comment_type' => $this->getFieldSetting('comment_type'),
          '#comment_display_mode' => $this->getFieldSetting('default_mode'),
          'comments' => array(),
          'comment_form' => array(),
          'more_link' => array(),
        );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $element['num_comments'] = array(
      '#type' => 'number',
      '#min' => 0,
      '#max' => 10,
      '#title' => $this->t('Number of comments'),
      '#default_value' => $this->getSetting('num_comments'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array();
  }

  /**
   * {@inheritdoc}
   *
   * @see Drupal\comment\CommentStorage::loadThead().
   */
  public function loadThread(EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0) {
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

    $comments = array();
    if ($cids) {
      $comments = entity_load_multiple('comment', $cids);
    }

    return $comments;
  }

}
