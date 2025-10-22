<?php

namespace Drupal\social_course\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Provides the 'course_enrollment' Entity.
 *
 * @package Drupal\social_course\Entity
 *
 * @ContentEntityType(
 *   id = "course_enrollment",
 *   label = @Translation("Course Enrollment"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "course_enrollment",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class CourseEnrollment extends ContentEntityBase implements CourseEnrollmentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->setOwnerId($account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group ID'))
      ->setSetting('target_type', 'group')
      ->setSetting('handler', 'default');

    $fields['sid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Section ID'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default');

    $fields['mid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Material ID'))
      ->setSetting('target_type', 'paragraph')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDefaultValue(self::NOT_STARTED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourse() {
    return $this->get('gid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseId() {
    return $this->get('gid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSection() {
    return $this->get('sid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionId() {
    return $this->get('sid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaterial() {
    return $this->get('mid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaterialId() {
    return $this->get('mid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return (int) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->get('status')->setValue($status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFinishedAttempt(): bool {
    $attempts = $this->entityTypeManager()->getStorage('course_enrollment')
      ->getQuery()
      ->condition('gid', $this->getCourseId())
      ->condition('sid', $this->getSectionId())
      ->condition('mid', $this->getMaterialId())
      ->condition('uid', $this->getOwnerId())
      ->condition('status', self::FINISHED)
      ->accessCheck(FALSE)
      ->execute();

    return (bool) $attempts;
  }

}
