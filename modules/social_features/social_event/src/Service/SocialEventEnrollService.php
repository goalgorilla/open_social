<?php

namespace Drupal\social_event\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\Event;

/**
 * Class SocialEventEnrollService.
 *
 * @package Drupal\social_event\Service
 */
class SocialEventEnrollService implements SocialEventEnrollServiceInterface {

  /**
   * The event settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $eventSettings;

  /**
   * SocialEventEnrollService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->eventSettings = $config_factory->get('social_event.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(NodeInterface $node) {
    @trigger_error(__METHOD__ . '() is deprecated in social:11.5.0 and is removed from social:12.0.0. Use bundled node object itself `$event->isEnrollmentEnabled()` instead. See https://www.drupal.org/project/social/issues/3306568', E_USER_DEPRECATED);
    return $node instanceof Event &&
      $node->isEnrollmentEnabled();
  }

}
