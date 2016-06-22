<?php

/**
 * @file
 * Contains \Drupal\profile\Entity\ProfileTypeInterface.
 */

namespace Drupal\profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a profile type entity.
 */
interface ProfileTypeInterface extends ConfigEntityInterface {

  /**
   * Return the registration form flag.
   *
   * For allowing creation of profile type at user registration.
   */
  public function getRegistration();

  /**
   * Return the allow multiple flag.
   *
   * @return bool
   *   TRUE if multiple allowed.
   */
  public function getMultiple();

  /**
   * Set the allow multiple flag.
   *
   * @param bool $multiple
   *   Boolean for the allow multiple flag.
   *
   * @return $this
   */
  public function setMultiple($multiple);

  /**
   * Return the user roles allowed by this profile type.
   *
   * @return array
   *   Array of Drupal user roles ids.
   */
  public function getRoles();

  /**
   * Set the user roles allowed by this profile type.
   *
   * @param array $roles
   *   Array of Drupal user roles ids.
   *
   * @return $this
   */
  public function setRoles($roles);

  /**
   * Returns the profile type's weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets the profile type's weight.
   *
   * @param int $weight
   *   The profile type's weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
