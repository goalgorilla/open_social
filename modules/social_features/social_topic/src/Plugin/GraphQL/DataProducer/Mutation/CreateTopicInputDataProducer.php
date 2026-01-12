<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_topic\Wrappers\Input\CreateTopicInput as CreateTopicInputWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms raw GraphQL input into a validated CreateTopicInput object.
 *
 * This DataProducer is a necessary component in the GraphQL mutation pipeline.
 * It serves as a bridge between the raw input array from the GraphQL request
 * and the typed, validated CreateTopicInput wrapper class.
 *
 * Why this DataProducer exists:
 * - Dependency injection: CreateTopicInput requires Drupal services
 *   (EntityTypeManager, EntityRepository, AccountInterface, ConfigFactory)
 *   that must be injected via the container. GraphQL DataProducers provide
 *   the mechanism to inject these dependencies.
 * - Separation of concerns: This DataProducer handles the transformation
 *   from raw array to validated object, while CreateTopic handles the
 *   business logic of actually creating the topic.
 * - GraphQL architecture: The GraphQL resolver pipeline requires a
 *   DataProducer to transform data between stages. This follows the
 *   standard pattern where mutations use a two-stage approach:
 *   1. Input validation/transformation (this DataProducer)
 *   2. Business logic execution (CreateTopic DataProducer)
 *
 * Usage in the mutation resolver chain:
 * @code
 * Mutation::createTopic
 *   -> create_topic_input (this DataProducer)
 *     -> transforms array to CreateTopicInput
 *   -> create_topic (CreateTopic DataProducer)
 *     -> receives CreateTopicInput and executes mutation
 * @endcode
 *
 * @DataProducer(
 *   id = "create_topic_input",
 *   name = @Translation("Create Topic Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated CreateTopicInput wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("CreateTopicInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class CreateTopicInputDataProducer extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a CreateTopicInputDataProducer.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The Drupal entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    AccountInterface $current_user,
    EntityRepositoryInterface $entity_repository,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
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
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Transforms raw GraphQL input into a CreateTopicInput wrapper object.
   *
   * This method instantiates a CreateTopicInput object with all required
   * Drupal services injected, then populates it with the raw input values
   * from the GraphQL request. The CreateTopicInput object will perform
   * validation when its validate() method is called by the CreateTopic
   * DataProducer.
   *
   * @param array $input
   *   The raw input array from the GraphQL mutation request, containing
   *   fields like 'title', 'type', 'visibility', etc.
   *
   * @return \Drupal\social_topic\Wrappers\Input\CreateTopicInput
   *   A CreateTopicInput object populated with the input values and ready
   *   for validation. Note that validation is performed later by the
   *   CreateTopic DataProducer, not in this method.
   */
  public function resolve(array $input): CreateTopicInputWrapper {
    $topic_input = new CreateTopicInputWrapper(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->currentUser,
      $this->configFactory
    );
    $topic_input->setValues($input);
    return $topic_input;
  }

}
