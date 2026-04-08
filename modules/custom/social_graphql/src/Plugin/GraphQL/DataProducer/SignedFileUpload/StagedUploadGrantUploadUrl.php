<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the absolute tus upload URL for a staged upload grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_upload_url",
 *   name = @Translation("Staged upload grant upload URL"),
 *   description = @Translation("Builds the absolute URL for the tus endpoint using the grant's upload token."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Upload URL"),
 *     required = TRUE
 *   ),
 *   consumes = {
 *     "grant" = @ContextDefinition("any",
 *       label = @Translation("Staged upload grant"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadGrantUploadUrl extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a StagedUploadGrantUploadUrl producer.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   Used to build absolute routes to the tus endpoint.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected UrlGeneratorInterface $urlGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance using the container for URL generation.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   A new StagedUploadGrantUploadUrl instance.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_generator'),
    );
  }

  /**
   * Builds the absolute tus endpoint URL for the grant's upload token.
   *
   * Bubbleable metadata from URL generation is merged into $metadata for
   * correct cache contexts on the GraphQL response.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   GraphQL resolver cache metadata; receives the URL object's dependencies.
   *
   * @return string
   *   Absolute URL for PATCH/HEAD/DELETE against the staged bytes.
   */
  public function resolve(StagedUploadGrant $grant, RefinableCacheableDependencyInterface $metadata): string {
    $url = $this->urlGenerator->generateFromRoute(
      'signed_file_upload.tus',
      ['token' => $grant->sessionGrant->uploadToken],
      ['absolute' => TRUE],
      TRUE,
    );
    assert($url instanceof GeneratedUrl, "collect_bubbleable_metadata=TRUE should return GeneratedUrl");
    $metadata->addCacheableDependency($url);

    return $url->getGeneratedUrl();
  }

}
