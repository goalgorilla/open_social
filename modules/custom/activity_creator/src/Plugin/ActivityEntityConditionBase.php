<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Base class for Activity entity condition plugins.
 */
abstract class ActivityEntityConditionBase extends PluginBase implements ActivityEntityConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition(ContentEntityInterface $entity): bool {
    return TRUE;
  }

}
