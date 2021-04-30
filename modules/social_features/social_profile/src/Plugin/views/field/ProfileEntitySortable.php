<?php

namespace Drupal\social_profile\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager);
    $this->joinManager = $join_manager;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      // If we want to sort on the profile name, add the correct alias.
      if ($this->table === 'profile' && $this->field === 'profile_entity_sortable') {
        $definition = [
          'table' => 'profile__profile_name',
          'field' => 'entity_id',
          'left_table' => $this->relationship,
          'left_field' => 'profile_id',
        ];

        $join = $this->joinManager->createInstance('standard', $definition);
        $this->query->addRelationship($definition['table'], $join, $this->relationship);

        $this->field_alias = $definition['table'] . '.profile_name_value';
      }
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
      $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
    }
  }

}
