<?php

namespace Drupal\social_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Event enrollment entities.
 *
 * @ingroup social_event
 */
class EventEnrollmentListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Event enrollment ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\social_event\Entity\EventEnrollment */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.event_enrollment.edit_form', array(
          'event_enrollment' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
