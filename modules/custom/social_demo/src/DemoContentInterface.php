<?php

namespace Drupal\social_demo;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Interface DemoContentInterface.
 *
 * @package Drupal\social_demo
 */
interface DemoContentInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the file name.
   *
   * @return string
   *   The source filename where are data.
   */
  public function getSource();

  /**
   * Sets the used profile.
   *
   * @param string $profile
   *   The profile.
   *
   * @return string
   *   Void.
   */
  public function setProfile($profile);

  /**
   * Returns the profile.
   *
   * @return string
   *   The used demo content profile.
   */
  public function getProfile();

  /**
   * Returns the module name.
   *
   * @return string
   *   The module name where is placed file with data.
   */
  public function getModule();

  /**
   * Creates content.
   *
   * @return array
   *   An array with list of created entities.
   */
  public function createContent();

  /**
   * Removes content.
   */
  public function removeContent();

  /**
   * Returns quantity of created items.
   *
   * @return int
   *   Returns quantity of created items.
   */
  public function count();

  /**
   * Set entity storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The EntityStorageInterface entity_storage.
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage);

}
