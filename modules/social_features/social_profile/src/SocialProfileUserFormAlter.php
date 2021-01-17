<?php

namespace Drupal\social_profile;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a trusted callback to alter the system branding block.
 *
 * @see social_user_block_view_search_content_block_alter()
 */
class SocialProfileUserFormAlter implements RenderCallbackInterface {

  /**
   * Hide timezone fields group label.
   */
  public static function preRender($element) {
    $element['group_locale_settings']['timezone']['#title'] = NULL;
    return $element;
  }

}
