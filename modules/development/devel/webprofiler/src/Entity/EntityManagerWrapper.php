<?php

/**
 * @file
 * Contains \Drupal\webprofiler\Entity\EntityManagerWrapper.
 */

namespace Drupal\webprofiler\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityManagerWrapper
 */
class EntityManagerWrapper extends DefaultPluginManager implements EntityManagerInterface, ContainerAwareInterface {

  /**
   * @var array
   */
  private $loaded;

  /**
   * @var array
   */
  private $rendered;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  private $entityManager;

  /**
   * Config entities that don't support the EntityStorageDecorator.
   *
   * @var array
   */
  private $disallowedEntities;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;

    $this->disallowedEntities = ['user_role', 'shortcut_set', 'taxonomy_vocabulary'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type) {
    /** @var ConfigEntityStorageInterface $handler */
    $handler = $this->getHandler($entity_type, 'storage');

    if ($this->allowedType(
        $entity_type
      ) && $handler instanceof ConfigEntityStorageInterface
    ) {
      if (!isset($this->loaded[$entity_type])) {
        $handler = new EntityStorageDecorator($handler);
        $this->loaded[$entity_type] = $handler;
      }
      else {
        $handler = $this->loaded[$entity_type];
      }
    }

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type) {
    /** @var EntityViewBuilderInterface $handler */
    $handler = $this->getHandler($entity_type, 'view_builder');

    if ($handler instanceof EntityViewBuilderInterface) {
      if (!isset($this->rendered[$entity_type])) {
        $handler = new EntityViewBuilderDecorator($handler);
        $this->rendered[$entity_type] = $handler;
      }
      else {
        $handler = $this->rendered[$entity_type];
      }
    }

    return $handler;
  }

  /**
   * Returns TRUE if we can provide a decorator for this entity type.
   *
   * @param string $entity_type
   *
   * @return bool
   */
  private function allowedType($entity_type) {
    return !in_array($entity_type, $this->disallowedEntities);
  }

  /**
   * @param $type
   *
   * @return array
   */
  public function getLoaded($type) {
    return isset($this->loaded[$type]) ? $this->loaded[$type] : NULL;
  }

  /**
   * @param $type
   *
   * @return array
   */
  public function getRendered($type) {
    return isset($this->rendered[$type]) ? $this->rendered[$type] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->entityManager->useCaches($use_caches = FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->entityManager->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle, $entity_type_id) {
    $this->entityManager->onBundleCreate($bundle, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) {
    $this->entityManager->onBundleDelete($bundle, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels($group = FALSE) {
    return $this->entityManager->getEntityTypeLabels($group = FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions($entity_type_id) {
    return $this->entityManager->getBaseFieldDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($entity_type_id, $bundle) {
    return $this->entityManager->getFieldDefinitions($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinitions($entity_type_id) {
    return $this->entityManager->getFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id) {
    return $this->entityManager->getLastInstalledFieldStorageDefinitions(
      $entity_type_id
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    return $this->entityManager->getFieldMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapByFieldType($field_type) {
    return $this->entityManager->getFieldMapByFieldType($field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($entity_type) {
    return $this->entityManager->getAccessControlHandler($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBundleInfo() {
    return $this->entityManager->getAllBundleInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedFieldDefinitions() {
    $this->entityManager->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedBundles() {
    $this->entityManager->clearCachedBundles();
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type) {
    return $this->entityManager->getListBuilder($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type, $operation) {
    return $this->entityManager->getFormObject($entity_type, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviders($entity_type) {
    return $this->entityManager->getRouteProviders($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandler($entity_type, $handler_type) {
    return $this->entityManager->hasHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type, $handler_type) {
    return $this->entityManager->getHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance(
    $class,
    EntityTypeInterface $definition = NULL
  ) {
    return $this->entityManager->createHandlerInstance($class, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo($entity_type) {
    return $this->entityManager->getBundleInfo($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFields($entity_type_id, $bundle) {
    return $this->entityManager->getExtraFields($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(
    EntityInterface $entity,
    $langcode = NULL,
    $context = []
  ) {
    return $this->entityManager->getTranslationFromContext(
      $entity,
      $langcode,
      $context
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    return $this->entityManager->getDefinition(
      $entity_type_id,
      $exception_on_invalid
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledDefinition($entity_type_id) {
    return $this->entityManager->getLastInstalledDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->entityManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllViewModes() {
    return $this->entityManager->getAllViewModes();
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($entity_type_id) {
    return $this->entityManager->getViewModes($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFormModes() {
    return $this->entityManager->getAllFormModes();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModes($entity_type_id) {
    return $this->entityManager->getFormModes($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptions(
    $entity_type_id,
    $include_disabled = FALSE
  ) {
    return $this->entityManager->getViewModeOptions(
      $entity_type_id,
      $include_disabled
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptions($entity_type_id) {
    return $this->entityManager->getFormModeOptions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->entityManager->getViewModeOptionsByBundle(
      $entity_type_id,
      $bundle
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->entityManager->getFormModeOptionsByBundle(
      $entity_type_id,
      $bundle
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    return $this->entityManager->loadEntityByUuid($entity_type_id, $uuid);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByConfigTarget($entity_type_id, $target) {
    return $this->entityManager->loadEntityByConfigTarget(
      $entity_type_id,
      $target
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeFromClass($class_name) {
    return $this->entityManager->getEntityTypeFromClass($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->entityManager->onEntityTypeCreate($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(
    EntityTypeInterface $entity_type,
    EntityTypeInterface $original
  ) {
    $this->entityManager->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->entityManager->onEntityTypeDelete($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->entityManager->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(
    FieldDefinitionInterface $field_definition
  ) {
    $this->entityManager->onFieldDefinitionCreate($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(
    FieldDefinitionInterface $field_definition,
    FieldDefinitionInterface $original
  ) {
    $this->entityManager->onFieldDefinitionUpdate($field_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(
    FieldDefinitionInterface $field_definition
  ) {
    $this->entityManager->onFieldDefinitionDelete($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(
    FieldStorageDefinitionInterface $storage_definition
  ) {
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(
    FieldStorageDefinitionInterface $storage_definition,
    FieldStorageDefinitionInterface $original
  ) {
    $this->entityManager->onFieldStorageDefinitionUpdate(
      $storage_definition,
      $original
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(
    FieldStorageDefinitionInterface $storage_definition
  ) {
    $this->entityManager->onFieldStorageDefinitionDelete($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->entityManager->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->entityManager->setContainer($container = NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function clearDisplayModeInfo() {
    return $this->entityManager->clearDisplayModeInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMap(array $field_map) {
    return $this->entityManager->setFieldMap($field_map);
  }

}
