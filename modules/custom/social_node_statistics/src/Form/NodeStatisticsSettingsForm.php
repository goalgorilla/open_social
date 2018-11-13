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

    // List of node types.
    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Track views for node types'),
      '#description' => $this->t('Select which node types views should be tracked for.'),
      '#default_value' => $this->config('social_node_statistics.settings')->get('node_types'),
    ];

    // Max age of caching.
    $form['max_age'] = [
      '#type' => 'select',
      '#options' => [
        30 => $this->t('30 seconds'),
        60 => $this->t('1 minute'),
        120 => $this->t('2 minutes'),
        300 => $this->t('5 minutes'),
        600 => $this->t('10 minutes'),
        900 => $this->t('15 minutes'),
        1800 => $this->t('30 minutes'),
        3600 => $this->t('1 hour'),
        21600 => $this->t('6 hours'),
        43200 => $this->t('12 hours'),
        86400 => $this->t('1 day'),
      ],
      '#title' => $this->t('Max age of views count caching'),
      '#description' => $this->t('Select how long before the views count cache on a node display is invalidated.'),
      '#default_value' => $this->config('social_node_statistics.settings')->get('max_age'),
      '#required' => TRUE,
      '#access' => $this->currentUser()->hasPermission('administer node statistics caching'),
    ];

    // Minimum number of views.
    $form['min_views'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum number of new views before cache invalidation'),
      '#description' => $this->t('Set the minimum number of new views before the views count cache on a node display is invalidated.'),
      '#default_value' => $this->config('social_node_statistics.settings')->get('min_views'),
      '#min' => 0,
      '#step' => 1,
      '#required' => TRUE,
      '#access' => $this->currentUser()->hasPermission('administer node statistics caching'),
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
      ->set('max_age', $form_state->getValue('max_age'))
      ->set('min_views', $form_state->getValue('min_views'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
