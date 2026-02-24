<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\CreateEventInput as CreateEventInputWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms raw GraphQL input into a validated CreateEventInput object.
 *
 * This DataProducer serves as a bridge between the raw input array from the
 * GraphQL request and the typed, validated CreateEventInput wrapper class.
 * It handles dependency injection of Drupal services that the wrapper needs.
 *
 * @DataProducer(
 *   id = "create_event_input",
 *   name = @Translation("Create Event Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated CreateEventInput wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("CreateEventInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class CreateEventInput extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * Constructs a CreateEventInput.
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Transforms raw GraphQL input into a CreateEventInput wrapper object.
   *
   * @param array $input
   *   The raw input array from the GraphQL mutation request.
   *
   * @return \Drupal\social_event\Wrappers\Input\CreateEventInput
   *   A CreateEventInput object populated with the input values and ready
   *   for validation.
   */
  public function resolve(array $input): CreateEventInputWrapper {
    $event_input = new CreateEventInputWrapper(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->currentUser,
    );
    $event_input->setValues($input);
    return $event_input;
  }

}
