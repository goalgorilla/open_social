<?php

/**
 * @file
 * Contains \Drupal\message\Form\MessageTypeConfigTranslationAddForm.
 */

namespace Drupal\message\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\MessageType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form for adding configuration translations.
 */
class MessageTypeConfigTranslationAddForm extends MessageTypeConfigTranslationBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_type_config_translation_add_form';
  }
}
