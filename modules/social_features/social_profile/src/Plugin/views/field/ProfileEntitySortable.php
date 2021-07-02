<?php

namespace Drupal\social_profile\Plugin\views\field;

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
    ViewsHandlerManager $join_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager);
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
        /** @var \Drupal\views\Plugin\views\query\Sql $query */
        $query = $this->query;

        // Get a field list that will be used for sorting.
        $order_by_fields = $this->orderByFields();

        // Add relationship for necessary fields.
        foreach ($order_by_fields as $sort_field) {
          $definition = [
            'table' => 'profile__' . $sort_field,
            'field' => 'entity_id',
            'left_table' => $this->relationship,
            'left_field' => 'profile_id',
          ];

          $join = $this->joinManager->createInstance('standard', $definition);
          $query->addRelationship($definition['table'], $join, $this->relationship);
        }

        // If we have more than one field for sort then use Firstname, Lastname,
        // and Nickname fields.
        if (count($order_by_fields) > 1) {
          $this->field_alias = 'profile_full_name';
          // We will have different expressions depending on is the Nickname
          // field provided or not.
          // Members will be sort by next queue:
          // - Nickname
          // - Firstname + Lastname (if Nickname is NULL)
          // - Firstname (if Nickname and Lastname are NULL)
          // - Lastname (if Nickname and Firstname are NULL)
          // - Username (if all Name fields are NULL)
          $field = in_array('field_profile_nick_name', $order_by_fields) ?
            "CASE WHEN
              profile__field_profile_nick_name.field_profile_nick_name_value IS NOT NULL
            THEN
              TRIM(profile__field_profile_nick_name.field_profile_nick_name_value)
            WHEN
              (profile__field_profile_nick_name.field_profile_nick_name_value IS NULL) AND ((profile__field_profile_first_name.field_profile_first_name_value IS NOT NULL) OR (profile__field_profile_last_name.field_profile_last_name_value IS NOT NULL))
            THEN
              CONCAT(TRIM(COALESCE(profile__field_profile_first_name.field_profile_first_name_value, '')), ' ', TRIM(COALESCE(profile__field_profile_last_name.field_profile_last_name_value, '')))
            ELSE
              TRIM(" . $this->view->relationship['profile']->tableAlias . ".name)
            END" :
            "CASE WHEN
              ((profile__field_profile_first_name.field_profile_first_name_value IS NOT NULL) OR (profile__field_profile_last_name.field_profile_last_name_value IS NOT NULL))
            THEN
              CONCAT(TRIM(COALESCE(profile__field_profile_first_name.field_profile_first_name_value, '')), ' ', TRIM(COALESCE(profile__field_profile_last_name.field_profile_last_name_value, '')))
            ELSE
              TRIM(" . $this->view->relationship['profile']->tableAlias . ".name)
            END";
          $query->addField(
            NULL,
            $field,
            $this->field_alias
          );
        }
        // If we have only one field for sort then use the Profile name field.
        elseif (count($order_by_fields) === 1) {
          $this->field_alias = $definition['table'] . '.profile_name_value';
        }
      }
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
      $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
    }
  }

  /**
   * Get the list of fields that will be used for sorting.
   *
   * @return string[]
   *   List of fields.
   */
  private function orderByFields(): array {
    // Set default sort fields.
    $fields = [
      'profile_name',
    ];

    // If social_profile_privacy module is not enabled then we sort users by
    // default sort field.
    if (!$this->moduleHandler->moduleExists('social_profile_privacy')) {
      return $fields;
    }

    // If the user has no access to view hidden fields then we sort users by
    // default sort fields.
    if (!$this->currentUser->hasPermission('social profile privacy view hidden fields')) {
      return $fields;
    }

    // In the case where the user has access to view hidden fields, we need to
    // sort profiles by Firstname and Lastname.
    $fields = [
      'field_profile_first_name',
      'field_profile_last_name',
    ];

    // If module social_profile_fields is enabled then also need to sort
    // profiles by Nickname.
    if ($this->moduleHandler->moduleExists('social_profile_fields')) {
      $fields[] = 'field_profile_nick_name';
    }

    return $fields;
  }

}
