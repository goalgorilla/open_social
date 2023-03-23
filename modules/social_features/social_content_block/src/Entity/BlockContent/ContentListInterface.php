<?php

namespace Drupal\social_content_block\Entity\BlockContent;

use Drupal\block_content\BlockContentInterface;

/**
 * Provides an interface for the "Content list" custom block bundle class.
 */
interface ContentListInterface extends BlockContentInterface {

  /**
   * Returns value of subtitle field.
   */
  public function getSubtitle(): string;

  /**
   * Checks if the subtitle field is filled.
   */
  public function hasSubtitle(): bool;

}
