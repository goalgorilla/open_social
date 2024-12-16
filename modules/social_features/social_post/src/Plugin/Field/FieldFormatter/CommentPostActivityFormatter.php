<?php

namespace Drupal\social_post\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentInterface;

/**
 * Provides a post comment activity formatter.
 *
 * @FieldFormatter(
 *   id = "comment_post_activity",
 *   module = "social_post",
 *   label = @Translation("Last two comments on post"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentPostActivityFormatter extends CommentPostFormatter {

  /**
   * {@inheritdoc}
   *
   * @see Drupal\comment\CommentStorage::loadThead()
   */
  public function loadThread(EntityInterface $entity, string $field_name, int $mode, int $comments_per_page = 0, int|bool $pager_id = 0): array {
    // @todo Refactor this to use CommentDefaultFormatter->loadThread with dependency injection instead.
    $query = \Drupal::database()->select('comment_field_data', 'c');
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

    $query->orderBy('c.cid', 'DESC');

    // Limit The number of results.
    if ($comments_per_page) {
      $query->range(0, $comments_per_page);
    }

    $cids = $query->execute()?->fetchCol();

    $comments = [];
    if ($cids) {
      krsort($cids);
      $comments = $this->storage->loadMultiple($cids);
    }

    return $comments;
  }

}
