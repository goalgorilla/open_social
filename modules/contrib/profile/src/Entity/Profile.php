<?php

/**
 * @file
 * Contains \Drupal\profile\Entity\Profile.
 */

namespace Drupal\profile\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\user\UserInterface;


/**
 * Defines the profile entity class.
 *
 * @ContentEntityType(
 *   id = "profile",
 *   label = @Translation("Profile"),
 *   bundle_label = @Translation("Profile"),
 *   handlers = {
 *     "storage" = "Drupal\profile\ProfileStorage",
 *     "view_builder" = "Drupal\profile\ProfileViewBuilder",
 *     "views_data" = "Drupal\profile\ProfileViewsData",
 *     "access" = "Drupal\profile\ProfileAccessControlHandler",
 *     "list_builder" = "Drupal\profile\ProfileListBuilder",
 *     "form" = {
 *       "default" = "Drupal\profile\Form\ProfileForm",
 *       "add" = "Drupal\profile\Form\ProfileForm",
 *       "edit" = "Drupal\profile\Form\ProfileForm",
 *       "delete" = "Drupal\profile\Form\ProfileDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\profile\ProfileHtmlRouteProvider",
 *     },
 *   },
 *   bundle_entity_type = "profile_type",
 *   field_ui_base_route = "entity.profile_type.edit_form",
 *   admin_permission = "administer profiles",
 *   base_table = "profile",
 *   revision_table = "profile_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "profile_id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *  links = {
 *    "canonical" = "/profile/{profile}",
 *    "edit-form" = "/profile/{profile}/edit",
 *    "delete-form" = "/profile/{profile}/delete",
 *    "collection" = "/admin/config/people/profiles",
 *    "set-default" = "/profile/{profile}/set-default"
 *   },
 * )
 */
class Profile extends ContentEntityBase implements ProfileInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that owns this profile.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active status'))
      ->setDescription(t('A boolean indicating whether the profile is active.'))
      ->setDefaultValue(TRUE)
      ->setRevisionable(TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
       ->setLabel(t('Default'))
       ->setDescription(t('A boolean indicating whether the profile is the default one.'))
       ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the profile was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the profile was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Overrides Entity::id().
   */
  public function id() {
    return $this->get('profile_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $profile_type = ProfileType::load($this->bundle());
    return
      t('@type profile of @username (uid: @uid)',
        [
          '@type' => $profile_type->label(),
          '@username' => $this->getOwner()->getDisplayName(),
          '@uid' => $this->getOwnerId(),
        ]);
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
    return $this;
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
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
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? PROFILE_ACTIVE : PROFILE_NOT_ACTIVE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return (bool) $this->get('is_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($is_default) {
    $this->set('is_default', $is_default ? PROFILE_DEFAULT : PROFILE_NOT_DEFAULT);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    return Cache::mergeTags($tags, [
      'user:' . $this->getOwnerId(),
      'user_view',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /** @var \Drupal\profile\ProfileStorage $storage */
    parent::postSave($storage, $update);

    // Check if this profile is, or became the default.
    if ($this->isDefault()) {
      /** @var \Drupal\profile\Entity\ProfileInterface[] $profiles */
      $profiles = $storage->loadMultipleByUser($this->getOwner(), $this->getType());

      // Ensure that all other profiles are set to not default.
      foreach ($profiles as $profile) {
        if ($profile->id() != $this->id() && $profile->isDefault()) {
          $profile->setDefault(FALSE);
          $profile->save();
        }
      }
    }
  }

}
