<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Post entities.
 *
 * @ingroup social_post
 */
class PostListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['id'] = $this->t('Post ID');
    $header['post'] = $this->t('Post');
    $header['author'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /** @var \Drupal\social_post\Entity\Post $entity */
    $row['id'] = $entity->id();
    $row['post'] = '';
    if ($entity->hasField('field_post')) {
      $post_value = $entity->get('field_post')->value;
      $row['post'] = text_summary($post_value, NULL, 120);
    }
    $row['author'] = $entity->getOwner()->toLink();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'small');
    $row['status'] = $entity->isPublished() ? t("published") : t("unpublished");

    return $row + parent::buildRow($entity);
  }

}
