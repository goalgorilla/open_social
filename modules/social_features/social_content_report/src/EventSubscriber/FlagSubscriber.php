<?php

namespace Drupal\social_content_report\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;

/**
 * Class FlagSubscriber.
 */
class FlagSubscriber implements EventSubscriberInterface {

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
   * Creates a DiffFormatter to render diffs in a table.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->unpublishImmediately = $config_factory->get('social_content_report.settings')->get('unpublish_immediately');
    $this->entityTypeManager = $entity_type_manager;
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
    // Do nothing unless we need to unpublish the entity immediately.
    // @todo Consider changing the strpos() to a custom hook.
    if ($this->unpublishImmediately && strpos($event->getFlagging()->getFlagId(), 'report_') === 0) {
      $flagging = $event->getFlagging();

      // Retrieve the entity.
      $entity = $this->entityTypeManager->getStorage($flagging->getFlaggable()->getEntityTypeId())
        ->load($flagging->getFlaggable()->id());

      try {
        $entity->setPublished(FALSE);
        $entity->save();
      }
      catch (EntityStorageException $exception) {
        // @todo Log this exception.
      }
    }

  }

}
