<?php

namespace Drupal\social_content_block_landing_page\Service;

use Drupal\block_content\BlockContentInterface;
use Drupal\social_content_block\ContentBuilder;

/**
 * Class SocialContentBlockLandingPageContentBuilder.
 *
 * @package Drupal\social_content_block_landing_page\Service
 */
class SocialContentBlockLandingPageContentBuilder extends ContentBuilder {

  /**
   * {@inheritdoc}
   */
  public function build($entity_id, $entity_type_id, $entity_bundle) : array {
    $build = parent::build($entity_id, $entity_type_id, $entity_bundle);

    if (!isset($build['content']['entities']['#markup'])) {
      $build['content']['entities']['#prefix'] = str_replace(
        'content-list__items',
        'field--name-field-featured-items',
        $build['content']['entities']['#prefix']
      );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLink(BlockContentInterface $block_content) : array {
    if ($link = parent::getLink($block_content)) {
      $link = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['card__link'],
        ],
        'link' => $link,
      ];
    }

    return $link;
  }

}
