<?php

namespace Drupal\social_node_statistics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure social profile settings.
 */
class NodeStatisticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_node_statistics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_node_statistics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    /** @var \Drupal\node\Entity\NodeType $node_type */
    foreach (NodeType::loadMultiple() as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Track views for node types'),
      '#description' => $this->t('Select which node types views should be tracked for.'),
      '#default_value' => $this->config('social_node_statistics.settings')->get('node_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Filter out unchecked items.
    $node_types = $form_state->getValue('node_types');
    foreach ($node_types as $node_type => $value) {
      if (!$value) {
        unset($node_types[$node_type]);
      }
    }

    // Save config.
    $config = $this->config('social_node_statistics.settings');
    $config->set('node_types', $node_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
