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
   * The CSS class which adding to wrapper when at least one image was loaded.
   */
  const CLASS_NAME = 'post-images-loaded';

  /**
   * {@inheritdoc}
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $response = parent::uploadAjaxCallback($form, $form_state, $request);
    $parents = explode('/', $request->query->get('element_parents'));

    return $response->addCommand(new InvokeCommand(
      '#edit-' . str_replace('_', '-', $parents[0]) . '-wrapper',
      'addClass',
      [self::CLASS_NAME]
    ));
  }

  /**
   * {@inheritdoc}
   */
  public static function processManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#value']['fids'])) {
      $complete_form[$element['#field_name']]['#attributes']['class'][] = self::CLASS_NAME;
    }

    return parent::processManagedFile($element, $form_state, $complete_form);
  }

}
