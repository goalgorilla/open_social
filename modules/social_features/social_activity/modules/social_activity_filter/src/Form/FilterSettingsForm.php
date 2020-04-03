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
 * Class FilterSettingsForm.
 *
 * @package Drupal\unpd_cop\Form
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
    $taxonomyFields = $config->get('taxonomy_fields');

    $form['social_activity_filter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter options list'),
    ];

    $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $taxonomy_vocabularies = $storage->loadMultiple();

    $vocabulariesList = [];

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    foreach ($taxonomy_vocabularies as $vid => $vocabulary) {
      $referencedField = isset($taxonomyFields[$vid]) ? $taxonomyFields[$vid] : $this->t('none');
      $vocabulariesList[$vid] = $vocabulary->get('name') . " (field: $referencedField)";
    }

    $form['social_activity_filter']['vocabulary'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Taxonomy vocabularies'),
      '#options' => $vocabulariesList,
      '#default_value' => $config->get('vocabulary'),
      '#required' => TRUE,
      '#description' => $this->t('Select vocabulary which should be displayed in the "Activity filter block". Note, for selected vocabulary will be automatically added a  taxonomy field that is referenced to the content type of activity.
       Also, this field will be used to filter items in the list. If both content types have the same taxonomy but with different fields then only the first matched field be selected.'),
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

    $config->set('vocabulary', $vocabularies);
    $config->set('taxonomy_fields', $fields);
    $config->save();

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

}
