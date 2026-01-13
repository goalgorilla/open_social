<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_topic\Wrappers\Input\DeleteTopicInput as DeleteTopicInputWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms raw GraphQL input into a validated DeleteTopicInput object.
 *
 * This DataProducer is a necessary component in the GraphQL mutation pipeline.
 * It serves as a bridge between the raw input array from the GraphQL request
 * and the typed, validated DeleteTopicInput wrapper class.
 *
 * Why this DataProducer exists:
 * - Dependency injection: DeleteTopicInput requires Drupal services
 *   (EntityTypeManager, EntityRepository, AccountInterface) that must be
 *   injected via the container. GraphQL DataProducers provide the mechanism
 *   to inject these dependencies.
 * - Separation of concerns: This DataProducer handles the transformation
 *   from raw array to validated object, while DeleteTopic handles the
 *   business logic of actually deleting the topic.
 * - GraphQL architecture: The GraphQL resolver pipeline requires a
 *   DataProducer to transform data between stages. This follows the
 *   standard pattern where mutations use a two-stage approach:
 *   1. Input validation/transformation (this DataProducer)
 *   2. Business logic execution (DeleteTopic DataProducer)
 *
 * Usage in the mutation resolver chain:
 * @code
 * Mutation::deleteTopic
 *   -> delete_topic_input (this DataProducer)
 *     -> transforms array to DeleteTopicInput
 *   -> delete_topic (DeleteTopic DataProducer)
 *     -> receives DeleteTopicInput and executes mutation
 * @endcode
 *
 * @DataProducer(
 *   id = "delete_topic_input",
 *   name = @Translation("Delete Topic Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated DeleteTopicInput wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("DeleteTopicInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class DeleteTopicInput extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a DeleteTopicInput.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The Drupal entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    AccountProxyInterface $current_user,
    EntityRepositoryInterface $entity_repository,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Transforms raw GraphQL input into a DeleteTopicInput wrapper object.
   *
   * This method instantiates a DeleteTopicInput object with all required
   * Drupal services injected, then populates it with the raw input values
   * from the GraphQL request. The DeleteTopicInput object will perform
   * validation when its validate() method is called by the DeleteTopic
   * DataProducer.
   *
   * @param array $input
   *   The raw input array from the GraphQL mutation request, containing
   *   fields like 'id' and 'clientMutationId'.
   *
   * @return \Drupal\social_topic\Wrappers\Input\DeleteTopicInput
   *   A DeleteTopicInput object populated with the input values and ready
   *   for validation. Note that validation is performed later by the
   *   DeleteTopic DataProducer, not in this method.
   */
  public function resolve(array $input): DeleteTopicInputWrapper {
    $topic_input = new DeleteTopicInputWrapper(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->currentUser
    );
    $topic_input->setValues($input);
    return $topic_input;
  }

}
