<?php

namespace Drupal\social_queue_storage\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Queue storage entity entity.
 *
 * @ingroup social_queue_storage
 *
 * @ContentEntityType(
 *   id = "queue_storage_entity",
 *   label = @Translation("Queue storage entity"),
 *   bundle_label = @Translation("Queue storage entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\social_queue_storage\QueueStorageEntityListBuilder",
 *     "views_data" = "Drupal\social_queue_storage\Entity\QueueStorageEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\social_queue_storage\Form\QueueStorageEntityForm",
 *       "add" = "Drupal\social_queue_storage\Form\QueueStorageEntityForm",
 *       "edit" = "Drupal\social_queue_storage\Form\QueueStorageEntityForm",
 *       "delete" = "Drupal\social_queue_storage\Form\QueueStorageEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\social_queue_storage\QueueStorageEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\social_queue_storage\QueueStorageEntityAccessControlHandler",
 *   },
 *   base_table = "queue_storage_entity",
 *   data_table = "queue_storage_entity_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer queue storage entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/queue_storage_entity/{queue_storage_entity}",
 *     "add-page" = "/admin/structure/queue_storage_entity/add",
 *     "add-form" = "/admin/structure/queue_storage_entity/add/{queue_storage_entity_type}",
 *     "edit-form" = "/admin/structure/queue_storage_entity/{queue_storage_entity}/edit",
 *     "delete-form" = "/admin/structure/queue_storage_entity/{queue_storage_entity}/delete",
 *     "collection" = "/admin/structure/queue_storage_entity",
 *   },
 *   bundle_entity_type = "queue_storage_entity_type",
 *   field_ui_base_route = "entity.queue_storage_entity_type.edit_form"
 * )
 */
class QueueStorageEntity extends ContentEntityBase implements QueueStorageEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isFinished() {
    return $this->get('finished')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFinished($status) {
    $this->set('finished', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Queue storage entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Queue storage entity entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['finished'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Finished'))
      ->setDescription(t('Is the entity considered finished by the background task it was used in?'))
      ->setInitialValue(FALSE)
      ->setDefaultValue(FALSE);

    return $fields;
  }

}
