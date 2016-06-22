<?php
/**
 * @file
 *
 * Contains \Drupal\message\Form\ConfigTranslationDeleteForm.
 */

namespace Drupal\message\Form;

use Drupal\config_translation\Form\ConfigTranslationDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\MessageType;
/**
 * Builds a form to delete configuration translation.
 */
class MessageTypeConfigTranslationDeleteForm extends ConfigTranslationDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'message_type_config_translation_delete_form';
  }
}
