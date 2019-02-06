<?php

namespace Drupal\social_content_report\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;

/**
 * Class FlagSubscriber.
 */
class FlagSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait, LoggerChannelTrait;

  /**
   * Whether to unpublish the entity immediately on reporting or not.
   *
   * @var bool
   */
  protected $unpublishImmediately;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheInvalidator;

  /**
   * Creates a DiffFormatter to render diffs in a table.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, CacheTagsInvalidatorInterface $cache_invalidator) {
    $this->unpublishImmediately = $config_factory->get('social_content_report.settings')->get('unpublish_threshold');
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->cacheInvalidator = $cache_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    return $events;
  }

  /**
   * Listener for flagging events.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The event when something is flagged.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onFlag(FlaggingEvent $event) {
    $flagging = $flagging = $event->getFlagging();
    $entity_type = $flagging->getFlaggable()->getEntityTypeId();
    $entity_id = $flagging->getFlaggable()->id();
    $invalidated = FALSE;

    // Do nothing unless we need to unpublish the entity immediately.
    // @todo Consider changing the strpos() to a custom hook.
    if ($this->unpublishImmediately && strpos($flagging->getFlagId(), 'report_') === 0) {
      // Retrieve the entity.
      $entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);

      try {
        $entity->setPublished(FALSE);
        $entity->save();
        $invalidated = TRUE;
      }
      catch (EntityStorageException $exception) {
        $this->getLogger('social_content_report')
          ->error(t('@entity_type @entity_id could not be unpublished after a user reported it.', [
            '@entity_type' => $entity_type,
            '@entity_id' => $entity_id,
          ]));
      }
    }

    // In any case log that the report was submitted.
    $this->messenger->addMessage($this->t('Your report is submitted.'));

    // Clear cache tags for entity to remove the Report link.
    if (!$invalidated) {
      $this->cacheInvalidator->invalidateTags([$entity_type . ':' . $entity_id]);
    }
  }

}
