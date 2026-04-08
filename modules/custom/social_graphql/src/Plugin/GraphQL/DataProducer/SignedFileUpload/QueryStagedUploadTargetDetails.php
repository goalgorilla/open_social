<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\signed_file_upload\UploadConstraintResolverInterface;
use Drupal\social_graphql\SignedFileUpload\StagedUploadTargetEnumDestinationResolverTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the staged upload constraints based on a target enum value.
 *
 * @DataProducer(
 *   id = "query_staged_uploads_target_details",
 *   name = @Translation("Target enum to upload constraints"),
 *   description = @Translation("Resolves the staged upload constraints based on a target enum value."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("ResolvedUploadConstraints")
 *   ),
 *   consumes = {
 *     "target" = @ContextDefinition("string",
 *       label = @Translation("Target"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class QueryStagedUploadTargetDetails extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use StagedUploadTargetEnumDestinationResolverTrait;

  /**
   * Constructs a QueryStagedUploadTargetDetails producer.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\signed_file_upload\UploadConstraintResolverInterface $uploadConstraintResolver
   *   Resolves upload destinations to normalized constraint snapshots.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected UploadConstraintResolverInterface $uploadConstraintResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
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
   *   A configured producer instance.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(UploadConstraintResolverInterface::class),
    );
  }

  /**
   * Resolves upload constraints for a StagedUploadTarget enum value.
   *
   * @param string $target
   *   The GraphQL enum name. Must map to StagedUploadTarget with a destination
   *   directive.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   Resolver info; the schema is used to read enum directives.
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   Normalized constraints for clients (size, extensions, image bounds).
   */
  public function resolve(string $target, ResolveInfo $info): ResolvedUploadConstraints {
    return $this->uploadConstraintResolver->resolve(
      $this->getDestinationFromEnum($info->schema, $target)
    );
  }

}
