<?php

/**
 * @file
 * Contains
 * \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentSiteType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to a site package based on entity types.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentSiteType::METHOD_ID,
 *   weight = 7,
 *   name = @Translation("Site type"),
 *   description = @Translation("Assign designated types of configuration to a site configuration package module. For example, if image styles are selected as a site type, a site package will be generated and image styles will be assigned to it."),
 *   config_route_name = "features.assignment_site"
 * )
 */
class FeaturesAssignmentSiteType extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'site';

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $machine_name = 'site';
    $name = $this->t('Site');
    $description = $this->t('Provide site components.');
    $this->featuresManager->initPackage($machine_name, $name, $description, 'module', $current_bundle);
    $this->assignPackageByConfigTypes(self::METHOD_ID, $machine_name, $force);
  }

}
