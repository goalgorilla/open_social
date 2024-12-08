<?php

namespace Drupal\mentions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\mentions\MentionsPluginManager;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class MentionsTypeForm.
 *
 * @package Drupal\mentiona\Form
 */
class MentionsTypeForm extends EntityForm {

  use ConfigFormBaseTrait;

  /**
   * The mentions plugin manager.
   *
   * @var \Drupal\mentions\MentionsPluginManager
   */
  protected MentionsPluginManager $mentionsManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected EntityTypeRepositoryInterface $entityTypeRepository;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(MentionsPluginManager $mentions_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, ModuleHandlerInterface $module_handler) {
    $this->mentionsManager = $mentions_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.mentions'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'mentions.mentions_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mentions_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $plugin_names = $this->mentionsManager->getPluginNames();
    $entity = $this->entity;
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $input_settings = $entity->get('input');
    $entity_id = $entity->id();
    $all_entity_types = array_keys($this->entityTypeRepository->getEntityTypeLabels());
    $candidate_entity_types = [];
    foreach ($all_entity_types as $entity_type) {
      $entity_type_info = $this->entityTypeManager->getDefinition($entity_type);
      if ($entity_type_info === NULL) {
        continue;
      }

      $config_entity_class_name = ContentEntityType::class;
      $entity_type_type = get_class($entity_type_info);
      if ($entity_type_type === $config_entity_class_name) {
        $candidate_entity_label = $entity_type_info->getLabel();
        if (!$candidate_entity_label instanceof TranslatableMarkup) {
          continue;
        }
        $candidate_entity_types[$entity_type] = $candidate_entity_label->getUntranslatedString();
        $candidate_entity_type_fields[$entity_type][$entity_type_info->getKey('id')] = $entity_type_info->getKey('id');

        if ($entity_type === 'user') {
          $candidate_entity_type_fields[$entity_type]['name'] = 'name';
        }
        else {
          $candidate_entity_type_fields[$entity_type][$entity_type_info->getKey('label')] = $entity_type_info->getKey('label');
        }
      }
    }

    $config = $this->config('mentions.mentions_type.' . $entity_id);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#description' => $this->t('The human-readable name of this mention type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#default_value' => $config->get('name'),
    ];

    $form['mention_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Mention Type'),
      '#options' => $plugin_names,
      '#default_value' => $config->get('mention_type'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Describe this mention type.'),
      '#default_value' => $config->get('description'),
    ];

    $form['input'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Input Settings'),
      '#tree' => TRUE,
    ];

    $form['input']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $config->get('input.prefix'),
      '#size' => 2,
    ];

    $form['input']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $config->get('input.suffix'),
      '#size' => 2,
    ];

    $entitytype_selection = $config->get('input.entity_type');

    $form['input']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $candidate_entity_types,
      '#default_value' => $entitytype_selection,
      '#ajax' => [
        'callback' => [$this, 'changeEntityTypeInForm'],
        'wrapper' => 'edit-input-value-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Please wait...'),
        ],
      ],
    ];

    if (!isset($candidate_entity_type_fields)) {
      $inputvalue_options = [];
    }
    elseif (isset($entitytype_selection)) {
      $inputvalue_options = $candidate_entity_type_fields[$entitytype_selection];
    }
    else {
      $inputvalue_options = array_values($candidate_entity_type_fields)[0];
    }

    $inputvalue_default_value = count($input_settings) == 0 ? 0 : $input_settings['inputvalue'];

    $form['input']['inputvalue'] = [
      '#type' => 'select',
      '#title' => $this->t('Value'),
      '#options' => $inputvalue_options,
      '#default_value' => $inputvalue_default_value,
      '#prefix' => '<div id="edit-input-value-wrapper">',
      '#suffix ' => '</div>',
      '#validated' => 1,
    ];

    $form['output'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Output Settings'),
      '#tree' => TRUE,
    ];

    $form['output']['outputvalue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $config->get('output.outputvalue'),
    ];

    $form['output']['renderlink'] = [
      '#type' => 'checkbox',
      '#title' => 'Render as link',
      '#default_value' => $config->get('output.renderlink'),
    ];

    $form['output']['renderlinktextbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $config->get('output.renderlinktextbox'),
      '#states' => [
        'visible' => [
          ':input[name="output[renderlink]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['output']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
        '#theme_wrappers' => ['form_element'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save Mentions Type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.mentions_type.list');
  }

  /**
   * {@inheritdoc}
   */
  public function changeEntityTypeInForm(array &$form, FormStateInterface $form_state): mixed {
    $entity_type_state = $form_state->getValue(['input', 'entity_type']);
    $entity_type_info = $this->entityTypeManager->getDefinition($entity_type_state);
    if ($entity_type_info === NULL) {
      return $form['input']['inputvalue'];
    }

    $id = $entity_type_info->getKey('id');
    $label = $entity_type_info->getKey('label');
    if ($entity_type_state === 'user') {
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
