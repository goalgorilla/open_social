<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\url_embed\UrlEmbedInterface;

/**
 * Service to extend embed functionality.
 */
class SocialUrlEmbedHelper implements SocialUrlEmbedHelperInterface {

  /**
   * The URL embed service.
   */
  protected UrlEmbedInterface $urlEmbed;

  /**
   * Cache backend.
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * Time service.
   */
  protected TimeInterface $time;

  /**
   * Constructs a new SocialUrlEmbedHelper object.
   */
  public function __construct(
    UrlEmbedInterface $urlEmbed,
    CacheBackendInterface $cacheBackend,
    TimeInterface $time,
  ) {
    $this->urlEmbed = $urlEmbed;
    $this->cacheBackend = $cacheBackend;
    $this->time = $time;
  }

  /**
   * Get the embed info for a URL.
   *
   * @param string $url
   *   The URL to embed.
   *
   * @return array|null
   *   Embed metadata or null.
   */
  public function getUrlInfo(string $url): ?array {
    $data = [];
    $keys = [
      'code',
      'providerName',
      'title',
    ];
    $cid = 'social_embed_url:' . $url;
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      $info = $this->urlEmbed->getEmbed($url);
      foreach ($keys as $key) {
        $data[$key] = $info->{$key};
      }
      $expiration = $this->time->getRequestTime() + 3600;
      $this->cacheBackend->set($cid, $data, $expiration);
    }

    return $data;
  }

}
