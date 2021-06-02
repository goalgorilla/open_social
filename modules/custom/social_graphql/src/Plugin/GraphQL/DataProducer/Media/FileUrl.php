<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Media;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Convert file URI to URL.
 *
 * @DataProducer(
 *   id = "file_url",
 *   name = @Translation("File url"),
 *   description = @Translation("Convert uri to url."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("File url")
 *   ),
 *   consumes = {
 *     "uri" = @ContextDefinition("string",
 *       label = @Translation("File uri")
 *     ),
 *   }
 * )
 */
class FileUrl extends DataProducerPluginBase {

  /**
   * Resolves the request to the requested values.
   *
   * @param string $uri
   *   The file URI.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return string
   *   The file URL.
   */
  public function resolve($uri, RefinableCacheableDependencyInterface $metadata) {
    return file_create_url($uri);
  }

}
