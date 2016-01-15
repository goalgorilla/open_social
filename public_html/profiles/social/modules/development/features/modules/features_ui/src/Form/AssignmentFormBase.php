<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\AssignmentFormBase.
 */

namespace Drupal\features_ui\Form;

use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
abstract class AssignmentFormBase extends FormBase {

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current bundle.
   *
   * @var \Drupal\features\FeaturesBundleInterface
   */
  protected $currentBundle;

  /**
   * Constructs a AssignmentBaseForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager.
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The assigner.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner, EntityManagerInterface $entity_manager) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('entity.manager')
    );
  }

  /**
   * Adds configuration types checkboxes.
   */
  protected function setConfigTypeSelect(&$form, $defaults, $type, $bundles_only = FALSE) {
    $options = $this->featuresManager->listConfigTypes($bundles_only);

    if (!isset($form['types'])) {
      $form['types'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
      );
    }

    $form['types']['config'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Configuration types'),
      '#description' => $this->t('Select types of configuration that should be considered @type types.', array('@type' => $type)),
      '#options' => $options,
      '#default_value' => $defaults,
    );
  }

  /**
   * Adds content entity types checkboxes.
   */
  protected function setContentTypeSelect(&$form, $defaults, $type, $exclude_has_config_bundles = TRUE) {
    $entity_types = $this->entityManager->getDefinitions();

    $has_config_bundle = array();
    foreach ($entity_types as $definition) {
      if ($entity_type_id = $definition->getBundleOf()) {
        $has_config_bundle[] = $entity_type_id;
      }
    }
    $options = array();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      if ($exclude_has_config_bundles && in_array($entity_type_id, $has_config_bundle)) {
        continue;
      }
      $options[$entity_type_id] = $entity_type->getLabel() ?: $entity_type_id;
    }

    // Sort the entity types by label.
    uasort($options, 'strnatcasecmp');

    if (!isset($form['types'])) {
      $form['types'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
      );
    }

    $form['types']['content'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Content entity types'),
      '#description' => $this->t('Select content entity types that should be considered @type types.', array('@type' => $type)),
      '#options' => $options,
      '#default_value' => $defaults,
    );
  }

  /**
   * Adds a "Save settings" submit action.
   */
  protected function setActions(&$form) {
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save settings'),
    );
  }

  /**
   * Redirects back to the Bundle config form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function setRedirect(FormStateInterface $form_state) {
    $form_state->setRedirect('features.assignment', array('bundle_name' => $this->currentBundle->getMachineName()));
  }

}
