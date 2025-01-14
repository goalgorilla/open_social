<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Url;

/**
 * Pre-processes variables for "like_and_dislike_icons" hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("like_and_dislike_icons")
 */
class LikeAndDislikeIcons extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables): void {
    parent::preprocessVariables($variables);

    $variables->url = Url::fromRoute('view.who_liked_this_entity.wholiked', [
      'arg_0' => $variables->entity_type,
      'arg_1' => $variables->entity_id,
    ]);
  }

}
