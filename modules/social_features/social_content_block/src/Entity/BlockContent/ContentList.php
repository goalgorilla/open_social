<?php

namespace Drupal\social_content_block\Entity\BlockContent;

use Drupal\block_content\Entity\BlockContent;

/**
 * Defines bundle class for custom content list block.
 */
class ContentList extends BlockContent implements ContentListInterface {

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(): string {
    return $this->get('field_subtitle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubtitle(): bool {
    return !$this->get('field_subtitle')->isEmpty();
  }

}
