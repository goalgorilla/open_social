<?php

namespace Drupal\search_api\Plugin\search_api\datasource;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a datasource which exposes the content entities.
 *
 * @SearchApiDatasource(
 *   id = "entity",
 *   deriver = "Drupal\search_api\Plugin\search_api\datasource\ContentEntityDeriver"
 * )
 */
class ContentEntity extends DatasourcePluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  protected $entityFieldManager;

  /**
   * The entity display repository manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null
   */
  protected $entityTypeBundleInfo;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|null
   */
  protected $typedDataManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!empty($configuration['index']) && $configuration['index'] instanceof IndexInterface) {
      $this->setIndex($configuration['index']);
      unset($configuration['index']);
    }

    // Since defaultConfiguration() depends on the plugin definition, we need to
    // override the constructor and set the definition property before calling
    // that method.
    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $datasource */
    $datasource = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var $entity_type_manager \Drupal\Core\Entity\EntityTypeManagerInterface */
    $entity_type_manager = $container->get('entity_type.manager');
    $datasource->setEntityTypeManager($entity_type_manager);

    /** @var $entity_field_manager \Drupal\Core\Entity\EntityFieldManagerInterface */
    $entity_field_manager = $container->get('entity_field.manager');
    $datasource->setEntityFieldManager($entity_field_manager);

    /** @var $entity_display_repo \Drupal\Core\Entity\EntityDisplayRepositoryInterface */
    $entity_display_repo = $container->get('entity_display.repository');
    $datasource->setEntityDisplayRepository($entity_display_repo);

    /** @var $entity_type_bundle_info \Drupal\Core\Entity\EntityTypeBundleInfoInterface */
    $entity_type_bundle_info = $container->get('entity_type.bundle.info');
    $datasource->setEntityTypeBundleInfo($entity_type_bundle_info);

    /** @var \Drupal\Core\TypedData\TypedDataManager $typed_data_manager */
    $typed_data_manager = $container->get('typed_data_manager');
    $datasource->setTypedDataManager($typed_data_manager);

    /** @var $config_factory \Drupal\Core\Config\ConfigFactoryInterface */
    $config_factory = $container->get('config.factory');
    $datasource->setConfigFactory($config_factory);

    /** @var $language_manager \Drupal\Core\Language\LanguageManagerInterface */
    $language_manager = $container->get('language_manager');
    $datasource->setLanguageManager($language_manager);

    return $datasource;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Retrieves the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager ?: \Drupal::getContainer()->get('entity_field.manager');
  }

  /**
   * Retrieves the entity display repository.
   *
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity entity display repository.
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository ?: \Drupal::getContainer()->get('entity_display.repository');
  }

  /**
   * Retrieves the entity display repository.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity entity display repository.
   */
  public function getEntityTypeBundleInfo() {
    return $this->entityTypeBundleInfo ?: \Drupal::getContainer()->get('entity_type.bundle.info');
  }

  /**
   * Retrieves the entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getEntityStorage() {
    return $this->getEntityTypeManager()->getStorage($this->pluginDefinition['entity_type']);
  }

  /**
   * Returns the definition of this datasource's entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getEntityType() {
    return $this->getEntityTypeManager()->getDefinition($this->getEntityTypeId());
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Sets the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The new entity field manager.
   *
   * @return $this
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
    return $this;
  }

  /**
   * Sets the entity display repository.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   *
   * @return $this
   */
  public function setEntityDisplayRepository(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
    return $this;
  }

  /**
   * Sets the entity type bundle info.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The new entity type bundle info.
   *
   * @return $this
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    return $this;
  }

  /**
   * Retrieves the typed data manager.
   *
   * @return \Drupal\Core\TypedData\TypedDataManager
   *   The typed data manager.
   */
  public function getTypedDataManager() {
    return $this->typedDataManager ?: \Drupal::typedDataManager();
  }

  /**
   * Sets the typed data manager.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The new typed data manager.
   *
   * @return $this
   */
  public function setTypedDataManager(TypedDataManager $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
    return $this;
  }

  /**
   * Retrieves the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Retrieves the config value for a certain key in the Search API settings.
   *
   * @param string $key
   *   The key whose value should be retrieved.
   *
   * @return mixed
   *   The config value for the given key.
   */
  protected function getConfigValue($key) {
    return $this->getConfigFactory()->get('search_api.settings')->get($key);
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The new config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Retrieves the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function getLanguageManager() {
    return $this->languageManager ?: \Drupal::languageManager();
  }

  /**
   * Sets the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The new language manager.
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $type = $this->getEntityTypeId();
    $properties = $this->getEntityFieldManager()->getBaseFieldDefinitions($type);
    if ($bundles = array_keys($this->getBundles())) {
      foreach ($bundles as $bundle_id) {
        $properties += $this->getEntityFieldManager()->getFieldDefinitions($type, $bundle_id);
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    $allowed_languages = $all_languages = $this->getLanguageManager()->getLanguages();

    if ($this->isTranslatable()) {
      $selected_languages = array_flip($this->configuration['languages']);
      if ($this->configuration['default']) {
        $allowed_languages = array_diff_key($all_languages, $selected_languages);
      }
      else {
        $allowed_languages = array_intersect_key($all_languages, $selected_languages);
      }
    }
    // Always allow items with undefined language. (Can be the case when
    // entities are created programmatically.)
    $allowed_languages[LanguageInterface::LANGCODE_NOT_SPECIFIED] = TRUE;

    $entity_ids = array();
    foreach ($ids as $item_id) {
      list($entity_id, $langcode) = explode(':', $item_id, 2);
      if (isset($allowed_languages[$langcode])) {
        $entity_ids[$entity_id][$item_id] = $langcode;
      }
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->getEntityStorage()->loadMultiple(array_keys($entity_ids));
    $missing = array();
    $items = array();
    foreach ($entity_ids as $entity_id => $langcodes) {
      foreach ($langcodes as $item_id => $langcode) {
        // @todo Also refuse to load entities from not-included bundles? This
        //   would help to avoid possible race conditions when removing bundles
        //   from the datasource config. See #2574583.
        if (!empty($entities[$entity_id]) && $entities[$entity_id]->hasTranslation($langcode)) {
          $items[$item_id] = $entities[$entity_id]->getTranslation($langcode)->getTypedData();
        }
        else {
          $missing[] = $item_id;
        }
      }
    }
    // If we were unable to load some of the items, mark them as deleted.
    // @todo The index should be responsible for this, not individual
    //   datasources. See #2574589.
    if ($missing) {
      $this->getIndex()->trackItemsDeleted($this->getPluginId(), array_keys($missing));
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $new_config) {
    $old_config = $this->getConfiguration();
    $new_config += $this->defaultConfiguration();
    $this->configuration = $new_config;

    // We'd now check the items of which bundles need to be added to or removed
    // from tracking for this index. However, if the index is not tracking at
    // all (i.e., is disabled) currently, we can skip all that.
    if (!$this->index->status() || !$this->index->hasValidTracker()) {
      return;
    }

    // Including "$old_config != $new_config" for this "if" would make sense –
    // however, since 0 == 'article', that leads to wrong results. "!==", on the
    // other hand, is too restrictive, since it also checks order. Therefore,
    // this can only be added once we prepare the "bundles" setting to be just a
    // list of the checked ones.
    if ($this->hasBundles()) {
      // First, check if the "default" setting changed and invert the set
      // bundles for the old config, so the following comparison makes sense.
      // @todo If the available bundles changed in between, this will still
      //   produce wrong results. Also, we should definitely only store a
      //   numerically-indexed array of the selected bundles, not the
      //   "checkboxes" raw format. This will, very likely, also resolve that
      //   issue. See #2471535.
      if ($old_config['default'] != $new_config['default']) {
        foreach ($old_config['bundles'] as $bundle_key => $bundle) {
          if ($bundle_key == $bundle) {
            $old_config['bundles'][$bundle_key] = 0;
          }
          else {
            $old_config['bundles'][$bundle_key] = $bundle_key;
          }
        }
      }

      // Now, go through all the bundles and start/stop tracking for them
      // accordingly.
      $bundles_start = array();
      $bundles_stop = array();
      if ($diff = array_diff_assoc($new_config['bundles'], $old_config['bundles'])) {
        foreach ($diff as $bundle_key => $bundle) {
          if ($new_config['default'] == 0) {
            if ($bundle_key === $bundle) {
              $bundles_start[$bundle_key] = $bundle;
            }
            else {
              $bundles_stop[$bundle_key] = $bundle;
            }
          }
          else {
            if ($bundle_key === $bundle) {
              $bundles_stop[$bundle_key] = $bundle;
            }
            else {
              $bundles_start[$bundle_key] = $bundle;
            }
          }
        }
        // @todo Make this use a batch instead, like when enabling a datasource.
        //   See #2574611.
        if (!empty($bundles_start)) {
          if ($entity_ids = $this->getBundleItemIds(array_keys($bundles_start))) {
            $this->getIndex()->trackItemsInserted($this->getPluginId(), $entity_ids);
          }
        }
        if (!empty($bundles_stop)) {
          if ($entity_ids = $this->getBundleItemIds(array_keys($bundles_stop))) {
            $this->getIndex()->trackItemsDeleted($this->getPluginId(), $entity_ids);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array();
    if ($this->hasBundles() || $this->isTranslatable()) {
      $default_configuration['default'] = '1';
    }

    if ($this->hasBundles()) {
      $default_configuration['bundles'] = array();
    }

    if ($this->isTranslatable()) {
      $default_configuration['languages'] = array();
    }

    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->hasBundles() || $this->isTranslatable()) {
      $form['default'] = array(
        '#type' => 'radios',
        '#title' => $this->t('What should be indexed?'),
        '#options' => array(
          1 => $this->t('All except those selected'),
          0 => $this->t('None except those selected'),
        ),
        '#default_value' => $this->configuration['default'],
      );
    }

    if ($this->hasBundles()) {
      $bundles = $this->getEntityBundleOptions();
      $form['bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundles,
        '#default_value' => $this->configuration['bundles'],
        '#size' => min(4, count($bundles)),
        '#multiple' => TRUE,
      );
    }

    if ($this->isTranslatable()) {
      $form['languages'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Languages'),
        '#options' => $this->getTranslationOptions(),
        '#default_value' => array_combine($this->configuration['languages'], $this->configuration['languages']),
        '#multiple' => TRUE,
      );
    }

    return $form;
  }

  /**
   * Retrieves the available bundles of this entity type as an options list.
   *
   * @return array
   *   An associative array of bundle labels, keyed by the bundle name.
   */
  protected function getEntityBundleOptions() {
    $options = array();
    if (($bundles = $this->getEntityBundles())) {
      foreach ($bundles as $bundle => $bundle_info) {
        $options[$bundle] = Html::escape($bundle_info['label']);
      }
    }
    return $options;
  }

  /**
   * Retrieves the available languages of this entity type as an options list.
   *
   * @return array
   *   An associative array of language labels, keyed by the language name.
   */
  protected function getTranslationOptions() {
    $options = array();
    foreach ($this->getLanguageManager()->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $languages = $form_state->getValue('languages', array());
    $languages = array_keys(array_filter($languages));
    $form_state->setValue('languages', $languages);

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    if ($item instanceof EntityAdapter) {
      return $item->getValue()->id() . ':' . $item->getValue()->language()->getId();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemLabel(ComplexDataInterface $item) {
    if ($item instanceof EntityAdapter) {
      return $item->getValue()->label();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemBundle(ComplexDataInterface $item) {
    if ($item instanceof EntityAdapter) {
      return $item->getValue()->bundle();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl(ComplexDataInterface $item) {
    if ($item instanceof EntityAdapter) {
      $entity = $item->getValue();
      if ($entity->hasLinkTemplate('canonical')) {
        return $entity->toUrl('canonical');
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds($page = NULL) {
    return $this->getBundleItemIds(NULL, $page);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $summary = '';

    // Add bundle information in the description.
    if ($this->hasBundles()) {
      $bundles = array_values(array_intersect_key($this->getEntityBundleOptions(), array_filter($this->configuration['bundles'])));
      if ($this->configuration['default']) {
        $summary .= $this->t('Excluded bundles: @bundles', array('@bundles' => implode(', ', $bundles)));
      }
      else {
        $summary .= $this->t('Included bundles: @bundles', array('@bundles' => implode(', ', $bundles)));
      }
    }

    // Add language information in the description.
    if ($this->isTranslatable()) {
      if ($summary) {
        $summary .= '; ';
      }
      $languages = array_intersect_key($this->getTranslationOptions(), array_flip($this->configuration['languages']));
      if ($this->configuration['default']) {
        $summary .= $this->t('Excluded languages: @languages', array('@languages' => implode(', ', $languages)));
      }
      else {
        $summary .= $this->t('Included languages: @languages', array('@languages' => implode(', ', $languages)));
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['entity_type'];
  }

  /**
   * Determines whether the entity type supports bundles.
   *
   * @return bool
   *   TRUE if the entity type supports bundles, FALSE otherwise.
   */
  protected function hasBundles() {
    return $this->getEntityType()->hasKey('bundle');
  }

  /**
   * Determines whether the entity type supports translations.
   *
   * @return bool
   *   TRUE if the entity is translatable, FALSE otherwise.
   */
  protected function isTranslatable() {
    return $this->getEntityType()->isTranslatable();
  }

  /**
   * Retrieves all bundles of this datasource's entity type.
   *
   * @return array
   *   An associative array of bundle infos, keyed by the bundle names.
   */
  protected function getEntityBundles() {
    return $this->hasBundles() ? $this->getEntityTypeBundleInfo()->getBundleInfo($this->getEntityTypeId()) : array();
  }

  /**
   * Retrieves all item IDs of entities of the specified bundles.
   *
   * @param string[]|null $bundles
   *   (optional) The bundles for which all item IDs should be returned; or NULL
   *   to retrieve IDs from all enabled bundles in this datasource.
   * @param int|null $page
   *   The zero-based page of IDs to retrieve, for the paging mechanism
   *   implemented by this datasource; or NULL to retrieve all items at once.
   *
   * @return string[]
   *   An array of all item IDs of these bundles.
   */
  protected function getBundleItemIds(array $bundles = NULL, $page = NULL) {
    // If NULL was passed, use all enabled bundles.
    if (!isset($bundles)) {
      $bundles = array_keys($this->getBundles());
    }

    $select = \Drupal::entityQuery($this->getEntityTypeId());
    // If there are bundles to filter on, and they don't include all available
    // bundles, add the appropriate condition.
    if ($bundles && $this->hasBundles()) {
      if (count($bundles) != count($this->getEntityBundles())) {
        $select->condition($this->getEntityType()->getKey('bundle'), $bundles, 'IN');
      }
    }
    if (isset($page)) {
      $page_size = $this->getConfigValue('tracking_page_size');
      $select->range($page * $page_size, $page_size);
    }
    $entity_ids = $select->execute();

    if (!$entity_ids) {
      return NULL;
    }

    // For all the loaded entities, compute all their item IDs (one for each
    // translation).
    $item_ids = array();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($this->getEntityStorage()->loadMultiple($entity_ids) as $entity_id => $entity) {
      foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
        $item_ids[] = "$entity_id:$langcode";
      }
    }

    return $item_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    if (!$this->hasBundles()) {
      // For entity types that have no bundle, return a default pseudo-bundle.
      return array($this->getEntityTypeId() => $this->label());
    }

    $configuration = $this->getConfiguration();

    // If "default" is TRUE (i.e., "All except those selected"), remove all the
    // selected bundles from the available ones to compute the indexed bundles.
    // Otherwise, return all the selected bundles.
    $bundles = array();
    $entity_bundles = $this->getEntityBundles();
    $selected_bundles = array_filter($configuration['bundles']);
    $function = $configuration['default'] ? 'array_diff_key' : 'array_intersect_key';
    $entity_bundles = $function($entity_bundles, $selected_bundles);
    foreach ($entity_bundles as $bundle_id => $bundle_info) {
      $bundles[$bundle_id] = isset($bundle_info['label']) ? $bundle_info['label'] : $bundle_id;
    }
    return $bundles ?: array($this->getEntityTypeId() => $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($bundle = NULL) {
    if (isset($bundle)) {
      return $this->getEntityDisplayRepository()->getViewModeOptionsByBundle($this->getEntityTypeId(), $bundle);
    }
    else {
      return $this->getEntityDisplayRepository()->getViewModeOptions($this->getEntityTypeId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewItem(ComplexDataInterface $item, $view_mode, $langcode = NULL) {
    try {
      if ($item instanceof EntityAdapter) {
        $entity = $item->getValue();
        $langcode = $langcode ?: $entity->language()->getId();
        return $this->getEntityTypeManager()->getViewBuilder($this->getEntityTypeId())->view($entity, $view_mode, $langcode);
      }
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultipleItems(array $items, $view_mode, $langcode = NULL) {
    try {
      $view_builder = $this->getEntityTypeManager()->getViewBuilder($this->getEntityTypeId());
      // Langcode passed, use that for viewing.
      if (isset($langcode)) {
        $entities = array();
        foreach ($items as $i => $item) {
          if ($item instanceof EntityAdapter) {
            $entities[$i] = $item->getValue();
          }
        }
        if ($entities) {
          return $view_builder->viewMultiple($entities, $view_mode, $langcode);
        }
        return array();
      }
      // Otherwise, separate the items by language, keeping the keys.
      $items_by_language = array();
      foreach ($items as $i => $item) {
        if ($item instanceof EntityInterface) {
          $items_by_language[$item->language()->getId()][$i] = $item;
        }
      }
      // Then build the items for each language. We initialize $build beforehand
      // and use array_replace() to add to it so the order stays the same.
      $build = array_fill_keys(array_keys($items), array());
      foreach ($items_by_language as $langcode => $language_items) {
        $build = array_replace($build, $view_builder->viewMultiple($language_items, $view_mode, $langcode));
      }
      return $build;
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies += parent::calculateDependencies();

    $this->addDependency('module', $this->getEntityType()->getProvider());

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDependencies(array $fields) {
    $dependencies = array();
    $properties = $this->getPropertyDefinitions();

    foreach ($fields as $field_id => $property_path) {
      $dependencies[$field_id] = $this->getPropertyPathDependencies($property_path, $properties);
    }

    return $dependencies;
  }

  /**
   * Computes all dependencies of the given property path.
   *
   * @param string $property_path
   *   The property path of the property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The properties which form the basis for the property path.
   *
   * @return string[][]
   *   An associative array with the dependencies for the given property path,
   *   mapping dependency types to arrays of dependency names.
   */
  protected function getPropertyPathDependencies($property_path, array $properties) {
    $dependencies = array();

    list($key, $nested_path) = Utility::splitPropertyPath($property_path, FALSE);
    if (!isset($properties[$key])) {
      return $dependencies;
    }

    $property = $properties[$key];
    if ($property instanceof FieldConfigInterface) {
      $storage = $property->getFieldStorageDefinition();
      if ($storage instanceof FieldStorageConfigInterface) {
        $name = $storage->getConfigDependencyName();
        $dependencies[$storage->getConfigDependencyKey()][$name] = $name;
      }
    }

    $property = Utility::getInnerProperty($property);

    if ($property instanceof EntityDataDefinitionInterface) {
      $entity_type_definition = $this->getEntityTypeManager()
        ->getDefinition($property->getEntityTypeId());
      if ($entity_type_definition) {
        $module = $entity_type_definition->getProvider();
        $dependencies['module'][$module] = $module;
      }
    }

    if (isset($nested_path) && $property instanceof ComplexDataDefinitionInterface) {
      $nested_dependencies = $this->getPropertyPathDependencies($nested_path, $property->getPropertyDefinitions());
      foreach ($nested_dependencies as $type => $names) {
        $dependencies += array($type => array());
        $dependencies[$type] += $names;
      }
    }

    return array_map('array_values', $dependencies);
  }

  /**
   * Returns an array of config entity dependencies.
   *
   * @param string $entity_type_id
   *   The entity type to which the fields are attached.
   * @param string[] $fields
   *   An array of property paths of fields from this entity type.
   * @param string[] $all_fields
   *   An array of property paths of all the fields from this datasource.
   *
   * @return string[]
   *   An array keyed by the IDs of entities on which this datasource depends.
   *   The values are containing list of Search API fields.
   */
  public function getFieldDependenciesForEntityType($entity_type_id, array $fields, array $all_fields) {
    $field_dependencies = array();

    // Figure out which fields are directly on the item and which need to be
    // extracted from nested items.
    $direct_fields = array();
    $nested_fields = array();
    foreach ($fields as $field) {
      if (strpos($field, ':entity:') !== FALSE) {
        list($direct, $nested) = explode(':entity:', $field, 2);
        $nested_fields[$direct][] = $nested;
      }
      else {
        // Support nested Search API fields.
        $base_field_name = explode(':', $field, 2)[0];
        $direct_fields[$base_field_name] = TRUE;
      }
    }

    // Extract the config dependency name for direct fields.
    foreach (array_keys($this->getEntityTypeBundleInfo()->getBundleInfo($entity_type_id)) as $bundle) {
      foreach ($this->getEntityFieldManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
        if ($field_definition instanceof FieldConfigInterface) {
          if (isset($direct_fields[$field_name]) || isset($nested_fields[$field_name])) {
            // Make a mapping of dependencies and fields that depend on them.
            $storage_definition = $field_definition->getFieldStorageDefinition();
            if (!$storage_definition instanceof EntityInterface) {
              continue;
            }
            $dependency = $storage_definition->getConfigDependencyName();
            $search_api_fields = array();

            // Get a list of enabled fields on the datasource.
            foreach ($all_fields as $field_id => $property_path) {
              if (strpos($property_path, $field_definition->getName()) !== FALSE) {
                $search_api_fields[] = $field_id;
              }
            }
            $field_dependencies[$dependency] = $search_api_fields;
          }

          // Recurse for nested fields.
          if (isset($nested_fields[$field_name])) {
            $entity_type = $field_definition->getSetting('target_type');
            $field_dependencies += $this->getFieldDependenciesForEntityType($entity_type, $nested_fields[$field_name], $all_fields);
          }
        }
      }
    }

    return $field_dependencies;
  }

  /**
   * Retrieves all indexes that are configured to index the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to check.
   *
   * @return \Drupal\search_api\IndexInterface[]
   *   All indexes that are configured to index the given entity (using this
   *   datasource class).
   */
  public static function getIndexesForEntity(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $datasource_id = 'entity:' . $entity_type;
    $entity_bundle = $entity->bundle();

    $index_names = \Drupal::entityQuery('search_api_index')
      ->condition('datasource_settings.*.plugin_id', $datasource_id)
      ->execute();

    if (!$index_names) {
      return array();
    }

    // Needed for PhpStorm. See https://youtrack.jetbrains.com/issue/WI-23395.
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = Index::loadMultiple($index_names);

    // If the datasource's entity type supports bundles, we have to filter the
    // indexes for whether they also include the specific bundle of the given
    // entity. Otherwise, we are done.
    if ($entity_type !== $entity_bundle) {
      foreach ($indexes as $index_id => $index) {
        try {
          $config = $index->getDatasource($datasource_id)->getConfiguration();
          $default = !empty($config['default']);
          $bundle_set = !empty($config['bundles'][$entity_bundle]);
          if ($default == $bundle_set) {
            unset($indexes[$index_id]);
          }
        }
        catch (SearchApiException $e) {
          unset($indexes[$index_id]);
        }
      }
    }

    return $indexes;
  }

}
