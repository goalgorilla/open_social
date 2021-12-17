<?php

namespace Drupal\social_core\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Define events for entity that become published.
 */
class EntityPublishedEvent extends Event {

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * SocialChallengePhaseEndingEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Phase entity.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Returns parameters from event.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The parameters.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
