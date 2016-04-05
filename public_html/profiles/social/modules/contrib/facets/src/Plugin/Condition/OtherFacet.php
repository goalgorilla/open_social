<?php

namespace Drupal\facets\Plugin\Condition;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'other facet' condition.
 *
 * This adds a condition plugin to make sure that facets can depend on other
 * facet's or their values. The facet value is a freeform textfield and works on
 * both raw and display values of the results.
 *
 * @Condition(
 *   id = "other_facet",
 *   label = @Translation("Other facet"),
 * )
 */
class OtherFacet extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The facet entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The user that's currently logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Creates a new instance of the condition.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged in user.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The default facet manager class.
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
  public function __construct(EntityStorageInterface $entity_storage, BlockManager $block_manager, AccountProxyInterface $current_user, DefaultFacetManager $facet_manager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetStorage = $entity_storage;
    $this->blockManager = $block_manager;
    $this->currentUser = $current_user;
    $this->facetManager = $facet_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('facets_facet'),
      $container->get('plugin.manager.block'),
      $container->get('current_user'),
      $container->get('facets.manager'),
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

    // Loop over all defined blocks and filter them by provider, this builds an
    // array of blocks that are provided by the facets module.
    foreach ($this->blockManager->getDefinitions() as $definition) {
      if ($definition['provider'] == 'facets') {
        $options[$definition['id']] = $definition['label'];
      }
    }

    $form['facets'] = [
      '#title' => $this->t('Other facet blocks'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $this->configuration['facets'],
    ];
    $form['facet_value'] = [
      '#title' => $this->t('Facet value'),
      '#description' => $this->t('Only applies when a facet is already selected.'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['facet_value'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['facets'] = $form_state->getValue('facets');
    $this->configuration['facet_value'] = $form_state->getValue('facet_value');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t(
      'The facet is @facet also rendered on the same page.',
      ['@facet' => $this->configuration['facets']]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $allowed_facet_value = $this->configuration['facet_value'];
    $allowed_facets = $this->configuration['facets'];

    // Return as early as possible when there are no settings for allowed
    // facets.
    if (empty($allowed_facets)) {
      return TRUE;
    }

    /** @var \Drupal\facets\Plugin\Block\FacetBlock $block_plugin */
    $block_plugin = $this->blockManager->createInstance($allowed_facets);

    // Allowed facet value is not set, so we only have to check if the block is
    // shown here by running the access method on the block plugin with the
    // currently logged in user.
    if (empty($allowed_facet_value)) {
      return $block_plugin->access($this->currentUser);
    }

    // The block plugin id is saved in the schema: BasePluginID:FacetID. This
    // means we can explode the ID on ':' and the facet id is in the last part
    // of that result.
    $block_plugin_id  = $block_plugin->getPluginId();
    $facet_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $block_plugin_id)[1];

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->facetStorage->load($facet_id);
    $facet = $this->facetManager->returnProcessedFacet($facet);

    foreach ($facet->getResults() as $result) {
      $is_value = $result->getRawValue() == $allowed_facet_value || $result->getDisplayValue() == $allowed_facet_value;
      if ($is_value && $result->isActive()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = ['facets' => FALSE, 'facet_value' => FALSE];
    return $config + parent::defaultConfiguration();
  }

}
