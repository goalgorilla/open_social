<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_group_flexible_group\Service\GroupInputValidationService;
use Drupal\social_organization\Service\OrganizationInputValidationService;
use Drupal\social_tagging\Service\ContentTagInputValidationServiceInterface;
use Drupal\social_topic\Wrappers\Input\UpdateTopicInput as UpdateTopicInputWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms raw GraphQL input into a validated UpdateTopicInput object.
 *
 * This DataProducer is a necessary component in the GraphQL mutation pipeline.
 * It serves as a bridge between the raw input array from the GraphQL request
 * and the typed, validated UpdateTopicInput wrapper class.
 *
 * Why this DataProducer exists:
 * - Dependency injection: UpdateTopicInput requires Drupal services
 *   (EntityTypeManager, EntityRepository, AccountInterface)
 *   that must be injected via the container. GraphQL DataProducers provide
 *   the mechanism to inject these dependencies.
 * - Separation of concerns: This DataProducer handles the transformation
 *   from raw array to validated object, while UpdateTopic handles the
 *   business logic of actually updating the topic.
 * - GraphQL architecture: The GraphQL resolver pipeline requires a
 *   DataProducer to transform data between stages. This follows the
 *   standard pattern where mutations use a two-stage approach:
 *   1. Input validation/transformation (this DataProducer)
 *   2. Business logic execution (UpdateTopic DataProducer)
 *
 * Usage in the mutation resolver chain:
 * @code
 * Mutation::updateTopic
 *   -> update_topic_input (this DataProducer)
 *     -> transforms array to UpdateTopicInput
 *   -> update_topic (UpdateTopic DataProducer)
 *     -> receives UpdateTopicInput and executes mutation
 * @endcode
 *
 * @DataProducer(
 *   id = "update_topic_input",
 *   name = @Translation("Update Topic Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated UpdateTopicInput wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("UpdateTopicInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class UpdateTopicInput extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an UpdateTopicInput.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The Drupal entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null $groupInputValidationService
   *   The group input validation service.
   * @param \Drupal\social_organization\Service\OrganizationInputValidationService|null $organizationInputValidationService
   *   The organization input validation service.
   * @param \Drupal\social_tagging\Service\ContentTagInputValidationServiceInterface|null $contentTagInputValidationService
   *   The content tag input validation service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected AccountProxyInterface $currentUser,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?GroupInputValidationService $groupInputValidationService = NULL,
    protected ?OrganizationInputValidationService $organizationInputValidationService = NULL,
    protected ?ContentTagInputValidationServiceInterface $contentTagInputValidationService = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $module_handler = $container->get('module_handler');
    $group_validation_service = $module_handler->moduleExists('social_group_flexible_group')
      ? $container->get('social_group_flexible_group.group_input_validation')
      : NULL;
    $organization_validation_service = $module_handler->moduleExists('social_organization')
      ? $container->get('social_organization.organization_input_validation')
      : NULL;
    $content_tag_validation_service = $module_handler->moduleExists('social_tagging')
      ? $container->get('social_tagging.content_tag_input_validation')
      : NULL;
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $group_validation_service,
      $organization_validation_service,
      $content_tag_validation_service,
    );
  }

  /**
   * Transforms raw GraphQL input into an UpdateTopicInput wrapper object.
   *
   * This method instantiates an UpdateTopicInput object with all required
   * Drupal services injected, then populates it with the raw input values
   * from the GraphQL request. The UpdateTopicInput object will perform
   * validation when its validate() method is called by the UpdateTopic
   * DataProducer.
   *
   * @param array $input
   *   The raw input array from the GraphQL mutation request, containing
   *   fields like 'id', 'title', 'type', 'visibility', etc.
   *
   * @return \Drupal\social_topic\Wrappers\Input\UpdateTopicInput
   *   An UpdateTopicInput object populated with the input values and ready
   *   for validation. Note that validation is performed later by the
   *   UpdateTopic DataProducer, not in this method.
   */
  public function resolve(array $input): UpdateTopicInputWrapper {
    $topic_input = new UpdateTopicInputWrapper(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->currentUser,
      $this->groupInputValidationService,
      $this->organizationInputValidationService,
      $this->contentTagInputValidationService,
    );
    $topic_input->setValues($input);
    return $topic_input;
  }

}
