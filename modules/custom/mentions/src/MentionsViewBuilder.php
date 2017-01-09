<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for mentions.
 */
class MentionsViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);
  }

}
