<?php

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Model\AddressInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches a single zone.
 *
 * @ZoneMember(
 *   id = "zone",
 *   name = @Translation("Zone"),
 * )
 */
class ZoneMemberZone extends ZoneMemberBase implements ContainerFactoryPluginInterface {

  /**
   * The zone storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $zoneStorage;

  /**
   * Constructs a new ZoneMemberZone object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->zoneStorage = $entity_type_manager->getStorage('zone');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'zone' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['zone'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Zone'),
      '#default_value' => $this->zoneStorage->load($this->configuration['zone']),
      '#target_type' => 'zone',
      '#tags' => FALSE,
      '#required' => TRUE,
      '#selection_settings' => [
        'skip_id' => $this->parentZone->id(),
        'sort' => [
          'field' => 'name',
          'direction' => 'ASC',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $this->configuration['zone'] = $form_state->getValue('zone');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function match(AddressInterface $address) {
    $zone = $this->zoneStorage->load($this->configuration['zone']);
    if ($zone) {
      return $zone->match($address);
    }
  }

}
