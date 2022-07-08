<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Media;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class FileUrl extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * File Generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * Constructs a FileUrl object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   File url generator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileUrlGenerator $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('file_url_generator')
    );
  }

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
    return $this->fileUrlGenerator->generateAbsoluteString($uri);
  }

}
