<?php

namespace Drupal\social_comment\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a default comment formatter.
 *
 * @FieldFormatter(
 *   id = "social_comment_default",
 *   module = "social_comment",
 *   label = @Translation("Social comment list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SocialCommentDefaultFormatter extends CommentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if (!empty($elements[0]['comments'])) {
      $elements[0]['comments'] = [
        '#lazy_builder' => [
          'social_comment.lazy_renderer:renderComments',
          [
            $items->getEntity()->id(),
            $this->getSetting('view_mode'),
            $items->getName(),
            $this->getSetting('num_comments'),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }
    return $elements;
  }

}
