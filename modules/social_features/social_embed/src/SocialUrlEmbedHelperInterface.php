<?php

namespace Drupal\social_embed;

/**
 * Interface for SocialUrlEmbedHelper.
 */
interface SocialUrlEmbedHelperInterface {

  /**
   * Get the info for an URL embed.
   *
   * Only URLs of the providers we support are fetched, anything else returns
   * NULL without a request being made.
   *
   * @param string $url
   *   The URL to embed.
   *
   * @return array|null
   *   The info for the URL embed, or NULL when we do not embed this URL.
   *
   * @see \Drupal\social_embed\Service\SocialEmbedHelper::isWhitelisted()
   */
  public function getUrlInfo(string $url): ?array;

}
