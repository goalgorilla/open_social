<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\hux\Attribute\Alter;

/**
 * Provides hooks related to node event online feature.
 */
final class EventOnline {

  /**
   * AJAX callback to return the event address field.
   *
   * This function is used in an AJAX request to update the address field
   * dynamically when field_event_online is changed.
   */
  public static function refreshAddressFieldCallback(array &$form, FormStateInterface $form_state): array {
    return $form['field_event_address'];
  }

  /**
   * After-build callback to modify the address field.
   *
   * This function ensures that the country_code field inside the address field
   * is set as required.
   * It is applied dynamically using the #after_build property.
   */
  public static function rebuildAddressFieldCallback(array $form, FormStateInterface $form_state): array {
    // Get online value.
    $is_online = $form_state->getValue(['field_event_online', 'value']);
    if ($is_online) {
      $form['field_event_address']['widget'][0]['#suffix'] = '<div class="help-block">' . t('For online events that can also be joined at location.') . '</div>';
    }

    // Set requirement.
    $form['field_event_address']['widget'][0]['address']['country_code']['country_code']['#required'] = !$is_online;

    return $form;
  }

  /**
   * Adjusts the address field for dynamic behavior.
   *
   * This method sets up AJAX integration for dynamically updating the
   * address field when the online checkbox state changes. It also wraps
   * the address field in a container for AJAX updates and attaches a
   * callback to conditionally modify the address field after build.
   *
   * @param array $online
   *   The online checkbox form field element.
   * @param array $address
   *   The address form field element to be adjusted.
   */
  private function adjustAddressField(array &$online, array &$address): void {
    // Attach AJAX to trigger form rebuild when toggled.
    $online['widget']['value']['#ajax'] = [
      'callback' => [static::class, 'refreshAddressFieldCallback'],
      'wrapper' => $wrapper = 'event-address-wrapper',
      'event' => 'change',
      'progress' => [
        'type' => 'none',
      ],
    ];

    // Wrap the address field for AJAX updates.
    $address['#prefix'] = "<div id='$wrapper'>";
    $address['#suffix'] = '</div>';

    // Attach after_build to conditionally require the country field based on
    // the online checkbox state.
    $address['#after_build'][] = [static::class, 'rebuildAddressFieldCallback'];
  }

  /**
   * Alters the event node form for specific adjustments for online feature.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_node_event_form')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    if (empty($form['field_event_online'])) {
      return;
    }

    $online =& $form['field_event_online'];

    // Hide field description.
    $online['widget']['value']['#description_display'] = 'invisible';

    // Make changes to "field_event_address".
    if (isset($form['field_event_address'])) {
      $this->adjustAddressField($online, $form['field_event_address']);
    }
  }

}
