<?php


namespace Drupal\search_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\user\SharedTempStore;

/**
 * Represents a configuration of an index that was not yet permanently saved.
 *
 * Proxy code created with:
 * php ./core/scripts/generate-proxy-class.php 'Drupal\search_api\IndexInterface' modules/search_api/src/
 */
class UnsavedIndexConfiguration implements IndexInterface, UnsavedConfigurationInterface {

  /**
   * The proxied index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The shared temporary storage to use.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Either the UID of the currently logged-in user, or the session ID.
   *
   * @var int|string
   */
  protected $currentUserId;

  /**
   * The lock information for this configuration.
   *
   * @var object|null
   */
  protected $lock;

  /**
   * The properties changed in this copy compared to the original.
   *
   * @var string[]
   */
  protected $changedProperties = array();

  /**
   * Constructs a new UnsavedIndexConfiguration.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to proxy.
   * @param \Drupal\user\SharedTempStore $temp_store
   *   The shared temporary storage to use.
   * @param int|string $current_user_id
   *   Either the UID of the currently logged-in user, or the session ID (for
   *   anonymous users).
   */
  public function __construct(IndexInterface $index, SharedTempStore $temp_store, $current_user_id) {
    $this->entity = $index;
    $this->tempStore = $temp_store;
    $this->currentUserId = $current_user_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUserId($current_user_id) {
    $this->currentUserId = $current_user_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanges() {
    return (bool) $this->lock;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    if ($this->lock) {
      return $this->lock->owner != $this->currentUserId;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockOwner(EntityTypeManagerInterface $entity_type_manager = NULL) {
    if (!$this->lock) {
      return NULL;
    }
    $uid = is_numeric($this->lock->owner) ? $this->lock->owner : 0;
    $entity_type_manager = $entity_type_manager ?: \Drupal::entityTypeManager();
    return $entity_type_manager->getStorage('user')->load($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastUpdated() {
    return $this->lock ? $this->lock->updated : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLockInformation($lock = NULL) {
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function savePermanent(EntityTypeManagerInterface $entity_type_manager = NULL) {
    // Make sure to overwrite only those properties that were changed in this
    // copy. Unlike in the Views UI, we have several edit pages for indexes
    // ("Edit", "Fields", "Processors") and only one of them is locked, so this
    // is necessary.
    /** @var \Drupal\search_api\IndexInterface $original */
    $original = $entity_type_manager->getStorage($this->entity->getEntityTypeId())->loadUnchanged($this->entity->id());
    foreach ($this->changedProperties as $property) {
      $original->set($property, $this->entity->get($property));
    }
    $original->save();
    // Setting the saved entity as the wrapped one is important if methods like
    // isReindexing() are called on the object afterwards.
    $this->entity = $original;
    $this->discardChanges();
  }

  /**
   * {@inheritdoc}
   */
  public function discardChanges() {
    $this->tempStore->delete($this->entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->entity->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->entity->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheId($sub_id) {
    return $this->entity->getCacheId($sub_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name, $default = NULL) {
    return $this->entity->getOption($name, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->entity->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $option) {
    $this->changedProperties['options'] = 'options';
    return $this->entity->setOption($name, $option);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->changedProperties['options'] = 'options';
    return $this->entity->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceIds() {
    return $this->entity->getDatasourceIds();
  }

  /**
   * {@inheritdoc}
   */
  public function isValidDatasource($datasource_id) {
    return $this->entity->isValidDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource($datasource_id) {
    return $this->entity->getDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasources($only_enabled = TRUE) {
    return $this->entity->getDatasources($only_enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidTracker() {
    return $this->entity->hasValidTracker();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerId() {
    return $this->entity->getTrackerId();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerInstance() {
    return $this->entity->getTrackerInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidServer() {
    return $this->entity->hasValidServer();
  }

  /**
   * {@inheritdoc}
   */
  public function isServerEnabled() {
    return $this->entity->isServerEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerId() {
    return $this->entity->getServerId();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerInstance() {
    return $this->entity->getServerInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function setServer(ServerInterface $server = NULL) {
    return $this->entity->setServer($server);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors($only_enabled = TRUE) {
    return $this->entity->getProcessors($only_enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE) {
    return $this->entity->getProcessorsByStage($stage, $only_enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    return $this->entity->preprocessIndexItems($items);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    return $this->entity->preprocessSearchQuery($query);
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    return $this->entity->postprocessSearchResults($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->entity->getFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field_id) {
    return $this->entity->getField($field_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsByDatasource($datasource_id) {
    return $this->entity->getFieldsByDatasource($datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFulltextFields() {
    return $this->entity->getFulltextFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions($datasource_id, $alter = TRUE) {
    return $this->entity->getPropertyDefinitions($datasource_id, $alter);
  }

  /**
   * {@inheritdoc}
   */
  public function loadItem($item_id) {
    return $this->entity->loadItem($item_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadItemsMultiple(array $item_ids) {
    return $this->entity->loadItemsMultiple($item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems($limit = -1, $datasource_id = NULL) {
    return $this->entity->indexItems($limit, $datasource_id);
  }

  /**
   * {@inheritdoc}
   */
  public function indexSpecificItems(array $search_objects) {
    return $this->entity->indexSpecificItems($search_objects);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted($datasource_id, array $ids) {
    return $this->entity->trackItemsInserted($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated($datasource_id, array $ids) {
    return $this->entity->trackItemsUpdated($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted($datasource_id, array $ids) {
    return $this->entity->trackItemsDeleted($datasource_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function reindex() {
    return $this->entity->reindex();
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    return $this->entity->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function isReindexing() {
    return $this->entity->isReindexing();
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $options = array()) {
    return $this->entity->query($options);
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    return $this->entity->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    return $this->entity->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->changedProperties['status'] = 'status';
    return $this->entity->setStatus($status);
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($status) {
    return $this->entity->setSyncing($status);
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->entity->status();
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->entity->isSyncing();
  }

  /**
   * {@inheritdoc}
   */
  public function isUninstalling() {
    return $this->entity->isUninstalling();
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    return $this->entity->get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    $this->changedProperties[$property_name] = $property_name;
    return $this->entity->set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->entity->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return $this->entity->onDependencyRemoval($dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return $this->entity->getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    return $this->entity->isInstallable();
  }

  /**
   * {@inheritdoc}
   */
  public function trustData() {
    return $this->entity->trustData();
  }

  /**
   * {@inheritdoc}
   */
  public function hasTrustedData() {
    return $this->entity->hasTrustedData();
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->entity->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->entity->language();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsNew($value = TRUE) {
    return $this->entity->enforceIsNew($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->entity->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = array()) {
    return $this->entity->toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = array()) {
    return $this->entity->toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = array()) {
    return $this->entity->toUrl($rel, $options)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'canonical', array $options = array()) {
    return $this->entity->toLink($text, $rel, $options)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->entity->toLink($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    return $this->entity->hasLinkTemplate($key);
  }

  /**
   * {@inheritdoc}
   */
  public function uriRelationships() {
    return $this->entity->uriRelationships();
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    EntityInterface::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    EntityInterface::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = array()) {
    EntityInterface::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->tempStore->setIfOwner($this->entity->id(), $this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    return $this->entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    return $this->entity->preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    return $this->entity->postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    EntityInterface::preCreate($storage, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    return $this->entity->postCreate($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    EntityInterface::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    EntityInterface::postDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    EntityInterface::postLoad($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    return $this->entity->createDuplicate();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entity->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return $this->entity->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    return $this->entity->getOriginalId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return $this->entity->getCacheTagsToInvalidate();
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    return $this->entity->setOriginalId($id);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->entity->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    return $this->entity->getTypedData();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return $this->entity->getConfigDependencyKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->entity->getConfigDependencyName();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    return $this->entity->getConfigTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->entity->access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->entity->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->entity->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->entity->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    return $this->entity->addCacheContexts($cache_contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    return $this->entity->addCacheTags($cache_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeCacheMaxAge($max_age) {
    return $this->entity->mergeCacheMaxAge($max_age);
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($other_object) {
    return $this->entity->addCacheableDependency($other_object);
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->changedProperties['third_party_settings'] = 'third_party_settings';
    return $this->entity->setThirdPartySetting($module, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->entity->getThirdPartySetting($module, $key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return $this->entity->getThirdPartySettings($module);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    $this->changedProperties['third_party_settings'] = 'third_party_settings';
    return $this->entity->unsetThirdPartySetting($module, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return $this->entity->getThirdPartyProviders();
  }

  /**
   * Adds a field to this index.
   *
   * If the field is already present (with the same datasource and property
   * path) its settings will be updated.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to add, or update.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field could not be added, either because a different field
   *   with the same field ID would be overwritten, or because the field
   *   identifier is one of the pseudo-fields that can be used in search
   *   queries.
   */
  public function addField(FieldInterface $field) {
    // @todo Implement addField() method.
  }

  /**
   * Changes the field ID of a field.
   *
   * @param string $old_field_id
   *   The old ID of the field.
   * @param string $new_field_id
   *   The new ID of the field.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if no field with the old ID exists, or because the new ID is
   *   already taken, or because the new field ID is one of the pseudo-fields
   *   that can be used in search queries.
   */
  public function renameField($old_field_id, $new_field_id) {
    // @todo Implement renameField() method.
  }

  /**
   * Removes a field from the index.
   *
   * If the field doesn't exist, the call will fail silently.
   *
   * @param string $field_id
   *   The ID of the field to remove.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field is locked.
   */
  public function removeField($field_id) {
    // @todo Implement removeField() method.
  }

}
