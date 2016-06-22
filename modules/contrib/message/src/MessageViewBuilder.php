<?php

/**
 * @file
 * Definition of Drupal\message\MessageViewBuilder.
 */

namespace Drupal\message;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\message\Entity\Message;

/**
 * Render controller for Messages.
 */
class MessageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    if (!$langcode) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    else {
      if (\Drupal::moduleHandler()->moduleExists('config_translation') && !isset($partials[$langcode])) {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      }
    }

    // Load the partials in the correct language.
    /* @var \Drupal\message\Entity\Message $entity  */
    if ($langcode) {
      $entity->setLanguage($langcode);
    }
    $partials = $entity->getText();

    $extra = '';

    // Get the partials the user selected for the current view mode.
    $extra_fields = entity_get_display('message', $entity->bundle(), $view_mode);
    foreach ($extra_fields->getComponents() as $field_name => $settings) {
      // The partials are keyed with `partial_X`, check if that is set.
      list(, $delta) = explode('_', $field_name);
      if (isset($partials[$delta])) {
        $extra .= $partials[$delta];
      }
      else {
        // This is another field.
        $display = $this->getSingleFieldDisplay($entity, $field_name, $settings);
        $build += $display->build($entity);
      }
    }

    $build['#markup'] = $extra;

    return $build;
  }
}
