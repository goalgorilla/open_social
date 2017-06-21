<?php

namespace Drupal\social_demo;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityStorageInterface;

interface DemoContentInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the file name.
   *
   * @return string
   *   The source filename where are data.
   */
  public function getSource();

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
   */
  public function count();

  /**
   * Set entity storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage);

}
