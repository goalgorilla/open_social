<?php

namespace Drupal\social_profile\Plugin\views\field;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\RenderedEntity;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to sort rendered profile entity in views.
 *
 * Sorts profile entities in views based on the name of the user and the access
 * levels of the current viewer.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("profile_entity_sortable")
 */
class ProfileEntitySortable extends RenderedEntity {

  /**
   * The Views join plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an GroupContentToEntityBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    EntityRepositoryInterface $entity_repository,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ViewsHandlerManager $join_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $entity_repository, $entity_display_repository);
    $this->joinManager = $join_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.views.join'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      // If we want to sort on the profile name, add the correct alias.
      if ($this->table === 'profile' && $this->field === 'profile_entity_sortable') {
        $view_any = $this->currentUser->hasPermission('view any profile fields') || $this->currentUser->hasPermission('view any profile profile fields');
        $view_community = $this->currentUser->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile fields") || $this->currentUser->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields");

        // Add the joins to the fields containing profile name information.
        $name_fields = [
          'field_profile_first_name',
          'field_profile_last_name',
          'field_profile_nick_name',
        ];
        $visibility_fields = [];
        foreach ($name_fields as $name_field) {
          $definition = [
            'table' => 'profile__' . $name_field,
            'field' => 'entity_id',
            'left_table' => $this->relationship,
            'left_field' => 'profile_id',
          ];

          $join = $this->joinManager->createInstance('standard', $definition);
          $this->query->addRelationship($definition['table'], $join, $this->relationship);

          // If the user can't see all fields then we must add joins to the
          // tables containing the visibility data.
          if (!$view_any) {
            $visibility_fields[$name_field] = $visibility_field = $this->getVisibilityFieldName($name_field);

            $definition = [
              'table' => 'profile__' . $visibility_field,
              'field' => 'entity_id',
              'left_table' => $this->relationship,
              'left_field' => 'profile_id',
            ];

            $join = $this->joinManager->createInstance('standard', $definition);
            $this->query->addRelationship($definition['table'], $join, $this->relationship);
          }
        }

        // Add the join to the table containing the username.
        $definition = [
          'table' => 'users_field_data',
          'field' => 'uid',
          'left_table' => $this->relationship,
          'left_field' => 'uid',
        ];

        $join = $this->joinManager->createInstance('standard', $definition);
        $this->query->addRelationship($definition['table'], $join, $this->relationship);

        // The display for this field in views is the user's display name. This
        // is controlled by various permissions and privacy settings in
        // social_user_user_format_name_alter. This runs in code so we don't
        // have access to its value in the database. For sorting we try to apply
        // the default Open Social logic to sort based on what we expect the
        // outcome to be.
        //
        // We'll craft the following two possible sort orders:
        // - First name + Last name + Nickname + Username (If
        //   limit_search_and_mention is enabled and user can bypass this or
        //   limit_search_and_mention is disabled)
        // - Nickname + First name + Last name + Username (If
        //   limit_search_and_mention is enabled, protecting the first name and
        //   last name by the nickname but falling back to real name in case
        //   nickname is NULL)
        //
        // Username is added to both sort orders in case a user hasn't filled
        // out any of the other profile fields.
        // Concatenation is used because we want to sort by the expected string
        // shown to the user which could be just a last name, so the sort field
        // is treated as a single string containing whatever is filled in.
        //
        // While the order is dictated by the limit_search_and_mention setting
        // with the related permission, the visibility of individual fields
        // within that order is dictated by profile field settings.
        //
        // We don't handle the case where limit_search_and_mention protects
        // the users real name with a nickname but the user has removed access
        // for others to their nickname. In such a case their full name is
        // visible anyway. This can be solved by forcing a nickname to be
        // public but that's out of the scope of the work being done here.
        $limit_search_and_mention = \Drupal::config('social_profile_privacy.settings')
          ->get('limit_search_and_mention');
        if ($view_any) {
          if (!$limit_search_and_mention || $this->currentUser->hasPermission('social profile privacy always show full name')) {
            $sort = "
              TRIM(CONCAT(
                COALESCE(profile__field_profile_first_name.field_profile_first_name_value, ''),
                ' ',
                COALESCE(profile__field_profile_last_name.field_profile_last_name_value, ''),
                ' ',
                COALESCE(profile__field_profile_nick_name.field_profile_nick_name_value, ''),
                ' ',
                users_field_data.name
              ))
            ";
          }
          else {
            $sort = "
              TRIM(CONCAT(
                COALESCE(profile__field_profile_nick_name.field_profile_nick_name_value, ''),
                ' ',
                COALESCE(profile__field_profile_first_name.field_profile_first_name_value, ''),
                ' ',
                COALESCE(profile__field_profile_last_name.field_profile_last_name_value, ''),
                ' ',
                users_field_data.name
              ))
            ";
          }
        }
        // While the below uses variables in unfiltered SQL the value of the
        // variables comes from thirdPartySettings in FieldStorageConfig
        // entities which is system controlled and not user input so this is
        // still safe and not a SQL injection opportunity.
        else {
          $first_name_visibility = "profile__{$visibility_fields['field_profile_first_name']}.{$visibility_fields['field_profile_first_name']}_value";
          $first_name_profile = "profile__field_profile_first_name.field_profile_first_name_value";
          $last_name_visibility = "profile__{$visibility_fields['field_profile_last_name']}.{$visibility_fields['field_profile_last_name']}_value";
          $last_name_profile = "profile__field_profile_last_name.field_profile_last_name_value";
          $nick_name_visibility = "profile__{$visibility_fields['field_profile_nick_name']}.{$visibility_fields['field_profile_nick_name']}_value";
          $nick_name_profile = "profile__field_profile_nick_name.field_profile_nick_name_value";

          $allowed_visibility = $view_community
            ? "'" . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . "', '" . SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC . "'"
            : "'" . SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC . "'";

          // Below is the same as for any field except that the field is set to
          // NULL based on the configured visibility value.
          if (!$limit_search_and_mention || $this->currentUser->hasPermission('social profile privacy always show full name')) {
            $sort = "
              TRIM(CONCAT(
                COALESCE(IF($first_name_visibility IN ($allowed_visibility), $first_name_profile, NULL), ''),
                ' ',
                COALESCE(IF($last_name_visibility IN ($allowed_visibility), $last_name_profile, NULL), ''),
                ' ',
                COALESCE(IF($nick_name_visibility IN ($allowed_visibility), $nick_name_profile, NULL), ''),
                ' ',
                users_field_data.name
              ))
            ";
          }
          else {
            $sort = "
              TRIM(CONCAT(
                COALESCE(IF($nick_name_visibility IN ($allowed_visibility), $nick_name_profile, NULL), ''),
                ' ',
                COALESCE(IF($first_name_visibility IN ($allowed_visibility), $first_name_profile, NULL), ''),
                ' ',
                COALESCE(IF($nick_name_visibility IN ($allowed_visibility), $nick_name_profile, NULL), ''),
                ' ',
                users_field_data.name
              ))
            ";
          }
        }

        $this->field_alias = 'profile_sort_by_name';
        $this->query->addField(
          NULL,
          $sort,
          $this->field_alias
        );

        // Since fields should always have themselves already added, just
        // add a sort on the field.
        $params = $this->options['group_type'] !== 'group' ? ['function' => $this->options['group_type']] : [];
        $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
      }
    }
  }

  /**
   * Get the name of the field that stores visibility data for a field.
   *
   * @param string $for_field
   *   The field to get the visibility data field for.
   *
   * @return string
   *   The name of the field that stores visibility data.
   */
  private function getVisibilityFieldName(string $for_field) : string {
    // @todo Dependency injection.
    /** @var \Drupal\field\FieldStorageConfigInterface|NULL $field_storage_config */
    $field_storage_config = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->load("profile.${for_field}");

    if ($field_storage_config === NULL) {
      throw new \InvalidArgumentException("Field '${for_field}' does not exist on profile entity.");
    }

    $visibility_field = $field_storage_config->getThirdPartySetting('social_profile', 'visibility_stored_by');

    if ($visibility_field === NULL) {
      throw new \RuntimeException("Field '${for_field}' does not have a visibility field configured.");
    }

    return $visibility_field;
  }

}
