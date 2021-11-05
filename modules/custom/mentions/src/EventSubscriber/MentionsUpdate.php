<?php

namespace Drupal\mentions\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MentionsUpdate handles event 'mentions.update'.
 */
class MentionsUpdate implements EventSubscriberInterface {

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * MentionsUpdate constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events['mentions.update'][] = ['onMentionsUpdate', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onMentionsUpdate($event) {
    $config = $this->configFactory->get('mentions.settings');
    $config_mentions_events = $config->get('mentions_events');
    $action_id = $config_mentions_events['update'];
    if (empty($action_id)) {
      return;
    }
    $entity_storage = $this->entityTypeManager->getStorage('action');
    $action = $entity_storage->load($action_id);

    if ($action === NULL) {
      return;
    }

    $action_plugin = $action->getPlugin();

    if (!empty($action_plugin)) {
      $action_plugin->execute(FALSE);
    }
  }

}
