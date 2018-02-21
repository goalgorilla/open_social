<?php

namespace Drupal\social_tagging\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\node\Entity\NodeType;
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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
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

    $content_types = [];
    foreach (NodeType::loadMultiple() as $node_type) {
      /* @var NodeType $node_type */
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

    $form['node_type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Node type configuration'),
    ];

    /** @var \Drupal\node\Entity\NodeType $nodetype */
    foreach (NodeType::loadMultiple() as $nodetype) {
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

    /** @var \Drupal\node\Entity\NodeType $nodetype */
    foreach (NodeType::loadMultiple() as $nodetype) {
      $config_name = 'tag_node_type_' . $nodetype->id();
      $config->set($config_name, $form_state->getValue($config_name))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
