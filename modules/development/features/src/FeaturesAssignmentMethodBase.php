<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesAssignmentMethodBase.
 */

namespace Drupal\features;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for package assignment methods.
 */
abstract class FeaturesAssignmentMethodBase extends PluginBase implements FeaturesAssignmentMethodInterface {
  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The features assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setfeaturesManager(FeaturesManagerInterface $features_manager) {
    $this->featuresManager = $features_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssigner(FeaturesAssignerInterface $assigner) {
    $this->assigner = $assigner;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Assigns configuration of the types specified in a setting to a package.
   *
   * @param string $machine_name
   *   Machine name of the package.
   * @param bool $force
   *   (optional) If TRUE, assign config regardless of restrictions such as it
   *   being already assigned to a package.
   */
  protected function assignPackageByConfigTypes($machine_name, $force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings($this->getPluginId());
    $types = $settings['types']['config'];

    $config_collection = $this->featuresManager->getConfigCollection();

    foreach ($config_collection as $item_name => $item) {
      // Don't assign configuration that's provided by an extension.
      if (in_array($item->getType(), $types) && !($item->isProviderExcluded())) {
        try {
          $this->featuresManager->assignConfigPackage($machine_name, [$item_name]);
        }
        catch (\Exception $exception) {
          \Drupal::logger('features')->error($exception->getMessage());
        }
      }
    }
  }

  /**
   * Assigns a given subdirectory to configuration of specified types.
   *
   * @param string $subdirectory
   *   The subdirectory that designated configuration should be exported to.
   */
  protected function assignSubdirectoryByConfigTypes($subdirectory) {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings($this->getPluginId());
    $types = $settings['types']['config'];

    $config_collection = $this->featuresManager->getConfigCollection();

    foreach ($config_collection as &$item) {
      if (in_array($item->getType(), $types)) {
        $item->setSubdirectory($subdirectory);
      }
    }
    // Clean up the $item pass by reference.
    unset($item);

    $this->featuresManager->setConfigCollection($config_collection);
  }

}
