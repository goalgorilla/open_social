<?php

namespace Drupal\social_content_block_landing_page\Service;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\social_content_block\ContentBuilder;

/**
 * Class SocialContentBlockLandingPageContentBuilder.
 *
 * @package Drupal\social_content_block_landing_page\Service
 */
class SocialContentBlockLandingPageContentBuilder extends ContentBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function build($entity_id, $entity_type_id, $entity_bundle) : array {
    $build = parent::build($entity_id, $entity_type_id, $entity_bundle);

    if (!$build) {
      return $build;
    }

    $weight = 1;

    foreach (Element::children($build['content']) as $key) {
      $build['content'][$key]['#weight'] = $weight++;
    }

    $build['content']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => [
        'class' => ['title'],
      ],
      '#value' => $this->entityTypeManager->getStorage($entity_type_id)
        ->load($entity_id)
        ->field_subtitle
        ->value,
      '#weight' => 0,
    ];

    if (!isset($build['content']['entities']['#markup']) && !isset($build['content']['entities']['#lazy_builder'])) {
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
  public function getEntities($block_id) {
    $elements = parent::getEntities($block_id);

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
      $link['#url']->setOption('attributes', []);

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

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['build'];
  }

}
