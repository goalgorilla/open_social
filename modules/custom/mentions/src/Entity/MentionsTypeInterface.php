<?php

namespace Drupal\mentions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Mentions Type entities.
 */
interface MentionsTypeInterface extends ConfigEntityInterface {

  /**
   * The mention type.
   *
   * @return string
   *   Returns the mention type.
   */
  public function mentionType(): string;

  /**
   * Get the input settings.
   *
   * @return array
   *   Returns the input settings.
   */
  public function getInputSettings(): array;

}
