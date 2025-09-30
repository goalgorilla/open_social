<?php

declare(strict_types=1);

namespace Drupal\social_event\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\meeting_api_manual\PluginForm\ManualMeetingConfigurationForm as Base;

/**
 * Overrides the "Manual" backend settings form and makes the URL optional.
 */
final class ManualMeetingConfigurationForm extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    // We want to have the "Url" field optional.
    $form['url']['#required'] = FALSE;
    // Hide the URL field title.
    $form['url']['#title_display'] = 'invisible';
    // Override the description.
    $form['url']['#description'] = $this->t('For enrollees to join in one click, use any meeting tool.');

    return $form;
  }

}
