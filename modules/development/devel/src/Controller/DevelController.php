<?php

/**
 * @file
 * Contains \Drupal\devel\Controller\DevelController.
 */

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Returns responses for devel module routes.
 */
class DevelController extends ControllerBase {

  /**
   * Clears all caches, then redirects to the previous page.
   */
  public function cacheClear() {
    drupal_flush_all_caches();
    drupal_set_message('Cache cleared.');
    return $this->redirect('<front>');
  }

  public function menuItem() {
    $item = menu_get_item(current_path());
    return kdevel_print_object($item);
  }

  public function themeRegistry() {
    $hooks = theme_get_registry();
    ksort($hooks);
    return array('#markup' => kprint_r($hooks, TRUE));
  }

  /**
   * Builds the elements info overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function elementsPage() {
    $element_info_manager = \Drupal::service('element_info');

    $elements_info = array();
    foreach ($element_info_manager->getDefinitions() as $element_type => $definition) {
      $elements_info[$element_type] = $definition + $element_info_manager->getInfo($element_type);
    }

    ksort($elements_info);

    return array('#markup' => kdevel_print_object($elements_info));
  }

  /**
   * Builds the fields info overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function fieldInfoPage() {
    $fields = FieldStorageConfig::loadMultiple();
    ksort($fields);
    $output['fields'] = array('#markup' => kprint_r($fields, TRUE, $this->t('Fields')));

    $field_instances = FieldConfig::loadMultiple();
    ksort($field_instances);
    $output['instances'] = array('#markup' => kprint_r($field_instances, TRUE, $this->t('Instances')));

    $bundles = $this->entityManager()->getAllBundleInfo();
    ksort($bundles);
    $output['bundles'] = array('#markup' => kprint_r($bundles, TRUE, $this->t('Bundles')));

    $field_types = \Drupal::service('plugin.manager.field.field_type')->getUiDefinitions();
    ksort($field_types);
    $output['field_types'] = array('#markup' => kprint_r($field_types, TRUE, $this->t('Field types')));

    $formatter_types = \Drupal::service('plugin.manager.field.formatter')->getDefinitions();
    ksort($formatter_types);
    $output['formatter_types'] = array('#markup' => kprint_r($formatter_types, TRUE, $this->t('Formatter types')));

    $widget_types = \Drupal::service('plugin.manager.field.widget')->getDefinitions();
    ksort($widget_types);
    $output['widget_types'] = array('#markup' => kprint_r($widget_types, TRUE, $this->t('Widget types')));

    return $output;
  }

  /**
   * Builds the entity types overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityInfoPage() {
    $types = $this->entityManager()->getEntityTypeLabels();
    ksort($types);
    $result = array();
    foreach (array_keys($types) as $type) {
      $definition = $this->entityManager()->getDefinition($type);
      $reflected_definition = new \ReflectionClass($definition);
      $props = array();
      foreach ($reflected_definition->getProperties() as $property) {
        $property->setAccessible(TRUE);
        $value = $property->getValue($definition);
        $props[$property->name] = $value;
      }
      $result[$type] = $props;
    }

    return array('#markup' => kprint_r($result, TRUE));
  }

  /**
   * Builds the state variable overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function stateSystemPage() {
    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $output['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter state name'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '.devel-state-list',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the state name to filter by.'),
      ),
    );

    $can_edit = $this->currentUser()->hasPermission('administer site configuration');

    $header = array(
      'name' => $this->t('Name'),
      'value' => $this->t('Value'),
    );

    if ($can_edit) {
      $header['edit'] = $this->t('Operations');
    }

    $rows = array();
    // State class doesn't have getAll method so we get all states from the
    // KeyValueStorage.
    foreach ($this->keyValue('state')->getAll() as $state_name => $state) {
      $rows[$state_name] = array(
        'name' => array(
          'data' => $state_name,
          'class' => 'table-filter-text-source',
        ),
        'value' => array(
          'data' => kprint_r($state, TRUE),
        ),
      );

      if ($can_edit) {
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('devel.system_state_edit', array('state_name' => $state_name)),
        );
        $rows[$state_name]['edit'] = array(
          'data' => array('#type' => 'operations', '#links' => $operations),
        );
      }
    }

    $output['states'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No state variables found.'),
      '#attributes' => array(
        'class' => array('devel-state-list'),
      ),
    );

    return $output;
  }

  /**
   * Builds the session overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function session() {
    $output['description'] = array(
      '#markup' => '<p>' . $this->t('Here are the contents of your $_SESSION variable.') . '</p>',
    );
    $output['session'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Session name'), $this->t('Session ID')),
      '#rows' => array(array(session_name(), session_id())),
      '#empty' => $this->t('No session available.'),
    );
    $output['data'] = array(
      '#markup' => kprint_r($_SESSION, TRUE),
    );

    return $output;
  }

  /**
   * Prints the loaded structure of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function entityLoad(RouteMatchInterface $route_match) {
    $output = array();

    $parameter_name = $route_match->getRouteObject()->getOption('_devel_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);

    if ($entity && $entity instanceof EntityInterface) {
      $output = array('#markup' => kdevel_print_object($entity));
    }

    return $output;
  }

  /**
   * Prints the render structure of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function entityRender(RouteMatchInterface $route_match) {
    $output = array();

    $parameter_name = $route_match->getRouteObject()->getOption('_devel_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);

    if ($entity && $entity instanceof EntityInterface) {
      $entity_type_id = $entity->getEntityTypeId();
      $view_hook = $entity_type_id . '_view';

      $build = array();
      // If module implements own {entity_type}_view
      if (function_exists($view_hook)) {
        $build = $view_hook($entity);
      }
      // If entity has view_builder handler
      elseif ($this->entityManager()->hasHandler($entity_type_id, 'view_builder')) {
        $build = $this->entityManager()->getViewBuilder($entity_type_id)->view($entity);
      }

      $output = array('#markup' => kdevel_print_object($build));
    }

    return $output;
  }

}
