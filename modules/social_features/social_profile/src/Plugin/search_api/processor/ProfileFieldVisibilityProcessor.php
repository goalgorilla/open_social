<?php

namespace Drupal\social_profile\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\social_profile\FieldManager;
use Drupal\social_profile\Plugin\search_api\processor\Property\ProfileVisibilityControlledFieldProperty;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures visibility permissions are applied to profile searches.
 *
 * Although a lot of this processor is entity agnostic, we make some specific
 * assumptions specifically about the `profile` entity and the `profile` bundle
 * of that entity. That's what the profile's visibility system was originally
 * designed for.
 *
 * The processor works by creating a visibility field per managed profile field
 * (the original field is left intact for sorting rules as an "aggregate").
 * Only the field that's created for the configured visibility and the original
 * field will be filled with the value for the field. The query processor will
 * then only allow users to search in the visibility field copies that they have
 * access to and never in the original field.
 *
 * This implementation makes the trade-off of search index size (since fields
 * are duplicated 4-fold, even if half of them are empty) for performance
 * (assuming that SOLR is good at doing full-text search in a combined set of
 * fields and less good in processing SQL-like combinations of AND/OR queries).
 *
 * @SearchApiProcessor(
 *   id = "social_profile_field_visibility_processor",
 *   label = @Translation("Profile Field Visibility Processor"),
 *   description = @Translation("Adjusts search queries to ensure that searches only happen for field values that a user is allowed to see (e.g. hiding private fields for users without permissions to view them)."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = 50,
 *     "preprocess_query" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
final class ProfileFieldVisibilityProcessor extends ProcessorPluginBase {

  /**
   * The user viewing the search results being processed.
   */
  protected AccountInterface $currentUser;

  /**
   * Open Social's modified field manager.
   */
  protected FieldManager $fieldManager;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  protected const VISIBILITIES = [
    SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC,
    SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY,
    SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE,
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor
      ->setCurrentUser($container->get('current_user'))
      ->setFieldManager($container->get('social_profile.field_manager'))
      ->setEntityTypeManager($container->get("entity_type.manager"));

    return $processor;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $current_user) : self {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Set the field manager.
   *
   * @param \Drupal\social_profile\FieldManager $field_manager
   *   The Open Social implementation of the field manager.
   *
   * @return $this
   */
  public function setFieldManager(FieldManager $field_manager) : self {
    $this->fieldManager = $field_manager;
    return $this;
  }

  /**
   * Set the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) : self {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    return $index->isValidDatasource("entity:profile");
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    // Our fields pull the information from the actually indexed fields, so our
    // own field is datasource independent.
    if ($datasource === NULL) {
      $definition = [
        'label' => $this->t('Profile Visibility Controlled field'),
        'description' => $this->t('A field whose value is controlled by visibility field settings.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        // We need to support fields that are multi-valued so we err on the side
        // of caution.
        'is_list' => TRUE,
      ];
      $properties['profile_visibility_controlled_field'] = new ProfileVisibilityControlledFieldProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() : void {
    $managed_fields = $this->fieldManager->getManagedProfileFieldDefinitions();
    $processors = $this->getIndex()->getProcessors();

    // Add the visibility field copies of our profile fields as hidden fields
    // to our index.
    foreach ($this->getIndex()->getFields() as $index_field_name => $index_field) {
      // We don't care about non-profile fields except for fields we created
      // ourselves.
      if ($index_field->getDatasource()?->getEntityTypeId() !== "profile" && $index_field->getPropertyPath() !== "profile_visibility_controlled_field") {
        continue;
      }

      // For fields we create ourselves, check that the main field is still
      // managed otherwise remove it from the index.
      if ($index_field->getPropertyPath() === "profile_visibility_controlled_field") {
        [, $property_path] = Utility::splitCombinedId($index_field->getConfiguration()["value_property_path"]);
        [$field_name] = explode(":", $property_path);
        if (!isset($managed_fields[$field_name])) {
          $index_field->setIndexedLocked(FALSE);
          $this->getIndex()->removeField($index_field_name);
        }
        continue;
      }

      // If this is not a managed profile field we don't need to create copies
      // in the index.
      [$field_name] = explode(":", $index_field->getPropertyPath());
      if (!isset($managed_fields[$field_name])) {
        continue;
      }

      foreach (self::VISIBILITIES as $visibility) {
        $field_id = "${index_field_name}_${visibility}";
        $field = $this->index->getField($field_id);

        if ($field === NULL) {
          $properties = $this->index->getPropertyDefinitions(NULL);
          $property = $this->getFieldsHelper()
            ->retrieveNestedProperty($properties, "profile_visibility_controlled_field");
          assert($property instanceof ProfileVisibilityControlledFieldProperty, "Incorrect getPropertyDefinitions implementation for " . __CLASS__);

          $field = $this->getFieldsHelper()
            ->createFieldFromProperty($this->index, $property, NULL, "profile_visibility_controlled_field", $field_id, $index_field->getType())
            ->setConfiguration([
              'value_property_path' => Utility::createCombinedId($index_field->getDatasourceId() ?? "", $index_field->getPropertyPath()),
              'visibility_property_path' => Utility::createCombinedId($index_field->getDatasourceId() ?? "", $this->fieldManager::getVisibilityFieldName($managed_fields[$field_name]) ?? ""),
              'property_visibility' => $visibility,
            ]);
          $this->index->addField($field);
        }

        // If the index field's type has changed we must change the type too.
        // The field type must be unlocked to do that but will be re-locked
        // later.
        if ($index_field->getType() !== $field->getType()) {
          $field
            ->setTypeLocked(FALSE)
            ->setType($index_field->getType());
        }

        // We also update existing fields in case the dependant configuration
        // was changed.
        $field
          ->setDatasourceId(NULL)
          ->setBoost($index_field->getBoost())
          ->setLabel("Visibility Value » $visibility » " . $index_field->getLabel())
          ->setHidden(FALSE)
          ->setIndexedLocked()
          ->setTypeLocked();
      }

      // For this field check in which processors it's configured to be and then
      // add all our new per-visibility fields there too.
      foreach ($processors as $processor) {
        $processor_configuration = $processor->getConfiguration();
        if (!isset($processor_configuration['fields'])) {
          continue;
        }
        if (in_array($index_field_name, $processor_configuration['fields'], TRUE)) {
          foreach (self::VISIBILITIES as $visibility) {
            if (!in_array("${index_field_name}_${visibility}", $processor_configuration['fields'], TRUE)) {
              $processor_configuration['fields'][] = "${index_field_name}_${visibility}";
            }
          }
          $processor->setConfiguration($processor_configuration);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) : void {
    // We need the owner of the entity (profile) to determine whether the user
    // is allowed to change the visibility settings, so we can't process
    // non-entity items.
    $entity = $item->getOriginalObject()?->getValue();
    if (!$entity instanceof EntityOwnerInterface || !$entity instanceof EntityInterface) {
      return;
    }
    $owner = $entity->getOwner();

    // Create a default entity with the proper bundle (if supported) to get
    // fallback values for visibility if needed.
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle_key = $entity->getEntityType()->getKey('bundle') ?: NULL;
    $entity_bundle_value = $entity->bundle();
    $default_entity = $this->entityTypeManager->getStorage($entity_type)->create($entity_bundle_key !== NULL ? [$entity_bundle_key => $entity_bundle_value] : []);
    assert($default_entity instanceof FieldableEntityInterface);

    // The first time we go through all possible fields on the index.
    $fields = $this->index->getFields();
    $visibility_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'profile_visibility_controlled_field');
    $required_properties_by_datasource = [
      NULL => [],
      $item->getDatasourceId() => [],
    ];

    // We first figure out all the properties we might need to fetch.
    // This is the actual indexed value and the visibility value for each field.
    foreach ($visibility_fields as $field) {
      $combined_id = $field->getConfiguration()["value_property_path"];
      [$datasource_id, $property_path] = Utility::splitCombinedId($combined_id);
      $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
      $combined_id = $field->getConfiguration()["visibility_property_path"];
      [$datasource_id, $property_path] = Utility::splitCombinedId($combined_id);
      $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
    }

    // Now we get all the values in one go from our item.
    $property_values = $this->getFieldsHelper()
      ->extractItemValues([$item], $required_properties_by_datasource)[0];

    // Now we loop through the fields actually on the item.
    $visibility_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'profile_visibility_controlled_field');
    foreach ($visibility_fields as $field) {
      $value_path = $field->getConfiguration()["value_property_path"];
      $visibility_path = $field->getConfiguration()["visibility_property_path"];
      $field_visibility = $field->getConfiguration()["property_visibility"];
      [, $visibility_field_name] = Utility::splitCombinedId($visibility_path);

      // The actual visibility configured on the profile if the user is allowed
      // to change it or the default visibility configured by the site manager
      // otherwise.
      if ($owner->hasPermission("edit own $visibility_field_name $entity_bundle_value $entity_type field") || $owner->hasPermission("edit any $visibility_field_name $entity_bundle_value $entity_type field")) {
        $visibility = $property_values[$visibility_path][0] ?? [];
      }
      elseif ($default_entity->hasField($visibility_field_name) && !$default_entity->get($visibility_field_name)->isEmpty()) {
        $visibility = $default_entity->get($visibility_field_name)->value;
      }
      else {
        $visibility = NULL;
      }

      // We always clean out the value of the field to ensure that changing the
      // visibility will update the search index.
      $field->setValues([]);
      // In case the configured visibility matches the visibility we're indexing
      // for then we copy the values to our field.
      if ($visibility === $field_visibility) {
        foreach ($property_values[$value_path] ?? [] as $value) {
          // We must use `addValue` here rather than setting `setValues`
          // directly outside the loop because `addValue` does value processing
          // of the raw values we extracted from our entity.
          $field->addValue($value);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) : void {
    // If the query is opted out of access checking then we don't make any
    // adjustments.
    if ($query->getOption('search_api_bypass_access')) {
      return;
    }

    $access_user = $this->getAccessAccount($query);
    if ($access_user->hasPermission("view any profile fields")) {
      return;
    }
    // This processor only works for the `profile` bundle for which we manage
    // field access, so we respect its bundle permission too.
    if ($access_user->hasPermission("view any profile profile fields")) {
      return;
    }

    $can_view_community = $access_user->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile fields") || $access_user->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields");

    // Get all fields available to search in.
    $all_fields = $query->getFulltextFields() ?? $query->getIndex()->getFulltextFields();
    $field_definitions = $this->index->getFields();

    $managed_fields = $this->fieldManager->getManagedProfileFieldDefinitions();

    $search_fields = [];
    foreach ($all_fields as $index_field_name) {
      // If for some reason the full text field is a special field then we don't
      // care about it.
      if (!isset($field_definitions[$index_field_name])) {
        $search_fields[] = $index_field_name;
        continue;
      }

      // Only add visibility specific fields that the user has access to.
      if ($field_definitions[$index_field_name]->getPropertyPath() === "profile_visibility_controlled_field") {
        [, $property_path] = Utility::splitCombinedId($field_definitions[$index_field_name]->getConfiguration()["value_property_path"]);
        [$profile_field_name] = Utility::splitPropertyPath($property_path, FALSE);
        $visibility = $field_definitions[$index_field_name]->getConfiguration()["property_visibility"];
        assert(in_array($visibility, self::VISIBILITIES, TRUE), "Unsupported visibility for search field created with 'profile_visibility_controlled_field' property path.");
        assert(isset($managed_fields[$profile_field_name]), "Field '$profile_field_name' was indexed as managed field in '$index_field_name' but is not a managed profile field.");

        // If the field this property is based on then we disallow searching in
        // it.
        if (!$managed_fields[$profile_field_name]->status()) {
          continue;
        }

        if ($visibility === SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC) {
          $search_fields[] = $index_field_name;
          continue;
        }

        if ($visibility === SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY && $can_view_community) {
          $search_fields[] = $index_field_name;
          continue;
        }

        if ($access_user->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${profile_field_name} profile profile fields")) {
          $search_fields[] = $index_field_name;
        }
      }
      else {
        $entity_type_id = $field_definitions[$index_field_name]->getDatasource()?->getEntityTypeId();
        // We only care about fields on entities.
        if ($entity_type_id === NULL) {
          $search_fields[] = $index_field_name;
          continue;
        }

        [$entity_field_name] = Utility::splitPropertyPath($field_definitions[$index_field_name]->getPropertyPath(), FALSE);

        // If the property this field is based on is not managed, or it exists
        // on a different entity than we're managing then we don't care about
        // it.
        if (!isset($managed_fields[$entity_field_name]) || $managed_fields[$entity_field_name]->getTargetEntityTypeId() !== $entity_type_id) {
          $search_fields[] = $index_field_name;
        }

        // In all other cases we don't want to allow a user to search for it
        // since they should be using one of the visibility specific fields.
      }
    }

    // Update the query to use only our approved search fields.
    $query->setFulltextFields($search_fields);
  }

  /**
   * Retrieves the account object to use for access checks for the query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to check access for.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account for which to check access to returned or displayed entities.
   */
  private function getAccessAccount(QueryInterface $query) : AccountInterface {
    $account = $query->getOption('search_api_access_account', $this->currentUser);
    if (is_numeric($account)) {
      $account = User::load($account);
    }
    assert($account !== NULL, "Invalid UID provided for `search_api_access_content` option.");
    return $account;
  }

}
