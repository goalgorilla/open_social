<?php

namespace Drupal\search_api;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a listing of search index entities.
 */
class IndexListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity storage class for the 'search_api_server' entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $serverStorage;

  /**
   * Constructs an IndexListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $server_storage
   *   The entity storage class for the 'search_api_server' entity type.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityStorageInterface $server_storage) {
    parent::__construct($entity_type, $storage);

    $this->serverStorage = $server_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('search_api_server')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity instanceof IndexInterface) {
      $route_parameters['search_api_index'] = $entity->id();
      $operations['fields'] = array(
        'title' => $this->t('Fields'),
        'weight' => 20,
        'url' => new Url('entity.search_api_index.fields', $route_parameters),
      );
      $operations['processors'] = array(
        'title' => $this->t('Processors'),
        'weight' => 30,
        'url' => new Url('entity.search_api_index.processors', $route_parameters),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return array(
      'type' => $this->t('Type'),
      'title' => $this->t('Name'),
      'status' => array(
        'data' => $this->t('Status'),
        'class' => array('checkbox'),
      ),
    ) + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $row = parent::buildRow($entity);

    $status = $entity->status();
    $status_server = TRUE;
    $status_label = $status ? $this->t('Enabled') : $this->t('Disabled');

    if ($entity instanceof ServerInterface && $entity->status() && !$entity->isAvailable()) {
      $status = FALSE;
      $status_server = FALSE;
      $status_label = $this->t('Unavailable');
    }

    $status_icon = array(
      '#theme' => 'image',
      '#uri' => $status ? 'core/misc/icons/73b355/check.svg' : 'core/misc/icons/e32700/error.svg',
      '#width' => 18,
      '#height' => 18,
      '#alt' => $status_label,
      '#title' => $status_label,
    );

    $row = array(
      'data' => array(
        'type' => array(
          'data' => $entity instanceof ServerInterface ? $this->t('Server') : $this->t('Index'),
          'class' => array('search-api-type'),
        ),
        'title' => array(
          'data' => array(
            '#type' => 'link',
            '#title' => $entity->label(),
            '#suffix' => '<div>' . $entity->get('description') . '</div>',
          ) + $entity->toUrl('canonical')->toRenderArray(),
          'class' => array('search-api-title'),
        ),
        'status' => array(
          'data' => $status_icon,
          'class' => array('checkbox'),
        ),
        'operations' => $row['operations'],
      ),
      'title' => $this->t('ID: @name', array('@name' => $entity->id())),
      'class' => array(
        Html::cleanCssIdentifier($entity->getEntityTypeId() . '-' . $entity->id()),
        $status ? 'search-api-list-enabled' : 'search-api-list-disabled',
        $entity instanceof ServerInterface ? 'search-api-list-server' : 'search-api-list-index',
      ),
    );

    if (!$status_server) {
      $row['class'][] = 'color-error';
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entity_groups = $this->loadGroups();
    $list['#type'] = 'container';
    $list['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    $list['servers'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => $entity_groups['lone_indexes'] ? '' : $this->t('There are no servers or indexes defined. For a quick start, we suggest you install the Database Search Defaults module.'),
      '#attributes' => array(
        'id' => 'search-api-entity-list',
        'class' => array('search-api-entity-list'),
      ),
    );
    foreach ($entity_groups['servers'] as $server_groups) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
      foreach ($server_groups as $entity) {
        $list['servers']['#rows'][$entity->getEntityTypeId() . '.' . $entity->id()] = $this->buildRow($entity);
      }
    }

    // Output the list of indexes without a server separately.
    if (!empty($entity_groups['lone_indexes'])) {
      $list['lone_indexes']['heading']['#markup'] = '<h3>' . $this->t('Indexes not currently associated with any server') . '</h3>';
      $list['lone_indexes']['table'] = array(
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => array(),
      );

      foreach ($entity_groups['lone_indexes'] as $entity) {
        $list['lone_indexes']['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }

    return $list;
  }

  /**
   * Loads search servers and indexes, grouped by servers.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[][]
   *   An associative array with two keys:
   *   - servers: All available search servers, each followed by all search
   *     indexes attached to it.
   *   - lone_indexes: All search indexes that aren't attached to any server.
   */
  public function loadGroups() {
    $indexes = $this->storage->loadMultiple();
    /** @var \Drupal\search_api\ServerInterface[] $servers */
    $servers = $this->serverStorage->loadMultiple();

    $this->sortByStatusThenAlphabetically($indexes);
    $this->sortByStatusThenAlphabetically($servers);

    $server_groups = array();
    foreach ($servers as $server) {
      $server_group = array(
        'server.' . $server->id() => $server,
      );

      foreach ($server->getIndexes() as $index) {
        $server_group['index.' . $index->id()] = $index;
        // Remove this index from $index so it will finally only contain those
        // indexes not belonging to any server.
        unset($indexes[$index->id()]);
      }

      $server_groups['server.' . $server->id()] = $server_group;
    }

    return array(
      'servers' => $server_groups,
      'lone_indexes' => $indexes,
    );
  }

  /**
   * Sorts an array of entities by status and then alphabetically.
   *
   * Will preserve the key/value association of the array.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $entities
   *   An array of config entities.
   */
  protected function sortByStatusThenAlphabetically(array &$entities) {
    uasort($entities, function (ConfigEntityInterface $a, ConfigEntityInterface $b) {
      if ($a->status() == $b->status()) {
        return strnatcasecmp($a->label(), $b->label());
      }
      else {
        return $a->status() ? -1 : 1;
      }
    });
  }

}
