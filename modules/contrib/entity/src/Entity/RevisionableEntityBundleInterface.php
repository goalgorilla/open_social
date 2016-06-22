<?php

/**
 * @file
 * Contains \Drupal\entity\Entity\RevisionableEntityBundleInterface.
 */

namespace Drupal\entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface RevisionableEntityBundleInterface extends ConfigEntityInterface {

  /**
   * Returns whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function shouldCreateNewRevision();

}
