<?php

namespace Drupal\social_event\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Event enrollment entity.
 *
 * @ingroup social_event
 *
 * @ContentEntityType(
 *   id = "event_enrollment",
 *   label = @Translation("Event enrollment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\social_event\EventEnrollmentListBuilder",
 *     "views_data" = "Drupal\social_event\Entity\EventEnrollmentViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\social_event\Form\EventEnrollmentForm",
 *       "add" = "Drupal\social_event\Form\EventEnrollmentForm",
 *       "edit" = "Drupal\social_event\Form\EventEnrollmentForm",
 *       "delete" = "Drupal\social_event\Form\EventEnrollmentDeleteForm",
 *     },
 *     "access" = "Drupal\social_event\EventEnrollmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\social_event\EventEnrollmentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_enrollment",
 *   data_table = "event_enrollment_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer event enrollment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/event_enrollment/{event_enrollment}",
 *     "add-form" = "/admin/structure/event_enrollment/add",
 *     "edit-form" = "/admin/structure/event_enrollment/{event_enrollment}/edit",
 *     "delete-form" = "/admin/structure/event_enrollment/{event_enrollment}/delete",
 *     "collection" = "/admin/structure/event_enrollment",
 *   },
 *   field_ui_base_route = "event_enrollment.settings"
 * )
 */
class EventEnrollment extends ContentEntityBase implements EventEnrollmentInterface {
  use EntityChangedTrait;
  use StringTranslationTrait;

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
  public function preSave(EntityStorageInterface $storage) {
    $tags = [
      'event_content_list:user:' . $this->getAccount(),
      'event_enrollment_list:' . $this->getFieldValue('field_event', 'target_id'),
    ];
    Cache::invalidateTags($tags);
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    if (!empty($entities)) {
      $tags = [];
      foreach ($entities as $enrollment) {
        $tags = [
          'event_content_list:user:' . $enrollment->getAccount(),
          'event_enrollment_list:' . $enrollment->getFieldValue('field_event', 'target_id'),
        ];
      }
      Cache::invalidateTags($tags);
    }
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
  public function label() {
    // When a guest is allowed to join the name and account fields can be empty,
    // but the field for email will be provided.
    // The first and last name are not mandatory,
    // so the field_name is used for validation instead.
    if ($this->hasField('field_email') && !$this->get('field_email')->isEmpty()) {
      $label = trim(sprintf('%s %s', $this->get('field_first_name')->value, $this->get('field_last_name')->value));
      return empty($label) ? $this->get('field_email')->value : $label;
    }

    $label = $this->getName();
    if (empty($label) && $this->getAccountEntity() instanceof UserInterface) {
      // We use the user account name.
      $label = $this->getAccountEntity()->label();
    }
    else {
      // If the account is not returned, it means the user was deleted,
      // but somehow the event enrollment was not removed.
      $label = 'Deleted user';
    }

    return $label;
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
  public function getAccount(): ?string {
    return $this->get('field_account')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountEntity(): ?UserInterface {
    if ($this->get('field_account')->isEmpty()) {
      return NULL;
    }

    return $this->get('field_account')->entity;
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
  public function getEvent(): ?NodeInterface {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->get('field_event')->entity;
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventStandaloneEnrollConfirmationStatus(): bool {
    $event = $this->getEvent();
    if ($event instanceof NodeInterface) {
      return (bool) $event->get('field_event_send_confirmation')->getString();
    }

    return FALSE;
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
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Event enrollment entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Event enrollment entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Event enrollment entity.'))
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Event enrollment entity.'))
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Event enrollment is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Event enrollment entity.'))
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

}
