<?php

namespace Drupal\social_tagging\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialTaggingSettingsForm.
 *
 * @package Drupal\social_tagging\Form
 */
class SocialTaggingSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_tagging_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_tagging.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_tagging.settings');

    /** @var \Drupal\node\NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $content_types = [];
    foreach ($node_types as $node_type) {
      $content_types[] = $node_type->get('name');
    }

    $form['enable_content_tagging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to tag content in content.'),
      '#default_value' => $config->get('enable_content_tagging'),
      '#required' => FALSE,
      '#description' => $this->t("Determine whether users are allowed to tag content, view tags and filter on tags in content. (@content)", ['@content' => implode(', ', $content_types)]),
    ];

    $form['allow_category_split'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow category split.'),
      '#default_value' => $config->get('allow_category_split'),
      '#required' => FALSE,
      '#description' => $this->t("Determine if the main categories of the vocabury will be used as seperate tag fields or as a single tag field when using tags on content."),
    ];

    $form['use_category_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow use a parent of category.'),
      '#default_value' => $config->get('use_category_parent'),
      '#required' => FALSE,
      '#description' => $this->t("Determine if the parent of categories will be used with children tags."),
      '#states' => [
        'visible' => [
          ':input[name="allow_category_split"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['node_type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Type configuration'),
    ];

    $form['node_type_settings']['tag_type_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group'),
      '#default_value' => $config->get('tag_type_group'),
      '#required' => FALSE,
    ];

    $form['node_type_settings']['tag_type_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Profile'),
      '#default_value' => $config->get('tag_type_profile'),
      '#required' => FALSE,
    ];

    foreach ($node_types as $nodetype) {
      $field_name = 'tag_node_type_' . $nodetype->id();
      $value = $config->get($field_name);
      $default_value = isset($value) ? $config->get($field_name) : TRUE;
      $form['node_type_settings'][$field_name] = [
        '#type' => 'checkbox',
        '#title' => $nodetype->label(),
        '#default_value' => $default_value,
        '#required' => FALSE,
      ];
    }

    $form['some_text_field']['#markup'] = '<p><strong>' . Link::createFromRoute($this->t('Click here to go to the social tagging overview'), 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'social_tagging'])->toString() . '</strong></p>';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the configuration file.
    $config = $this->config('social_tagging.settings');
    $config->set('enable_content_tagging', $form_state->getValue('enable_content_tagging'))->save();
    $config->set('allow_category_split', $form_state->getValue('allow_category_split'))->save();
    $config->set('tag_type_group', $form_state->getValue('tag_type_group'))->save();
    $config->set('tag_type_profile', $form_state->getValue('tag_type_profile'))->save();

    if ($form_state->getValue('allow_category_split')) {
      $config->set('use_category_parent', $form_state->getValue('use_category_parent'))->save();
    }
    else {
      $config->clear('use_category_parent')->save();
    }

    // Clear cache tags of profiles.
    $query = $this->database->select('cachetags', 'ct');
    $query->fields('ct', ['tag']);
    $query->condition('ct.tag', 'profile:%', 'LIKE');
    $result = $query->execute()->fetchCol();
    $this->cacheTagsInvalidator->invalidateTags($result);

    /** @var \Drupal\node\NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $config_name = 'tag_node_type_' . $node_type->id();
      $config->set($config_name, $form_state->getValue($config_name))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
