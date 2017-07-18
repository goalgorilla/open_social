<?php

namespace Drupal\activity_creator\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\activity_creator\ActivityInterface;
use Drupal\user\UserInterface;
use Drupal\votingapi\Entity\Vote;

/**
 * Defines the Activity entity.
 *
 * @ingroup activity_creator
 *
 * @ContentEntityType(
 *   id = "activity",
 *   label = @Translation("Activity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\activity_creator\ActivityListBuilder",
 *     "views_data" = "Drupal\activity_creator\Entity\ActivityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\activity_creator\Form\ActivityForm",
 *       "add" = "Drupal\activity_creator\Form\ActivityForm",
 *       "edit" = "Drupal\activity_creator\Form\ActivityForm",
 *       "delete" = "Drupal\activity_creator\Form\ActivityDeleteForm",
 *     },
 *     "access" = "Drupal\activity_creator\ActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\activity_creator\ActivityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "activity",
 *   data_table = "activity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer activity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/activity/{activity}",
 *     "add-form" = "/admin/structure/activity/add",
 *     "edit-form" = "/admin/structure/activity/{activity}/edit",
 *     "delete-form" = "/admin/structure/activity/{activity}/delete",
 *     "collection" = "/admin/structure/activity",
 *   },
 *   field_ui_base_route = "activity.settings"
 * )
 */
class Activity extends ContentEntityBase implements ActivityInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Activity entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Activity entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Activity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Activity is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Activity entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Get related entity url.
   *
   * @return \Drupal\Core\Url|string
   *   Returns empty string or URL object of related entity canonical url.
   */
  public function getRelatedEntityUrl() {
    $link = "";
    $related_object = $this->get('field_activity_entity')->getValue();

    if (!empty($related_object)) {
      $target_type = $related_object['0']['target_type'];
      $target_id = $related_object['0']['target_id'];

      // Make an exception for Votes.
      if ($related_object['0']['target_type'] === 'vote') {
        /** @var Vote $vote */
        if ($vote = entity_load($target_type, $target_id)) {
          $target_type = $vote->getVotedEntityType();
          $target_id = $vote->getVotedEntityId();
        }
      }

      $entity = entity_load($target_type, $target_id);
      if (!empty($entity)) {
        /** @var \Drupal\Core\Url $link */
        $link = $entity->urlInfo('canonical');
      }
    }
    return $link;
  }

  /**
   * Get destinations.
   */
  public function getDestinations() {
    $values = [];
    $field_activity_destinations = $this->field_activity_destinations;
    if (isset($field_activity_destinations)) {
      $destinations = $field_activity_destinations->getValue();
      foreach ($destinations as $key => $destination) {
        $values[] = $destination['value'];
      }
    }
    return $values;
  }

  /**
   * Get recipient.
   *
   * Assume that activity can't have recipient group and user at the same time.
   *
   * @todo: Split it to two separate functions.
   */
  public function getRecipient() {
    $value = NULL;

    $field_activity_recipient_user = $this->field_activity_recipient_user;
    if (isset($field_activity_recipient_user)) {
      $recipient_user = $field_activity_recipient_user->getValue();
      if (!empty($recipient_user)) {
        $recipient_user['0']['target_type'] = 'user';
        return $recipient_user;
      }
    }

    $field_activity_recipient_group = $this->field_activity_recipient_group;
    if (isset($field_activity_recipient_group)) {
      $recipient_group = $field_activity_recipient_group->getValue();
      if (!empty($recipient_group)) {
        $recipient_group['0']['target_type'] = 'group';
        return $recipient_group;
      }
    }

    return $value;
  }

}
