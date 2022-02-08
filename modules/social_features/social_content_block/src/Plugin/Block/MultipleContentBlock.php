<?php

namespace Drupal\social_content_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a block to display multiple content types.
 *
 * @Block(
 *   id = "multiple_content_block",
 *   admin_label = @Translation("Custom multiple content list block"),
 *   category = @Translation("Content blocks")
 * )
 */
class MultipleContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The multiple content block manager service.
   *
   * @var \Drupal\social_content_block\Services\MultipleContentBlockManagerInterface
   */
  protected $multipleContentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->database = $container->get('database');
    $instance->multipleContentManager = $container->get('plugin.manager.multiple_content_block');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderEntities();
  }

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    $base_configs = parent::baseConfigurationDefaults();
    $base_configs['sorting'] = 'changed';
    $base_configs['content_types'] = [];
    $base_configs['content_tags'] = [];
    $base_configs['subtitle'] = '';
    $base_configs['amount'] = 5;
    return $base_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Information'),
    ];

    $form['info']['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#maxlength' => 255,
      '#default_value' => $config['subtitle'],
    ];

    $form['content'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content'),
    ];

    // Generate options based on available plugins as a content type.
    $options = [
      'all' => $this->t('All'),
    ];
    foreach ($this->getMultipleContentDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    $form['content']['content_types'] = [
      '#type' => 'select2',
      '#title' => $this->t('Type of content'),
      '#description' => $this->t('Select the type(s) of content which will be shown in this block.'),
      '#required' => TRUE,
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => in_array('all', $config['content_types']) ? ['all'] : $config['content_types'],
    ];

    $terms = [];
    if (!empty($this->getSelectedContentTags())) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadMultiple(array_column($this->getSelectedContentTags(), 'target_id'));
    }
    $form['content']['content_tags'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Content tags'),
      '#description' => $this->t('Enter a comma separated list of content tags.'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['social_tagging'],
      ],
      '#tags' => TRUE,
      '#default_value' => $terms,
    ];

    $form['content']['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 48,
      '#default_value' => $config['amount'],
    ];

    $form['sorting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sorting'),
    ];

    $form['sorting']['sorting'] = [
      '#type' => 'select',
      '#title' => $this->t('Sorting'),
      '#required' => TRUE,
      '#options' => [
        'changed' => $this->t('Last updated'),
        'created' => $this->t('Most recent'),
      ],
      '#default_value' => $config['sorting'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('subtitle', $form_state->getValue([
      'info',
      'subtitle',
    ]));
    $this->setConfigurationValue('content_types', $form_state->getValue([
      'content',
      'content_types',
    ]));
    $this->setConfigurationValue('content_tags', $form_state->getValue([
      'content',
      'content_tags',
    ]));
    $this->setConfigurationValue('amount', $form_state->getValue([
      'content',
      'amount',
    ]));
    $this->setConfigurationValue('sorting', $form_state->getValue([
      'sorting',
      'sorting',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $cache_tags = [];
    foreach ($this->getMultipleContentDefinitions() as $definition) {
      if (
        !in_array('all', $this->getSelectedContentTypes()) &&
        !in_array($definition['id'], $this->getSelectedContentTypes())
      ) {
        continue;
      }
      if (isset($definition['bundle'])) {
        $cache_tags[] = "{$definition['entity_type']}_list:{$definition['bundle']}";
        continue;
      }
      $cache_tags[] = "{$definition['entity_type']}_list";
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['user.permissions'];
  }

  /**
   * Gets all available content type for use.
   */
  protected function getMultipleContentDefinitions(): array {
    return $this->multipleContentManager->getDefinitions();
  }

  /**
   * Returns list of entities as a renderable array.
   */
  protected function renderEntities(): array {
    $entities = $entities_data = $types = $storages = [];

    // Prepare list of content types for a query to get all available entities.
    foreach ($this->getMultipleContentDefinitions() as $definition) {
      if (
        !in_array('all', $this->getSelectedContentTypes()) &&
        !in_array($definition['id'], $this->getSelectedContentTypes())
      ) {
        continue;
      }
      if (isset($definition['bundle'])) {
        $types[$definition['entity_type']][] = $definition['bundle'];
      }
      else {
        $types[$definition['entity_type']] = NULL;
      }
    }

    // Select all entities from multiple content types.
    /**
     * @var string $type
     * @var string[]|null $bundles
     */
    foreach ($types as $type => $bundles) {
      $entities_data = array_merge($entities_data, $this->getEntitiesByDefinitions($type, $bundles));
      $storages[$type] = $this->entityTypeManager->getStorage($type);
    }

    // Sort the list of provided entities.
    arsort($entities_data);
    $iteration = 0;

    foreach (array_keys($entities_data) as $key) {
      if ($iteration >= $this->getAmountItems()) {
        break;
      }
      $keys = explode('__', $key);

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $storages[$keys[0]]->load($keys[1]);

      // Skip if user cannot view provided entity.
      if (!$entity->access('view', $this->currentUser)) {
        continue;
      }

      $entities[] = $this->entityTypeManager->getViewBuilder($keys[0])
        ->view($entity, 'small_teaser');
      $iteration++;
    }

    return $entities;
  }

  /**
   * Returns list of entities and their values for sorting.
   *
   * @param string $type
   *   The content type.
   * @param array|null $bundles
   *   List of bundes.
   *
   * @return array
   *   List of entities for render.
   */
  protected function getEntitiesByDefinitions(string $type, ?array $bundles): array {
    $sorting = $this->getSelectedSorting();
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($type);
    $id_key = $entity_type->getKey('id');

    // Create select based on entity data table.
    if (($data_table = $entity_type->getDataTable()) === NULL) {
      return [];
    }
    $query = $this->database->select($data_table, 'base_table');
    $query->addField('base_table', $id_key);
    $query->addField('base_table', $sorting);

    // Load only default entity.
    $query->condition('default_langcode', '1');

    // Only if plugin has bundles add a condition by type.
    if ($bundles !== NULL && is_string($entity_type->getKey('bundle'))) {
      $query->condition($entity_type->getKey('bundle'), $bundles, 'IN');
    }

    // Only if we select content tags add a condition.
    if (!empty($this->getSelectedContentTags())) {
      $content_tag_table = "{$type}__social_tagging";
      if ($this->database->schema()->tableExists($content_tag_table)) {
        $tags = array_column($this->getSelectedContentTags(), 'target_id');
        $query->innerJoin($content_tag_table, 'st', "st.entity_id = base_table.$id_key");
        $query->condition('st.social_tagging_target_id', $tags, 'IN');
      }
    }

    // Sort by provided sorting field.
    $query->orderBy($sorting, 'DESC');

    // Select only specific number of items provided by block configuration.
    $query->range(0, $this->getAmountItems());
    $result = $query->execute();

    if ($result !== NULL) {
      // Prepare list of entities with values for sorting process.
      foreach ($result->fetchAll() as $row) {
        $entities["{$type}__{$row->{$id_key}}"] = $row->{$sorting};
      }
    }

    return $entities ?? [];
  }

  /**
   * Returns provided amount of items to show.
   */
  protected function getAmountItems(): string {
    return $this->configuration['amount'];
  }

  /**
   * Returns provided content types.
   */
  protected function getSelectedContentTypes(): array {
    return $this->configuration['content_types'];
  }

  /**
   * Returns provided content tags.
   */
  protected function getSelectedContentTags(): array {
    return $this->configuration['content_tags'] ?? [];
  }

  /**
   * Returns provided sorting.
   */
  protected function getSelectedSorting(): string {
    return $this->configuration['sorting'];
  }

}
