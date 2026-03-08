<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\CreateEventInput as CreateEventInputWrapper;
use Drupal\social_group_flexible_group\Service\GroupInputValidationService;
use Drupal\social_organization\Service\OrganizationInputValidationService;
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
   * Constructs a CreateEventInput.
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
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $countryRepository
   *   The country repository for validating address country codes.
   * @param \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null $groupInputValidationService
   *   The group input validation service.
   * @param \Drupal\social_organization\Service\OrganizationInputValidationService|null $organizationInputValidationService
   *   The organization input validation service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected AccountProxyInterface $currentUser,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CountryRepositoryInterface $countryRepository,
    protected ?GroupInputValidationService $groupInputValidationService = NULL,
    protected ?OrganizationInputValidationService $organizationInputValidationService = NULL,
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
    $group_validation_service = NULL;
    if ($container->get('module_handler')->moduleExists('social_group_flexible_group')) {
      $group_validation_service = $container->get('social_group_flexible_group.group_input_validation');
    }
    $organization_validation_service = $container->get('module_handler')->moduleExists('social_organization')
      ? $container->get('social_organization.organization_input_validation')
      : NULL;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('address.country_repository'),
      $group_validation_service,
      $organization_validation_service,
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
      $this->countryRepository,
      $this->groupInputValidationService,
      $this->organizationInputValidationService,
    );
    $event_input->setValues($input);
    return $event_input;
  }

}
