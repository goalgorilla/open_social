<?php

namespace Drupal\social_activity_filter\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a settings form of activity filter.
 *
 * @package Drupal\social_activity_filter\Form
 */
class FilterSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_activity_filter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_activity_filter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_activity_filter.settings');

    $form['social_activity_filter'] = [
      '#type' => 'fieldset',
    ];

    $displays = [];
    foreach (social_activity_default_views_list() as $views_id) {
      $displays = array_merge($displays, $this->getDisplayBlocks($views_id));
    }

    $form['social_activity_filter']['blocks'] = [
      '#type' => 'checkboxes',
      '#markup' => '<div class="fieldset__description">' . $this->t('Please select the blocks in which the taxonomy filters can be used.') . '</div>',
      '#title' => $this->t('Select blocks'),
      '#options' => $displays,
      '#default_value' => $config->get('blocks'),
      '#required' => TRUE,
    ];

    $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $taxonomy_vocabularies = $storage->loadMultiple();

    $vocabulariesList = [];

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    foreach ($taxonomy_vocabularies as $vid => $vocabulary) {
      $vocabulariesList[$vid] = $vocabulary->get('name');
    }

    $form['social_activity_filter']['vocabulary'] = [
      '#type' => 'checkboxes',
      '#markup' => '<div class="fieldset__description">' . $this->t('Please select the taxonomy vocabularies that can be used in the taxonomy filters.') . '</div>',
      '#title' => $this->t('Select taxonomy vocabularies'),
      '#options' => $vocabulariesList,
      '#default_value' => $config->get('vocabulary'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_activity_filter.settings');

    $vocabularies = array_filter($form_state->getValue('vocabulary'));
    $fields = $this->getReferencedTaxonomyFields($vocabularies);

    $blocks = $form_state->getValue('blocks');

    $config->set('vocabulary', $vocabularies);
    $config->set('taxonomy_fields', $fields);
    $config->set('blocks', $blocks);
    $config->save();

    foreach ($blocks as $id => $block) {
      $tag_filter = $block ? TRUE : FALSE;
      [$viws_id, $display_id] = explode('__', $id);
      $this->updateDisplayBlock($viws_id, $display_id, $tag_filter);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to find all referenced taxonomy fields.
   *
   * @param array $vocabulary_list
   *   Array of vocabulary id's.
   *
   * @return array
   *   Mapped array: vid => taxonomy_field.
   */
  public function getReferencedTaxonomyFields(array $vocabulary_list) {

    $content_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();

    $field_names = [];
    foreach ($vocabulary_list as $vocabulary) {

      foreach ($content_types as $content_type => $type) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $content_type);

        foreach ($field_definitions as $field_definition) {

          if ($field_definition->getType() == 'entity_reference' && $field_definition->getSetting('target_type') == 'taxonomy_term') {
            $handler_settings = $field_definition->getSetting('handler_settings');

            if (isset($handler_settings['target_bundles'][$vocabulary])) {

              if (isset($field_names[$vocabulary])) {
                continue;
              }

              $field_names[$vocabulary] = $field_definition->getName();
            }
          }
        }
      }
    }
    return $field_names;
  }

  /**
   * Gets all displays blocks of views.
   *
   * @param string $views_id
   *   Views ID.
   *
   * @return array
   *   Mapped array of views displays.
   */
  public function getDisplayBlocks($views_id) {
    $view = $this->entityTypeManager->getStorage('view')->load($views_id);

    $blocks = [];
    foreach ($view->get('display') as $display) {
      if ($display['display_plugin'] === 'block') {
        $blocks["{$views_id}__{$display['id']}"] = $display['display_title'];
      }
    }
    return $blocks;
  }

  /**
   * Update settings of displays views blocks.
   *
   * @param string $views_id
   *   Views ID.
   * @param string $display_id
   *   Display ID.
   * @param bool $enabled
   *   Flag to update/cleanup values.
   */
  public function updateDisplayBlock($views_id, $display_id, $enabled = FALSE) {
    $config = $this->configFactory->getEditable("views.view.{$views_id}");
    $override_tags_filter = "display.{$display_id}.display_options.override_tags_filter";
    $activity_filter_tags = "display.{$display_id}.display_options.filters.activity_filter_tags";

    if ($enabled) {
      $config->set($override_tags_filter, 1);
      $config->set($activity_filter_tags, social_activity_get_tag_filter_data());
    }
    else {
      $config->clear($override_tags_filter);
      $config->clear($activity_filter_tags);
    }

    $config->save();
  }

}
