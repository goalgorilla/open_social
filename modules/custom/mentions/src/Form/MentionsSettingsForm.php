<?php

namespace Drupal\mentions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Configure mentions settings.
 */
class MentionsSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mentions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mentions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mentions.settings');

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General configuration'),
      '#open' => TRUE,
    );

    $form['general']['supported_entity_types'] = [
      '#title' => t('Supported entity types'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->getContentEntityTypes(),
      '#default_value' => $config->get('supported_entity_types'),
      '#description' => t('Mentions entity will be created only for selected entity types.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $config = $this->config('mentions.settings');

    $config->set('supported_entity_types', $form_state->getValue('supported_entity_types'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get content entity types keyed by id.
   *
   * @return array
   *   Returns array of content entity types.
   */
  protected function getContentEntityTypes() {
    $options = [];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }
    return $options;
  }

}
