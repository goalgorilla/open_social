<?php

namespace Drupal\social_user;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a trusted callback to alter the system branding block.
 *
 * @see social_user_block_view_search_content_block_alter()
 */
class SocialUserSearchContentBlockAlter implements TrustedCallbackInterface {

  /**
   * Pre render for the search content in the header. This will add javascript.
   */
  public static function preRender($build) {
    // Attach the social_search library defined in social_search.libraries.yml.
    $build['#attached'] = [
      'library' => [
        'social_search/navbar-search',
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

}
