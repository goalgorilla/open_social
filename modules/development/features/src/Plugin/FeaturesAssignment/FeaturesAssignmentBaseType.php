<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentBaseType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\component\Utility\Unicode;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on entity types.
 *
 * @Plugin(
 *   id = "base",
 *   weight = -2,
 *   name = @Translation("Base type"),
 *   description = @Translation("Use designated types of configuration as the base for configuration package modules. For example, if content types are selected as a base type, a package will be generated for each content type and will include all configuration dependent on that content type."),
 *   config_route_name = "features.assignment_base",
 *   default_settings = {
 *     "types" = {
 *       "config" = {},
 *       "content" = {}
 *     }
 *   }
 * )
 */
class FeaturesAssignmentBaseType extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings($this->getPluginId());
    $config_base_types = $settings['types']['config'];

    $config_types = $this->featuresManager->listConfigTypes();
    $config_collection = $this->featuresManager->getConfigCollection();

    foreach ($config_collection as $item_name => $item) {
      if (in_array($item->getType(), $config_base_types)) {
        if (is_null($this->featuresManager->findPackage($item->getShortName())) && !$item->getPackage()) {
          $description = $this->t('Provides @label @type and related configuration.', array('@label' => $item->getLabel(), '@type' => Unicode::strtolower($config_types[$item->getType()])));
          if (isset($item->getData()['description'])) {
            $description .= ' ' . $item->getData()['description'];
          }
          $this->featuresManager->initPackage($item->getShortName(), $item->getLabel(), $description, 'module', $current_bundle);
          // Update list with the package we just added.
          try {
            $this->featuresManager->assignConfigPackage($item->getShortName(), [$item_name]);
          }
          catch (\Exception $exception) {
            \Drupal::logger('features')->error($exception->getMessage());
          }
          $this->featuresManager->assignConfigDependents([$item_name]);
        }
      }
    }

    $entity_types = $this->entityManager->getDefinitions();

    $content_base_types = $settings['types']['content'];
    foreach ($content_base_types as $entity_type_id) {
      if (!isset($packages[$entity_type_id]) && isset($entity_types[$entity_type_id])) {
        $label = $entity_types[$entity_type_id]->getLabel();
        $description = $this->t('Provide @label related configuration.', array('@label' => $label));
        $this->featuresManager->initPackage($entity_type_id, $label, $description, 'module', $current_bundle);
      }
    }
  }

}
