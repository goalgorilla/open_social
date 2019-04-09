<?php

namespace Drupal\social_content_report\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventSettingsForm.
 *
 * @package Drupal\social_content_report\Form
 */
class ContentReportSettingsForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_content_report.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_content_report_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('social_content_report.settings');

    // Allow immediate unpublishing.
    $form['unpublish_threshold'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublished immediately'),
      '#description' => $this->t('Whether the content is immediately unpublished if a user reports it as inappropriate.'),
      '#default_value' => $config->get('unpublish_threshold'),
    ];

    // A list of reason terms to display the reason textfield for.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('report_reasons');
    foreach ($terms as $term) {
      $reason_terms[$term->tid] = $term->name;
    }

    $form['reasons_with_text'] = [
      '#type' => 'checkboxes',
      '#options' => $reason_terms,
      '#title' => $this->t('Terms with additional reason text'),
      '#description' => $this->t('Select the terms that will show an additional field where users can describe their reasons.'),
      '#default_value' => $config->get('reasons_with_text'),
    ];

    // Make reason text at reporting mandatory.
    $form['mandatory_reason'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mandatory reason text'),
      '#description' => $this->t('Whether users should fill in a mandatory reason or if it is optional.'),
      '#default_value' => $config->get('mandatory_reason'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $term_ids = [];

    foreach ($form_state->getValue('reasons_with_text') as $term_id) {
      if ($term_id) {
        $term_ids[] = $term_id;
      }
    }

    $this->config('social_content_report.settings')
      ->set('unpublish_threshold', $form_state->getValue('unpublish_threshold'))
      ->set('reasons_with_text', $term_ids)
      ->set('mandatory_reason', $form_state->getValue('mandatory_reason'))
      ->save();
  }

}
