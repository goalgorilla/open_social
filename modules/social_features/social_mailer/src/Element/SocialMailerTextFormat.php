<?php

namespace Drupal\social_mailer\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a process callback for the text_format elements.
 */
class SocialMailerTextFormat {

  /**
   * Processes a text format form element.
   *
   * Delete the "Mail HTML" text format from each element of forms which doesn't
   * part of Views bulk process.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @see social_mailer_element_info_alter()
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    if (!$form_state->has('views_bulk_operations')) {
      $format = &$element['format'];

      if (isset($format['format']['#options']['mail_html'])) {
        unset($format['format']['#options']['mail_html']);

        if (count($format['format']['#options']) === 1) {
          $format['format']['#access'] = $format['help']['#access'] = FALSE;
        }
      }
    }

    return $element;
  }

}
