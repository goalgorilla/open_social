<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\DeleteEventInput as DeleteEventInputWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms raw GraphQL input into a validated DeleteEventInput object.
 *
 * This DataProducer is a necessary component in the GraphQL mutation pipeline.
 * It serves as a bridge between the raw input array from the GraphQL request
 * and the typed, validated DeleteEventInput wrapper class.
 *
 * @DataProducer(
 *   id = "delete_event_input",
 *   name = @Translation("Delete Event Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated DeleteEventInput wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("DeleteEventInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class DeleteEventInput extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a DeleteEventInput.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * Transforms raw GraphQL input into a DeleteEventInput wrapper object.
   *
   * @param array $input
   *   The raw input array from the GraphQL mutation request, containing
   *   fields like 'id' and 'clientMutationId'.
   *
   * @return \Drupal\social_event\Wrappers\Input\DeleteEventInput
   *   A DeleteEventInput object populated with the input values and ready
   *   for validation. Note that validation is performed later by the
   *   DeleteEvent DataProducer, not in this method.
   */
  public function resolve(array $input): DeleteEventInputWrapper {
    $event_input = new DeleteEventInputWrapper(
      $this->entityTypeManager,
      $this->currentUser
    );
    $event_input->setValues($input);
    return $event_input;
  }

}
