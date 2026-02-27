<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\DeleteEventInput;
use Drupal\social_event\Wrappers\Payload\DeleteEventPayload;
use Drupal\social_graphql\GraphQL\Violation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes an existing event.
 *
 * @DataProducer(
 *   id = "delete_event",
 *   name = @Translation("Delete Event"),
 *   description = @Translation("Deletes an existing event."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("DeleteEventPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "DeleteEventInput",
 *     ),
 *   }
 * )
 */
class DeleteEvent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  /**
   * DeleteEvent constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setLoggerFactory($logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
    );
  }

  /**
   * Deletes an existing event.
   *
   * @param \Drupal\social_event\Wrappers\Input\DeleteEventInput $input
   *   The information for the event to delete.
   *
   * @return \Drupal\social_event\Wrappers\Payload\DeleteEventPayload
   *   The GraphQL event response.
   */
  public function resolve(DeleteEventInput $input): DeleteEventPayload {
    $payload = new DeleteEventPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Get the event to delete.
    $node = $input->getEvent();

    // Delete the event. Drupal's entity delete triggers hooks and cascade
    // deletions (comments, enrollments, etc.) according to entity behavior.
    try {
      $node->delete();
    }
    catch (EntityStorageException $e) {
      $this->getLogger('social_event')->error('Event delete failed in GraphQL Delete Event Mutation: @message', [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);
      $payload->addViolation(new Violation('EVENT_DELETE_FAILED'));
      return $payload;
    }

    return $payload;
  }

}
