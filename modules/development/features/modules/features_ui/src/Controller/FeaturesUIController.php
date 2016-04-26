<?php

/**
 * @file
 * Contains \Drupal\features_ui\Controller\FeaturesUIController.
 */

namespace Drupal\features_ui\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;

/**
 * Returns ajax responses for the Features UI.
 */
class FeaturesUIController implements ContainerInjectionInterface {

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var array
   */
  protected $assigner;

  /**
   * Constructs a new FeaturesUIController object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *    The features manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner')
    );
  }

  /**
   * Returns a list of auto-detected config items for a feature.
   *
   * @param string $name
   *   Short machine name of feature to process.
   *
   * @return array
   *   List of auto-detected config items, keyed by type and short name.
   */
  public function detect($name) {
    $detected = array();
    $this->assigner->assignConfigPackages();
    $config_collection = $this->featuresManager->getConfigCollection();

    $items = $_POST['items'];
    if (!empty($items)) {
      $excluded = (!empty($_POST['excluded'])) ? $_POST['excluded'] : array();
      $selected = array();
      foreach ($items as $key) {
        preg_match('/^([^\[]+)(\[.+\])?\[(.+)\]\[(.+)\]$/', $key, $matches);
        if (!empty($matches[1]) && !empty($matches[4])) {
          $component = $matches[1];
          $item = $this->domDecode($matches[4]);
          if (!isset($excluded[$component][$item])) {
            $selected[] = $this->featuresManager->getFullName($component, $item);
          }
        }
      }
      $detected = !empty($selected) ? $this->getConfigDependents($selected) : array();
      $detected = array_merge($detected, $selected);
    }

    $result = [];
    foreach ($detected as $name) {
      $item = $config_collection[$name];
      $result[$item->getType()][$item->getShortName()] = $item->getName();
    }
    return new JsonResponse($result);
  }

  /**
   * Returns the configuration dependent on given items.
   *
   * @param array $item_names
   *   An array of item names.
   *
   * @return array
   *   An array of config items.
   */
  protected function getConfigDependents(array $item_names = NULL) {
    $result = [];
    $config_collection = $this->featuresManager->getConfigCollection();
    $packages = $this->featuresManager->getPackages();
    $settings = $this->featuresManager->getSettings();
    $allow_conflicts = $settings->get('conflicts');

    if (empty($item_names)) {
      $item_names = array_keys($config_collection);
    }

    foreach ($item_names as $item_name) {
      if ($config_collection[$item_name]->getPackage()) {
        foreach ($config_collection[$item_name]->getDependents() as $dependent_item_name) {
          if (isset($config_collection[$dependent_item_name])) {
            $allow = TRUE;
            if (!$allow_conflicts && $config_collection[$dependent_item_name]->getPackage()) {
              if ($packages[$config_collection[$dependent_item_name]->getPackage()]) {
                $allow = ($packages[$config_collection[$dependent_item_name]->getPackage()]->getStatus() == FeaturesManagerInterface::STATUS_NO_EXPORT)
                  || ($config_collection[$item_name]->getPackage() == $config_collection[$dependent_item_name]->getPackage());
              }
            }
            if ($allow) {
              $result[] = $dependent_item_name;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Encodes a given key.
   *
   * @param string $key
   *   The key to encode.
   *
   * @return string
   *   The encoded key.
   */
  protected function domEncode($key) {
    $replacements = $this->domEncodeMap();
    return strtr($key, $replacements);
  }

  /**
   * Decodes a given key.
   *
   * @param string $key
   *   The key to decode.
   *
   * @return string
   *   The decoded key.
   */
  protected function domDecode($key) {
    $replacements = array_flip($this->domEncodeMap());
    return strtr($key, $replacements);
  }

  /**
   * Returns encoding map for decode and encode options.
   *
   * @return array
   *   An encoding map.
   */
  protected function domEncodeMap() {
    return array(
      ':' => '__' . ord(':') . '__',
      '/' => '__' . ord('/') . '__',
      ',' => '__' . ord(',') . '__',
      '.' => '__' . ord('.') . '__',
      '<' => '__' . ord('<') . '__',
      '>' => '__' . ord('>') . '__',
      '%' => '__' . ord('%') . '__',
      ')' => '__' . ord(')') . '__',
      '(' => '__' . ord('(') . '__',
    );
  }

}
