<?php

namespace Drupal\social_tagging\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\social_tagging\SocialTaggingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialTaggingSettingsForm.
 *
 * @package Drupal\social_tagging\Form
 */
class SocialTaggingSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The cache tags invalidator.
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * The helper.
   */
  private SocialTaggingServiceInterface $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    /** @var self $instance */
    $instance = parent::create($container);

    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');
    $instance->cacheTagsInvalidator = $container->get('cache_tags.invalidator');
    $instance->helper = $container->get('social_tagging.tag_service');

    return $instance;
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
    $config = $this->config($this->getEditableConfigNames()[0]);

    $form['enable_content_tagging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to tag content in content.'),
      '#default_value' => $config->get('enable_content_tagging'),
    ];

    $form['allow_category_split'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow category split.'),
      '#default_value' => $config->get('allow_category_split'),
      '#description' => $this->t("Determine if the main categories of the vocabury will be used as seperate tag fields or as a single tag field when using tags on content."),
    ];

    $form['use_category_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow use a parent of category.'),
      '#default_value' => $config->get('use_category_parent'),
      '#description' => $this->t("Determine if the parent of categories will be used with children tags."),
      '#states' => [
        'visible' => [
          ':input[name="allow_category_split"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['use_and_condition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When filtering use AND condition.'),
      '#default_value' => $config->get('use_and_condition'),
      '#description' => $this->t("When filtering with multiple terms use AND condition in the query."),
    ];

    $form['node_type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Type configuration'),
    ];

    $types = $this->helper->types();

    ksort($types);
    asort($types);

    $content_types = [];

    foreach ($types as $entity_type => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);

      if ($definition === NULL) {
        continue;
      }

      $label = $entity_type === 'node'
        ? $this->t('Node') : $definition->getLabel();

      if ($bundles) {
        $bundle_entity_type = $definition->getBundleEntityType();

        if ($bundle_entity_type === NULL) {
          continue;
        }

        $storage = $this->entityTypeManager->getStorage($bundle_entity_type);

        $prefix = $entity_type . '_';
      }
      else {
        $prefix = '';
        $suffix = $entity_type;
      }

      foreach ($bundles ?: [$entity_type] as $type) {
        if ($bundles) {
          if (!isset($storage) || ($entity = $storage->load($type)) === NULL) {
            continue;
          }

          $title = $this->t('@entity_type type: @bundle', [
            '@entity_type' => $label,
            '@bundle' => $bundle = $entity->label(),
          ]);

          if ($entity_type === 'node') {
            $content_types[] = $bundle;
          }

          $suffix = $type;
        }
        else {
          $title = $label;
        }

        if (isset($suffix)) {
          $key = "tag_{$prefix}type_$suffix";

          $form['node_type_settings']['tag_type_' . $type] = [
            '#type' => 'checkbox',
            '#title' => $title,
            '#default_value' => $config->get($key) ?: !empty($bundles),
          ];
        }
      }
    }

    $form['enable_content_tagging']['#description'] = $this->t(
      'Determine whether users are allowed to tag content, view tags and filter on tags in content. (@content)',
      ['@content' => implode(', ', $content_types)],
    );

    $form['some_text_field']['#markup'] = '<p><strong>' . Link::createFromRoute($this->t('Click here to go to the social tagging overview'), 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'social_tagging'])->toString() . '</strong></p>';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the configuration file.
    $config = $this->config($this->getEditableConfigNames()[0]);

    if ($form_state->getValue('allow_category_split')) {
      $config->set(
        'use_category_parent',
        $form_state->getValue('use_category_parent'),
      );
    }
    else {
      $config->clear('use_category_parent')->save();
    }

    if ($form_state->hasValue('tag_type_profile')) {
      $result = $this->database->select('cachetags', 'ct')
        ->fields('ct', ['tag'])
        ->condition('ct.tag', 'profile:%', 'LIKE')
        ->execute();

      if ($result !== NULL) {
        // Clear cache tags of profiles.
        $this->cacheTagsInvalidator->invalidateTags($result->fetchCol());
      }
    }

    $fields = [
      'enable_content_tagging',
      'allow_category_split',
      'use_and_condition',
      ...Element::children($form['node_type_settings']),
    ];

    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
