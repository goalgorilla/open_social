<?php

namespace Drupal\activity_creator\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\activity_creator\ActivityInterface;
use Drupal\Core\Url;
use Drupal\flag\Entity\Flagging;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

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
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): ActivityInterface|static {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->get('user_id')->entity;
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): ?int {
    return (int) $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): EntityOwnerInterface|Activity|static {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): EntityOwnerInterface|Activity|static {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published): ActivityInterface {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
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
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getDefaultEntityOwner')
      ->setTranslatable(TRUE)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Activity is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Activity entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
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
   * Get related entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns NULL or Entity object.
   */
  public function getRelatedEntity(): ?EntityInterface {
    $related_object = $this->get('field_activity_entity')->getValue();
    if (!empty($related_object)) {
      $target_type = $related_object['0']['target_type'];
      $target_id = $related_object['0']['target_id'];
      $entity_storage = $this->entityTypeManager()->getStorage($target_type);
      if ($entity_storage instanceof ConfigEntityStorage) {
        $entity = $entity_storage->loadByProperties([
          'unique_id' => $target_id,
          'status' => 1,
        ]);
        $entity = reset($entity);
      }
      else {
        /** @var  \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $entity_storage->load($target_id);
      }
      return empty($entity) ? NULL : $entity;
    }
    return NULL;

  }

  /**
   * Get related entity url.
   *
   * @return \Drupal\Core\Url|string
   *   URL object of related entity canonical url or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getRelatedEntityUrl(): string|Url {
    $related_object = $this->get('field_activity_entity')->getValue();

    if (!empty($related_object)) {
      $target_type = $related_object['0']['target_type'];
      $target_id = $related_object['0']['target_id'];

      // Make an exception for Votes.
      if ($target_type === 'vote') {
        /** @var \Drupal\votingapi\VoteStorage $vote_storage */
        $vote_storage = \Drupal::entityTypeManager()->getStorage($target_type);
        if ($vote = $vote_storage->load($target_id)) {
          /** @var \Drupal\votingapi\Entity\Vote $vote */
          $target_type = $vote->getVotedEntityType();
          $target_id = $vote->getVotedEntityId();
        }
      }
      elseif ($target_type === 'group_content') {
        /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorage $group_content_storage */
        $group_content_storage = \Drupal::service('entity_type.manager')->getStorage($target_type);
        if ($group_content = $group_content_storage->load($target_id)) {
          /** @var \Drupal\group\Entity\GroupRelationship $group_content */
          $target_type = $group_content->getEntity()->getEntityTypeId();
          $target_id = $group_content->getEntity()->id();
        }
      }
      elseif ($target_type === 'event_enrollment') {
        $entity_storage = \Drupal::entityTypeManager()
          ->getStorage($target_type);
        $entity = $entity_storage->load($target_id);

        // Lets make the Event node the target for Enrollments.
        if ($entity !== NULL) {
          /** @var \Drupal\social_event\Entity\EventEnrollment $entity */
          $event_id = $entity->getFieldValue('field_event', 'target_id');
          $target_id = $event_id;
          $target_type = 'node';
        }
      }
      elseif ($target_type === 'flagging') {
        $flagging = Flagging::load($target_id);
        if (!$flagging) {
          return '';
        }
        $target_type = $flagging->getFlaggableType();
        $target_id = $flagging->getFlaggableId();
      }

      $entity_storage = \Drupal::entityTypeManager()
        ->getStorage($target_type);
      $entity = $entity_storage->load($target_id);
      if ($entity !== NULL) {
        /** @var \Drupal\Core\Url $link */
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $link = $entity->toUrl('canonical');
      }
    }

    return $link ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinations(): array {
    $values = [];
    $field_activity_destinations = $this->get('field_activity_destinations');
    $destinations = $field_activity_destinations->getValue();
    foreach ($destinations as $destination) {
      $values[] = $destination['value'];
    }
    return $values;
  }

  /**
   * Get recipient.
   *
   * Assume that activity can't have recipient group and user at the same time.
   *
   * @todo Split it to two separate functions.
   */
  public function getRecipient(): mixed {
    $field_activity_recipient_user = $this->get('field_activity_recipient_user');
    $recipient_user = $field_activity_recipient_user->getValue();
    if (!empty($recipient_user)) {
      $recipient_user['0']['target_type'] = 'user';
      return $recipient_user;
    }

    $field_activity_recipient_group = $this->get('field_activity_recipient_group');
    $recipient_group = $field_activity_recipient_group->getValue();
    if (!empty($recipient_group)) {
      $recipient_group['0']['target_type'] = 'group';
      return $recipient_group;
    }

    return NULL;
  }

}
