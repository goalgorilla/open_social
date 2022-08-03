<?php

namespace Drupal\social_mentions;

/**
 * Defines Social mentions' helper service interface.
 */
interface SocialMentionsHelperInterface {

  /**
   * Get the mentions prefix and suffix.
   *
   * @return array
   *   Prefix and suffix array.
   */
  public function getMentionsPrefixSuffix(): array;

  /**
   * Gets the mentions pattern.
   *
   * @return string
   *   Pattern string.
   */
  public function getMentionsPattern(): string;

  /**
   * Get the mentions from text.
   *
   * @param string $text
   *   The text to find mentions in.
   *
   * @return array
   *   A list of mentions.
   */
  public function getMentions(string $text): array;

}
