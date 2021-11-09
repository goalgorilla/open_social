<?php

namespace Drupal\social_demo;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Interface for demo content.
 *
 * @package Drupal\social_demo
 */
interface DemoContentInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the file name.
   *
   * @return string|null
   *   The source filename where are data.
   */
  public function getSource(): ?string;

  /**
   * Sets the used profile.
   *
   * @param string $profile
   *   The profile.
   */
  public function setProfile($profile): void;

  /**
   * Returns the profile.
   *
   * @return string
   *   The used demo content profile.
   */
  public function getProfile(): string;

  /**
   * Returns the module name.
   *
   * @return string|null
   *   The module name where is placed file with data.
   */
  public function getModule(): ?string;

  /**
   * Creates content.
   */
  public function createContent(): array;

  /**
   * Removes content.
   */
  public function removeContent(): void;

  /**
   * Returns quantity of created items.
   *
   * @return int
   *   Returns quantity of created items.
   */
  public function count(): int;

  /**
   * Set entity storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The EntityStorageInterface entity_storage.
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage): void;

  /**
   * Scramble it.
   *
   * @param array $data
   *   The data array to scramble.
   * @param int|null $max
   *   How many items to generate.
   *
   * @return array
   *   An array with list of data.
   */
  public function scrambleData(array $data, $max = NULL): array;

}
