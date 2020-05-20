<?php

namespace Drupal\social_content_block_landing_page\Service;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Render\Element;
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
  protected function getEntities(BlockContentInterface $block_content) {
    $elements = parent::getEntities($block_content);

    foreach (Element::children($elements) as $delta) {
      $elements[$delta]['#custom_content_list_section'] = TRUE;
    }

    return $elements;
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
