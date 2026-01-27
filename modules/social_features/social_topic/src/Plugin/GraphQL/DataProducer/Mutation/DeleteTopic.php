<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_topic\Wrappers\Input\DeleteTopicInput;
use Drupal\social_topic\Wrappers\Payload\DeleteTopicPayload;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes an existing topic.
 *
 * @DataProducer(
 *   id = "delete_topic",
 *   name = @Translation("Delete Topic"),
 *   description = @Translation("Deletes an existing topic."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("DeleteTopicPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "DeleteTopicInput",
 *     ),
 *   }
 * )
 */
class DeleteTopic extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * DeleteTopic constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Deletes an existing topic.
   *
   * @param \Drupal\social_topic\Wrappers\Input\DeleteTopicInput $input
   *   The information for the topic to delete.
   *
   * @return \Drupal\social_topic\Wrappers\Payload\DeleteTopicPayload
   *   The GraphQL topic response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resolve(DeleteTopicInput $input): DeleteTopicPayload {
    $payload = new DeleteTopicPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Get the topic to delete.
    $topic = $input->getTopic();

    // Delete the topic.
    // This will trigger all Drupal hooks and cascade deletions
    // (comments, files, etc.) according to Drupal's entity deletion behavior.
    $topic->delete();

    return $payload;
  }

}
