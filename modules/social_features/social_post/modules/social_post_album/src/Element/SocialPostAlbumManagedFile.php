<?php

namespace Drupal\social_post_album\Element;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("social_post_album_managed_file")
 */
class SocialPostAlbumManagedFile extends ManagedFile {

  /**
   * {@inheritdoc}
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $response = parent::uploadAjaxCallback($form, $form_state, $request);
    $parents = explode('/', $request->query->get('element_parents'));

    return $response->addCommand(new InvokeCommand(
      '#edit-' . str_replace('_', '-', $parents[0]) . '-wrapper',
      'addClass',
      ['post-images-loaded']
    ));
  }

}
