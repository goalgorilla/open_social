<?php

namespace Drupal\social_profile_hide_real_names\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventSubscriber.
 *
 * @package Drupal\social_profile_hide_real_names\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal entity type handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * ConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection.
   * @param \Drupal\user\UserDataInterface $user_data
   *   A user data service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    UserDataInterface $user_data
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'configSave',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function configSave(ConfigCrudEvent $event) {
    // A list of configuration changes that trigger an index update.
    $triggers = [
      'social_profile_privacy.settings' => 'allow_hide_real_names.status',
    ];

    // If the config that changed is part of our trigger list and the value that
    // changed is one we're interested in, perform the re-index.
    $configName = $event->getConfig()->getName();
    if (isset($triggers[$configName]) && $event->isChanged($triggers[$configName])) {
      // If SM disable global "hide real names" settings we remove all
      // user data already created by users and re-index their profiles.
      if (!$event->getConfig()->get('allow_hide_real_names.status')) {
        // Get users ids that already updated their profile.
        $query = $this->connection->select('users_data');
        $query->addField('users_data', 'uid');
        $query->condition('module', 'social_profile_privacy');
        $query->condition('name', 'hide_real_names');
        $uids = $query->execute()->fetchAllKeyed(0, 0);

        if ($uids) {
          foreach ($uids as $uid) {
            // Delete "hide_real_names" status.
            $this->userData->delete('social_profile_privacy', $uid, 'hide_real_names');
            // Remove fields.
            $fields = (array) $this->userData->get('social_profile_privacy', $uid, 'fields');
            unset($fields['field_profile_first_name'], $fields['field_profile_last_name'], $fields['field_profile_nick_name']);
            $this->userData->set('social_profile_privacy', $uid, 'fields', $fields);
          }

          $this->invalidateProfilesForSearchIndexes($uids);
        }
      }
    }
  }

  /**
   * Invalidates the search indices for every index that uses profile data.
   *
   * @param array $uids
   *   An array of users ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function invalidateProfilesForSearchIndexes(array $uids) : void {
    // If the search api module is not installed we have nothing to do.
    if (!$this->moduleHandler->moduleExists('search_api')) {
      return;
    }

    $profileIds = $this->entityTypeManager
      ->getStorage('profile')
      ->getQuery()
      ->condition('uid', $uids, 'IN')
      ->execute();

    // Users without profiles?
    if (empty($profileIds)) {
      return;
    }

    $profiles = $this->entityTypeManager
      ->getStorage('profile')
      ->loadMultiple($profileIds);

    // We load all indexes, we assume there will never be hundreds of search
    // indexes which would create its own problems for a site.
    $indexes = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->loadMultiple();

    /** @var \Drupal\search_api\IndexInterface $index */
    foreach ($indexes as $index) {
      // Check if the search index has profile entities as data source.
      if ($index->isValidDatasource('entity:profile')) {
        foreach ($profiles as $profile) {
          // Mark a profile as re-indexed.
          $index->trackItemsUpdated('entity:profile', [$profile->id() . ':und']);
        }
      }
    }
  }

}
