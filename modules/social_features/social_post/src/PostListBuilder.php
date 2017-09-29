<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;

/**
 * Defines a class to build a listing of Post entities.
 *
 * @ingroup social_post
 */
class PostListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Post ID');
    $header['post'] = $this->t('Post');
    $header['author'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\social_post\Entity\Post */
    $row['id'] = $entity->id();
    $post_value = $entity->get('field_post')->value;
    $row['post'] = text_summary($post_value, NULL, 120);
    $row['author'] = $entity->getOwner()->toLink();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'small');
    return $row + parent::buildRow($entity);
  }

}
