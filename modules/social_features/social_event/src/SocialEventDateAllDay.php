<?php

namespace Drupal\social_event;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trusted callback to alter the event date all day checkbox.
 *
 * @see social_event_field_widget_single_element_form_alter()
 */
class SocialEventDateAllDay implements TrustedCallbackInterface {

  /**
   * Pre render for the search content in the header. This will add javascript.
   */
  public static function allDayCheckboxCallback(array &$element, FormStateInterface $form_state): void {
    // Time field should disappear when 'All day' is checked.
    $state = [
      ':input[name="field_event_all_day[value]"]' => [
        'checked' => TRUE,
      ],
    ];
    $element['time']['#states'] = [
      'invisible' => $state,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['allDayCheckboxCallback'];
  }

}
