<?php

declare(strict_types=1);

namespace Drupal\social_event\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\meeting_api_bbb\PluginForm\BigBlueButtonMeetingConfigurationForm as Base;

/**
 * Overrides the "BBB" backend settings form.
 */
class BigBlueButtonMeetingConfigurationForm extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hide the list of settings we don't want to show for event managers.
    $form['auto_start_recording']['#access'] =
    $form['welcome_text']['#access'] =
    $form['allow_private_chat']['#access'] =
    $form['allow_webcams']['#access'] = FALSE;

    // Hide description for all fields.
    foreach (Element::children($form) as $key) {
      unset($form[$key]['#description']);
    }

    return $form;
  }

}
