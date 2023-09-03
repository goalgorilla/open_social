<?php

namespace Drupal\social_tagging\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\social_tagging\TaggingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialTaggingSettingsForm.
 *
 * @package Drupal\social_tagging\Form
 */
class SocialTaggingSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The social tagging manager.
   */
  protected TaggingManager $taggingManager;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TaggingManager $tagging_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->taggingManager = $tagging_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get("social_tagging.manager"),
      $container->get('entity_type.manager'),
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
    $config = $this->config("social_tagging.settings");

    $form['enable_content_tagging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to tag content in content'),
      '#default_value' => $config->get('enable_content_tagging'),
    ];

    $form['use_and_condition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When filtering use AND condition'),
      '#default_value' => $config->get('use_and_condition'),
      '#description' => $this->t("When filtering with multiple terms use AND condition in the query."),
    ];

    $form['type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Type configuration'),
      '#tree' => TRUE,
    ];

    $entities = $this->taggingManager->getInstalledBundles();
    $fields = $this->taggingManager->getInstalledFieldDefinitions();
    foreach ($entities as $entity_type => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      assert($definition !== NULL);
      $bundle_entity_type = $definition->getBundleEntityType();
      assert($bundle_entity_type !== NULL);
      $bundles = $this->entityTypeManager
        ->getStorage($bundle_entity_type)
        ->loadMultiple($bundles);

      if (count($bundles) === 1) {
        $bundle = reset($bundles);
        $field_id = "$entity_type.{$bundle->id()}.field_social_tagging";
        $form['type_settings'][$field_id] = [
          '#type' => 'checkbox',
          '#title' => $bundle->label(),
          '#default_value' => $fields[$field_id]->status(),
        ];
      }
      else {
        foreach ($bundles as $bundle) {
          // Only for nodes do we want the entity label itself to be prefixed,
          // otherwise the bundle's name is descriptive enough and the entity
          // type is an implementation detail.
          $label = $entity_type === "node"
            ? $definition->getLabel() . ": " . $bundle->label()
            : $bundle->label();
          $field_id = "$entity_type.{$bundle->id()}.field_social_tagging";
          $form['type_settings'][$field_id] = [
            '#type' => 'checkbox',
            '#title' => $label,
            '#default_value' => $fields[$field_id]->status(),
          ];
        }
      }

    }

    $form['link_to_overview']['#markup'] = '<p><strong>' . Link::createFromRoute($this->t('Click here to go to the social tagging overview'), 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'social_tagging'])->toString() . '</strong></p>';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config("social_tagging.settings");
    $config->set('enable_content_tagging', $form_state->getValue('enable_content_tagging'));
    $config->set('use_and_condition', $form_state->getValue('use_and_condition'));
    $config->save();

    $type_settings = $form_state->getValue("type_settings");
    $fields = $this->entityTypeManager->getStorage("field_config")->loadMultiple(array_keys($type_settings));
    foreach ($fields as $field) {
      if ($field->status() !== (bool) $type_settings[$field->id()]) {
        $field->setStatus((bool) $type_settings[$field->id()]);
        $field->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
