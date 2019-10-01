<?php

namespace Drupal\social_profile_privacy\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\search_api\SearchApiException;
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
   */
  public function configSave(ConfigCrudEvent $event) {
    // We're only interested in the settings for this module.
    if ($event->getConfig()->getName() !== 'social_profile_privacy.settings') {
      return;
    }

    // We only need to act if the setting controlling our custom processor has
    // changed.
    if (!$event->isChanged('limit_search_and_mention')) {
      return;
    }

    // If the search api module is not installed we have nothing to do.
    if (!$this->moduleHandler->moduleExists('search_api')) {
      return;
    }

    // We load all indexes, we assume there will never be hundreds of search
    // indexes which would create its own problems for a site.
    $indexes = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->loadMultiple();

    // Check if the search index has profile entities as data source in which
    // case we'll have to re-index all items with the change in settings.
    /** @var \Drupal\search_api\IndexInterface $index */
    foreach ($indexes as $index) {
      if ($index->isValidDatasource('entity:profile')) {
        try {
          $index->reindex();
        }
        catch (SearchApiException $exception) {
          $this->logger->error(
            'Could not mark index "@index" for re-indexing. @exception',
            [
              '@index' => $index->label(),
              '@exception' => $exception->getMessage(),
            ]
          );
        }
      }
    }
  }

}
