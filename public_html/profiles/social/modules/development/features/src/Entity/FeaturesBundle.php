<?php

/**
 * @file
 * Contains \Drupal\features\Entity\FeaturesBundle.
 */

namespace Drupal\features\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\features\FeaturesBundleInterface;

/**
 * Defines a features bundle.
 * @todo Better description
 *
 * @ConfigEntityType(
 *   id = "features_bundle",
 *   label = @Translation("Features bundle"),
 *   handlers = {
 *   },
 *   admin_permission = "administer site configuration",
 *   config_prefix = "bundle",
 *   entity_keys = {
 *     "id" = "machine_name",
 *     "label" = "name"
 *   },
 *   links = {
 *   },
 *   config_export = {
 *     "name",
 *     "machine_name",
 *     "description",
 *     "assignments",
 *     "weights",
 *     "settings",
 *     "profile_name",
 *     "is_profile",
 *   }
 * )
 */
class FeaturesBundle extends ConfigEntityBase implements FeaturesBundleInterface {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var
   */
  protected $machine_name;

  /**
   * @var string
   */
  protected $description;

  /**
   * @var string[]
   */
  protected $assignments = [];

  /**
   * @var array
   */
  protected $assignment_settings = [];

  /**
   * @var int[]
   */
  protected $weights = [];

  /**
   * @var array
   */
  protected $settings = [];

  /**
   * @var string
   */
  protected $profile_name;

  /**
   * @var bool
   */
  protected $is_profile;

  public function id() {
    // @todo Convert it to $this->id in the long run.
    return $this->getMachineName();
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->machine_name == static::DEFAULT_BUNDLE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    $this->machine_name = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullName($short_name) {
    if ($this->isDefault() || $this->inBundle($short_name)) {
      return $short_name;
    }
    else {
      return $this->machine_name . '_' . $short_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getShortName($machine_name) {
    if (!$this->isProfilePackage($machine_name) && $this->inBundle($machine_name)) {
      return substr($machine_name, strlen($this->getMachineName()) + 1, strlen($machine_name) - strlen($this->getMachineName()) - 1);
    }
    return $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function inBundle($machine_name) {
    return ($this->isProfilePackage($machine_name) || strpos($machine_name, $this->machine_name . '_') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isProfilePackage($machine_name) {
    return ($this->isProfile() && $machine_name == $this->getProfileName());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function isProfile() {
    return $this->is_profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsProfile($value) {
    $this->is_profile = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileName() {
    $name = $this->isProfile() ? $this->profile_name : '';
    return !empty($name) ? $name : drupal_get_profile();
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileName($machine_name) {
    $this->profileName = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledAssignments() {
    $list = array();
    foreach ($this->assignments as $method_id => $method) {
      if ($method['enabled']) {
        $list[$method_id] = $method_id;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabledAssignments(array $assignments) {
    foreach ($this->assignments as $method_id => &$method) {
      $method['enabled'] = in_array($method_id, $assignments);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentWeights() {
    $list = array();
    foreach ($this->assignments as $method_id => $method) {
      $list[$method_id] = $method['weight'];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentWeights(array $assignments) {
    foreach ($this->assignments as $method_id => &$method) {
      if (isset($assignments[$method_id])) {
        $method['weight'] = $assignments[$method_id];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentSettings($method_id = NULL) {
    if (isset($method_id)) {
      if (isset($this->assignments[$method_id])) {
        return $this->assignments[$method_id];
      }
    }
    else {
      $list = array();
      foreach ($this->assignments as $method_id => $method) {
        $list[$method_id] = $method;
      }
      return $list;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentSettings($method_id, array $settings) {
    if (isset($method_id)) {
      $this->assignments[$method_id] = $settings;
    }
    else {
      foreach ($settings as $method_id => $method_settings) {
        if (!empty($method_settings)) {
          $this->setAssignmentSettings($method_id, $method_settings);
        }
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function remove() {
    $this->delete();
  }

}
