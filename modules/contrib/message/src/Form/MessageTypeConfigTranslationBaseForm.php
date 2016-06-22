<?php
/**
 * @file
 *
 * Contains \Drupal\message\Form\MessageTypeConfigTranslationBaseForm.
 */

namespace Drupal\message\Form;

use Drupal\config_translation\Form\ConfigTranslationFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\message\Entity\MessageType;
use Drupal\message\FormElement\MessageTypeMultipleTextField;

/**
 * Defines a form for adding configuration translations.
 */
abstract class MessageTypeConfigTranslationBaseForm extends ConfigTranslationFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $plugin_id = NULL, $langcode = NULL) {
    $form = parent::buildForm($form, $form_state, $route_match, $plugin_id, $langcode);
    // Get the name of the message type.
    $names = $this->mapper->getConfigNames();
    $name = reset($names);
    $form['config_names'][$name]['text'] = [
      '#theme' => 'config_translation_manage_form_element',
      'source' => [
        '#type' => 'item',
        '#title' => $this->t('Text'),
        '#markup' => $this->t('The message text'),
      ],
    ];
    $translation = &$form['config_names'][$name]['text']['translation'];
    $configs = $form_state->get('config_translation_mapper')->getConfigData();
    $entity = MessageType::load($configs[$name]['type']);
    $form_state->set('#entity', $entity);

    $config_translation = $this->languageManager->getLanguageConfigOverride($this->language->getId(), $name);
    if (!$text = $config_translation->get('text')) {
      $text = [];
    }

    $multiple = new MessageTypeMultipleTextField($entity, [get_class($this), 'addMoreAjax'], $langcode);
    $multiple->textField($translation, $form_state, $text);
    return $form;
  }
  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $configs = $form_state->get('config_translation_mapper')->getConfigData();
    $config = reset($configs);
    return $form['config_names']['message.type.' . $config['type']]['text']['translation']['text'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Get the name of the message type.
    $names = $this->mapper->getConfigNames();
    $name = reset($names);

    // Sort the elements.
    $elements = $form_state->getValue('text');
    usort($elements, 'message_order_text_weight');

    // Build the new text.
    $text = [];

    foreach ($elements as $element) {
      if (!$element['value']) {
        continue;
      }

      $text[] = $element['value'];
    }

    // Save the new text.
    $config_translation = $this->languageManager->getLanguageConfigOverride($this->language->getId(), $name);
    $config_translation->set('text', $text);
    $config_translation->save();
  }
}
