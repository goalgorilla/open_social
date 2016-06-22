<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Condition\GroupType.
 */

namespace Drupal\group\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Group Type' condition.
 *
 * @Condition(
 *   id = "group_type",
 *   label = @Translation("Group type"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group", label = @Translation("Group"))
 *   }
 * )
 *
 */
class GroupType extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new GroupType instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('group_type'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    // Build a list of group type labels.
    $group_types = $this->entityStorage->loadMultiple();
    foreach ($group_types as $type) {
      $options[$type->id()] = $type->label();
    }

    // Show a series of checkboxes for group type selection.
    $form['group_types'] = [
      '#title' => $this->t('Group types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['group_types'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['group_types'] = array_filter($form_state->getValue('group_types'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $group_types = $this->configuration['group_types'];

    // Format a pretty string if multiple group types were selected.
    if (count($group_types) > 1) {
      $last = array_pop($group_types);
      $group_types = implode(', ', $group_types);
      return $this->t('The group type is @group_types or @last', ['@group_types' => $group_types, '@last' => $last]);
    }

    // If just one was selected, return a simpler string.
    return $this->t('The group type is @group_type', ['@group_type' => reset($group_types)]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // If there are no group types selected and the condition is not negated, we
    // return TRUE because it means all group types are valid.
    if (empty($this->configuration['group_types']) && !$this->isNegated()) {
      return TRUE;
    }

    // Check if the group type of the group context was selected.
    $group = $this->getContextValue('group');
    return !empty($this->configuration['group_types'][$group->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['group_types' => []] + parent::defaultConfiguration();
  }

}
