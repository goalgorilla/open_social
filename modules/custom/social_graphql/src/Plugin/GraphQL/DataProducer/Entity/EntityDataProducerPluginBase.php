<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bass class for Open Social entity data producers dealing with connections.
 */
class EntityDataProducerPluginBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The GraphQL entity buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected $graphqlEntityBuffer;

  /**
   * The GraphQL entity UUID buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer
   */
  protected $graphqlEntityUuidBuffer;

  /**
   * The GraphQL entity revision buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer
   */
  protected $graphqlEntityRevisionBuffer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity'),
      $container->get('graphql.buffer.entity_uuid'),
      $container->get('graphql.buffer.entity_revision')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer $graphqlEntityUuidBuffer
   *   The GraphQL entity uuid buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $graphqlEntityRevisionBuffer
   *   The GraphQL entity revision buffer.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityBuffer $graphqlEntityBuffer,
    EntityUuidBuffer $graphqlEntityUuidBuffer,
    EntityRevisionBuffer $graphqlEntityRevisionBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->graphqlEntityBuffer = $graphqlEntityBuffer;
    $this->graphqlEntityUuidBuffer = $graphqlEntityUuidBuffer;
    $this->graphqlEntityRevisionBuffer = $graphqlEntityRevisionBuffer;
  }

}
