<?php

/**
 * @file
 * Contains \Drupal\social_post\PostListBuilder.
 */

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\social_post\Entity\Post */
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
