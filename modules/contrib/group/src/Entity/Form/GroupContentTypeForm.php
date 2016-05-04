<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupContentTypeForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for group type forms.
 */
class GroupContentTypeForm extends EntityForm {

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupContentTypeForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The group content plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * Returns the configurable plugin for the group content type.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The configurable group content enabler plugin.
   */
  protected function getContentPlugin() {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    $group_content_type = $this->getEntity();
    $group_type = $group_content_type->getGroupType();

    // Initialize an empty plugin so we can show a default configuration form.
    if ($this->operation == 'add') {
      $plugin_id = $group_content_type->getContentPluginId();
      $configuration['id'] = $plugin_id;
      $configuration['group_type'] = $group_type->id();

      return $this->pluginManager->createInstance($plugin_id, $configuration);
    }
    // Return the already configured plugin for existing group content types.
    else {
      return $group_content_type->getContentPlugin();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    $group_content_type = $this->getEntity();
    $group_type = $group_content_type->getGroupType();
    $plugin = $this->getContentPlugin();

    // @todo These messages may need some love.
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Install content plugin');
      $message = 'By installing the %plugin plugin, you will allow %entity_type entities to be added to groups of type %group_type';
    }
    else {
      $form['#title'] = $this->t('Configure content plugin');
      $message = 'This form allows you to configure the %plugin plugin for the %group_type group type.';
    }

    // Add in the replacements for the $message variable set above.
    $replace = [
      '%plugin' => $plugin->getLabel(),
      '%entity_type' => $this->entityTypeManager->getDefinition($plugin->getEntityTypeId())->getLabel(),
      '%group_type' => $group_type->label(),
    ];

    // Display a description to explain the purpose of the form.
    $form['description'] = [
      '#markup' => $this->t($message, $replace),
    ];

    // Add in the plugin configuration form.
    $form += $plugin->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->operation == 'add' ? $this->t('Install plugin') : $this->t('Save configuration'),
      '#submit' => array('::submitForm'),
    );

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin = $this->getContentPlugin();
    $plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    $group_content_type = $this->getEntity();
    $group_type = $group_content_type->getGroupType();
    $plugin = $this->getContentPlugin();
    $plugin->submitConfigurationForm($form, $form_state);

    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();

    // Extract the values as configuration that should be saved.
    $config = $form_state->getValues();

    if ($this->operation == 'add') {
      $group_type->installContentPlugin($group_content_type->getContentPluginId(), $config);
      drupal_set_message($this->t('The content plugin was installed on the group type.'));
    }
    else {
      $group_type->updateContentPlugin($group_content_type->getContentPluginId(), $config);
      drupal_set_message($this->t('The content plugin configuration was saved.'));
    }

    $form_state->setRedirect('entity.group_type.content_plugins', ['group_type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      return $route_match->getParameter($entity_type_id);
    }

    // If we are on the create form, we can't extract an entity from the route,
    // so we need to create one based on the route parameters.
    $values = [];
    if ($route_match->getRawParameter('group_type') !== NULL && $route_match->getRawParameter('plugin_id') !== NULL) {
      $values = [
        'group_type' => $route_match->getRawParameter('group_type'),
        'content_plugin' => $route_match->getRawParameter('plugin_id'),
      ];
    }
    return $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
  }

}
