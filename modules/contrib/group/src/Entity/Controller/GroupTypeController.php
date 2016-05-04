<?php

/**
 * @file
 * Contains \Drupal\group\Controller\GroupTypeController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Plugin\GroupContentEnablerHelper;
use Drupal\group\Plugin\GroupContentEnablerInterface;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form for a specific group type.
 */
class GroupTypeController extends ControllerBase {

  /**
   * The group type to use in this controller.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The IDs of the content enabler plugins the group type uses.
   *
   * @var string[]
   */
  protected $installedPluginIds = [];

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupSettingsForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }
  
  /**
   * Builds an admin interface to manage the group type's group content plugins.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to build an interface for.
   */
  public function content(GroupTypeInterface $group_type) {
    $this->groupType = $group_type;
    foreach ($this->groupType->getInstalledContentPlugins() as $plugin_id => $plugin) {
      $this->installedPluginIds[] = $plugin_id;
    }

    // Render the table of available content enablers.
    $page['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $page['content'] = [
      '#type' => 'table',
      '#header' => [
        'info' => $this->t('Plugin information'),
        'provider' => $this->t('Provided by'),
        'entity_type_id' => $this->t('Applies to'),
        'status' => $this->t('Status'),
        'operations' => $this->t('Operations'),
      ],
      '#suffix' =>  $this->t('<em>* These plugins are set to be always on by their providing module.</em>'),
    ];

    foreach (GroupContentEnablerHelper::getAllContentEnablers() as $plugin_id => $plugin) {
      // If the plugin is installed on the group type, use that one instead of
      // an 'empty' version so that we may use methods on it which expect to
      // have a group type configured.
      if (in_array($plugin_id, $this->installedPluginIds)) {
        $plugin = $this->groupType->getContentPlugin($plugin_id);
      }
      $page['content'][$plugin_id] = $this->buildRow($plugin);
    }

    return $page;
  }

  /**
   * Builds a row for a content enabler plugin.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   A render array to use as a table row.
   */
  public function buildRow(GroupContentEnablerInterface $plugin) {
    // Get the plugin status.
    if (in_array($plugin->getPluginId(), $this->installedPluginIds)) {
      $status = $this->t('Installed');

      // Mark enforced plugins with an asterisk.
      if ($plugin->isEnforced()) {
        $status .= '*';
      }
    }
    else {
      $status = $this->t('Not installed');
    }

    $row = [
      'info' => [
        '#type' => 'inline_template',
        '#template' => '<div class="description"><span class="label">{{ label }}</span>{% if description %}<br/>{{ description }}{% endif %}</div>',
        '#context' => [
          'label' => $plugin->getLabel(),
        ],
      ],
      'provider' => [
        '#markup' => $this->moduleHandler->getName($plugin->getProvider())
      ],
      'entity_type_id' => [
        '#markup' => $this->entityTypeManager->getDefinition($plugin->getEntityTypeId())->getLabel()
      ],
      'status' => ['#markup' => $status],
      'operations' => $this->buildOperations($plugin),
    ];

    // Show the content enabler description if toggled on.
    if (!system_admin_compact_mode()) {
      $row['info']['#context']['description'] = $plugin->getDescription();
    }

    return $row;
  }

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   An associative array of operation links for the group type's content
   *   plugin, keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations($plugin) {
    return $plugin->getOperations() + $this->getDefaultOperations($plugin);
  }

  /**
   * Gets the group type's content plugin's default operation links.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations($plugin) {
    $operations = [];

    $plugin_id = $plugin->getPluginId();
    $installed = in_array($plugin_id, $this->installedPluginIds);

    if ($installed) {
      /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
      $group_content_type_id = $plugin->getContentTypeConfigId();
      $group_content_type = GroupContentType::load($group_content_type_id);

      $route_params = [
        'group_content_type' => $group_content_type_id,
      ];

      $operations['configure'] = [
        'title' => $this->t('Configure'),
        'url' => new Url('entity.group_content_type.edit_form', $route_params),
      ];

      if (!$plugin->isEnforced()) {
        $operations['uninstall'] = [
          'title' => $this->t('Uninstall'),
          'weight' => 99,
          'url' => new Url('entity.group_content_type.delete_form', $route_params),
        ];
      }

      $operations += field_ui_entity_operation($group_content_type);
    }
    elseif (!$plugin->isEnforced()) {
      $operations['install'] = [
        'title' => $this->t('Install'),
        'url' => new Url('entity.group_content_type.add_form', ['group_type' => $this->groupType->id(), 'plugin_id' => $plugin_id]),
      ];
    }

    return $operations;
  }

  /**
   * Builds operation links for the group type's content plugins.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   A render array of operation links.
   */
  public function buildOperations($plugin) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($plugin),
    ];

    return $build;
  }

}
