<?php

namespace Drupal\search_api\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\UnsavedIndexConfiguration;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\Routing\Route;

/**
 * Converts search indexes from path parameters to a temporary copy.
 *
 * This is done so that certain pages (like the "Fields" tab) can modify indexes
 * over several page requests without permanently saving the index in between.
 *
 * The code for this is largely taken from the views_ui module.
 */
class SearchApiConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * The shared temporary storage factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new SearchApiConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, SharedTempStoreFactory $temp_store_factory, AccountInterface $user) {
    parent::__construct($entity_manager);

    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    /** @var \Drupal\search_api\IndexInterface $entity */
    if (!($entity = parent::convert($value, $definition, $name, $defaults))) {
      return NULL;
    }

    // Get the temp store for this variable if it needs one. Attempt to load the
    // index from the temp store, update the currently logged-in user's ID and
    // store the lock metadata.
    $store = $this->tempStoreFactory->get('search_api_index');
    $current_user_id = $this->currentUser->id() ?: session_id();
    /** @var \Drupal\search_api\IndexInterface|\Drupal\search_api\UnsavedConfigurationInterface $index */
    if ($index = $store->get($value)) {
      $index->setCurrentUserId($current_user_id);
      $index->setLockInformation($store->getMetadata($value));
    }
    // Otherwise, create a new temporary copy of the search index.
    else {
      $index = new UnsavedIndexConfiguration($entity, $store, $current_user_id);
    }

    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      return !empty($definition['tempstore']) && $definition['type'] === 'entity:search_api_index';
    }
    return FALSE;
  }

}
