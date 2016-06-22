<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\ZoneMember\ZoneMemberBase.
 */

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Model\AddressInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines a base zone member class.
 */
abstract class ZoneMemberBase extends PluginBase implements ZoneMemberInterface {

  /**
   * The parent zone.
   *
   * @var \Drupal\address\Entity\ZoneInterface
   */
  protected $parentZone;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    if (isset($plugin_definition['parent_zone'])) {
      $this->parentZone = $plugin_definition['parent_zone'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => '',
      'name' => '',
      'weight' => 0,
      'plugin' => $this->pluginDefinition['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#member'] = $this;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['name'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->configuration['name'] = $form_state->getValue('name');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->configuration['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->configuration['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParentZone() {
    return $this->parentZone;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function match(AddressInterface $address);

}
