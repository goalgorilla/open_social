<?php

namespace Drupal\social_profile_privacy\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventSubscriber.
 *
 * @package Drupal\social_profile_privacy\EventSubscriber
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
   * A way for this module to log messages.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   A way for this module to log messages.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_channel_factory
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_channel_factory->get('social_profile_privacy');
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
   * @throws \Drupal\search_api\SearchApiException
   */
  public function configSave(ConfigCrudEvent $event) {
    // A list of configuration changes that trigger an index update.
    $triggers = [
      'social_profile_privacy.settings' => 'limit_search_and_mention',
      'social_profile_fields.settings' => 'profile_profile_field_profile_nick_name',
    ];

    // If the config that changed is part of our trigger list and the value that
    // changed is one we're interested in, perform the re-index.
    $config_name = $event->getConfig()->getName();
    if (isset($triggers[$config_name]) && $event->isChanged($triggers[$config_name])) {
      $this->invalidateSearchIndices();
    }
  }

  /**
   * Invalidates the search indices for every index that uses profile data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function invalidateSearchIndices() : void {
    // If the search api module is not installed we have nothing to do.
    if (!$this->moduleHandler->moduleExists('search_api')) {
      return;
    }

    // We load all indexes, we assume there will never be hundreds of search
    // indexes which would create its own problems for a site.
    $indexes = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->loadMultiple();

    /** @var \Drupal\search_api\IndexInterface $index */
    foreach ($indexes as $index) {
      // Check if the search index has profile entities as data source.
      if ($index->isValidDatasource('entity:profile')) {
        // Mark any indexed items based on profile entities as having changed so
        // they are re-indexed.
        $index->getTrackerInstance()->trackAllItemsUpdated('entity:profile');
      }
    }
  }

}
