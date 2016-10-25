<?php

namespace Drupal\social_post\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Post entities.
 *
 * @ingroup social_post
 */
class PostDeleteForm extends ContentEntityDeleteForm {

  public function getRedirectUrl() {
    // Set the redirect url to the destination in the url.
    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::destination();
      $path = $destination->get();
      return Url::fromUserInput($path);
    }
    // Default to the stream page.
    return  Url::fromRoute('social_core.homepage');
  }
}
