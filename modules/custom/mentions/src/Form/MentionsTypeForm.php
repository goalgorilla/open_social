<?php

/**
 * @file
 * Contains Drupal\mentions\Form\MentionsTypeForm.
 */

namespace Drupal\mentions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\mentions\MentionsPluginManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class MentionsTypeForm.
 *
 * @package Drupal\mentiona\Form
 */
class MentionsTypeForm extends EntityForm implements ContainerInjectionInterface {

  use ConfigFormBaseTrait;

  protected $mentionsManager;
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MentionsPluginManager $mentions_manager, EntityManagerInterface $entity_manager) {
    $this->mentionsManager = $mentions_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mentions'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mentions.mentions_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mentions_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin_names = $this->mentionsManager->getPluginNames();
    $entity = $this->entity;
    $inputsettings = $entity->get('input');
    $entity_id = isset($entity) ? $entity->id() : '';
    $all_entitytypes = array_keys($this->entityManager->getEntityTypeLabels());
    $candidate_entitytypes = array();
    foreach ($all_entitytypes as $entity_type) {
      $entitytype_info = $this->entityManager->getDefinition($entity_type);
      $configentityclassname = ContentEntityType::class;
      $entitytype_type = get_class($entitytype_info);
      if ($entitytype_type == $configentityclassname) {
        $candidate_entitytypes[$entity_type] = $entitytype_info->getLabel()
          ->getUntranslatedString();
        $candidate_entitytypefields[$entity_type][$entitytype_info->getKey('id')] = $entitytype_info->getKey('id');

        if ($entity_type === 'user') {
          $candidate_entitytypefields[$entity_type]['name'] = 'name';
        }
        else {
          $candidate_entitytypefields[$entity_type][$entitytype_info->getKey('label')] = $entitytype_info->getKey('label');
        }
      }
    }

    $config = $this->config('mentions.mentions_type.' . $entity_id);

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#description' => $this->t('The human-readable name of this mention type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#default_value' => $config->get('name'),
    );

    $form['mention_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Mention Type'),
      '#options' => $plugin_names,
      '#default_value' => $config->get('mention_type'),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Describe this mention type.'),
      '#default_value' => $config->get('description'),
    );

    $form['input'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Input Settings'),
      '#tree' => TRUE,
    );

    $form['input']['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $config->get('input.prefix'),
      '#size' => 2,
    );

    $form['input']['suffix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $config->get('input.suffix'),
      '#size' => 2,
    );

    $entitytype_selection = $config->get('input.entity_type');

    $form['input']['entity_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $candidate_entitytypes,
      '#default_value' => $entitytype_selection,
      '#ajax' => [
        'callback' => array($this, 'changeEntityTypeInForm'),
        'wrapper' => 'edit-input-value-wrapper',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => $this->t('Please wait...'),
        ),
      ],
    );

    if (!isset($candidate_entitytypefields)) {
      $inputvalue_options = array();
    }
    elseif (isset($entitytype_selection)) {
      $inputvalue_options = $candidate_entitytypefields[$entitytype_selection];
    }
    else {
      $inputvalue_options = array_values($candidate_entitytypefields)[0];
    }

    $inputvalue_default_value = count($inputsettings) == 0 ? 0 : $inputsettings['inputvalue'];

    $form['input']['inputvalue'] = array(
      '#type' => 'select',
      '#title' => $this->t('Value'),
      '#options' => $inputvalue_options,
      '#default_value' => $inputvalue_default_value,
      '#prefix' => '<div id="edit-input-value-wrapper">',
      '#suffix ' => '</div>',
      '#validated' => 1,
    );

    $form['output'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Output Settings'),
      '#tree' => TRUE,
    );

    $form['output']['outputvalue'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $config->get('output.outputvalue'),
    );

    $form['output']['renderlink'] = array(
      '#type' => 'checkbox',
      '#title' => 'Render as link',
      '#default_value' => $config->get('output.renderlink'),
    );

    $form['output']['renderlinktextbox'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $config->get('output.renderlinktextbox'),
      '#states' => array(
        'visible' => array(
          ':input[name="output[renderlink]"]' => array('checked' => TRUE),
        ),
      ),
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['output']['tokens'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
        '#theme_wrappers' => ['form_element'],
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save Mentions Type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.mentions_type.list');
  }

  public function changeEntityTypeInForm(array &$form, FormStateInterface $form_state) {
    $entitytype_state = $form_state->getValue(array('input', 'entity_type'));
    $entitytype_info = $this->entityManager->getDefinition($entitytype_state);
    $id = $entitytype_info->getKey('id');
    $label = $entitytype_info->getKey('label');
    if ($entitytype_state == 'user') {
      $label = 'name';
    }
    unset($form['input']['inputvalue']['#options']);
    unset($form['input']['inputvalue']['#default_value']);
    $form['input']['inputvalue']['#options'][$id] = $id;
    $form['input']['inputvalue']['#options'][$label] = $label;
    $form['input']['inputvalue']['#default_value'] = $id;
    return $form['input']['inputvalue'];
  }

}
