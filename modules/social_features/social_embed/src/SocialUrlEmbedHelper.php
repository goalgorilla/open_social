<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\social_embed\Service\SocialEmbedHelper;
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
   * The social embed helper service.
   */
  protected SocialEmbedHelper $embedHelper;

  /**
   * Constructs a new SocialUrlEmbedHelper object.
   */
  public function __construct(
    UrlEmbedInterface $urlEmbed,
    CacheBackendInterface $cacheBackend,
    TimeInterface $time,
    SocialEmbedHelper $embedHelper,
  ) {
    $this->urlEmbed = $urlEmbed;
    $this->cacheBackend = $cacheBackend;
    $this->time = $time;
    $this->embedHelper = $embedHelper;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(string $url): ?array {
    // Fetching the URL happens here, so this is the one place that decides
    // whether it may be requested at all. Guarding it here rather than in each
    // caller means a new caller can not forget the check.
    if (!$this->embedHelper->isWhitelisted($url)) {
      return NULL;
    }

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
