<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;
use Drupal\social_search\Utility\SocialSearchApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Altering a search api query for groups.
 *
 * @SearchApiProcessor(
 *   id = "social_group_group_access",
 *   label = @Translation("Search API Social Group Access"),
 *   description = @Translation("Alter 'group' type and 'group' type access query conditions groups."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_query" = -99,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SearchApiGroupAccess extends ProcessorPluginBase {

  use SocialSearchSearchApiProcessorTrait;

  /**
   * Constructs an "SearchApiGroupAccess" object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\social_group\SocialGroupHelperService $groupHelper
   *   The group helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SocialGroupHelperService $groupHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('social_group.helper_service'),
    );
  }

  /**
   * Returns the entity type field names list should be added to the index.
   *
   * @return array
   *   The field names list with additional settings (type, etc.) associated
   *   by entity type (node, post, etc.).
   */
  public static function getIndexData(): array {
    return [
      'group' => [
        'id' => ['type' => 'integer'],
        'type' => ['type' => 'string'],
        'field_flexible_group_visibility' => ['type' => 'string'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query): void {
    /* @see \Drupal\social_search\Plugin\search_api\processor\TaggingQuery::preprocessSearchQuery() */
    $or = SocialSearchApi::findTaggedQueryConditionsGroup('social_entity_type_group_access', $query->getConditionGroup());
    if (!$or instanceof ConditionGroupInterface) {
      return;
    }

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $query->getOption('social_search_access_account');

    $type = $this->findField('entity:group', 'type');
    $group_id_field = $this->findField('entity:group', 'id');
    $visibility_field = $this->findField('entity:group', 'field_flexible_group_visibility');
    if (
      !$type instanceof FieldInterface ||
      !$group_id_field instanceof FieldInterface ||
      !$visibility_field instanceof FieldInterface
    ) {
      // The required fields don't exist in the index.
      return;
    }

    if ($account->hasPermission('manage all groups')) {
      // Current user has access to all groups.
      $or->addCondition($group_id_field->getFieldIdentifier(), 0, '<>');
      return;
    }

    // We should allow access to all group types without visibility.
    $or->addCondition($visibility_field->getFieldIdentifier(), NULL, 'IS NULL');

    // Get all group types where we have visibility field.
    $group_bundles = SocialGroupHelperService::getGroupBundlesWithVisibility();
    $group_visibilities = SocialGroupHelperService::getAvailableVisibilities();

    foreach ($group_bundles as $bundle) {
      foreach ($group_visibilities as $visibility) {
        // Make sure the user has appropriate permission.
        if (!$account->hasPermission("view $visibility $bundle group")) {
          continue;
        }

        // Add a specific tag to allow other modules to change the current
        // condition group.
        // This is useful for cases like "role_visibility", etc.
        $condition = $query->createConditionGroup(tags: ["social_entity_type_group_access:$bundle:$visibility"])
          ->addCondition($type->getFieldIdentifier(), $bundle)
          ->addCondition($visibility_field->getFieldIdentifier(), $visibility);

        if ($visibility === 'members') {
          // For "members" visibility, we need to make sure the user has
          // access only to groups with membership.
          $user_groups = $this->groupHelper->getAllGroupsForUser((int) $account->id());
          $condition->addCondition($group_id_field->getFieldIdentifier(), $user_groups ?: [0], 'IN');
        }

        $or->addConditionGroup($condition);
      }
    }
  }

}
