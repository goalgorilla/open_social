<?php

namespace Drupal\social_embed;

/**
 * Interface for SocialUrlEmbedHelper.
 */
interface SocialUrlEmbedHelperInterface {

  /**
   * Get the info for an URL embed.
   *
   * @param string $url
   *   The URL to embed.
   *
   * @return null|array
   *   the info for the URL embed.
   */
  public function getUrlInfo(string $url): ?array;

}
