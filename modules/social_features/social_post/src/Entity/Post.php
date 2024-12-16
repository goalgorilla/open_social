<?php

namespace Drupal\social_post\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Post entity.
 *
 * @ingroup social_post
 *
 * @ContentEntityType(
 *   id = "post",
 *   label = @Translation("Post"),
 *   bundle_label = @Translation("Post type"),
 *   handlers = {
 *     "view_builder" = "Drupal\social_post\PostViewBuilder",
 *     "list_builder" = "Drupal\social_post\PostListBuilder",
 *     "views_data" = "Drupal\social_post\Entity\PostViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\social_post\Form\PostForm",
 *       "add" = "Drupal\social_post\Form\PostForm",
 *       "edit" = "Drupal\social_post\Form\PostForm",
 *       "delete" = "Drupal\social_post\Form\PostDeleteForm",
 *     },
 *     "access" = "Drupal\social_post\PostAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\social_post\PostHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "post",
 *   data_table = "post_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer post entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/post/{post}",
 *     "add-page" = "/post/add",
 *     "add-form" = "/post/add/{post_type}",
 *     "edit-form" = "/post/{post}/edit",
 *     "delete-form" = "/post/{post}/delete",
 *   },
 *   bundle_entity_type = "post_type",
 *   field_ui_base_route = "entity.post_type.edit_form"
 * )
 */
class Post extends ContentEntityBase implements PostInterface {

  use EntityChangedTrait;
  use EntityUrlLanguageTrait;
  use StringTranslationTrait;

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
  public function setCreatedTime($timestamp): PostInterface|static {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    /** @var \Drupal\user\UserInterface $owner */
    $owner = $this->get('user_id')->entity;
    return $owner;
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
  public function setOwnerId($uid): EntityOwnerInterface|Post|static {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): EntityOwnerInterface|Post|static {
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
  public function setPublished($published): PostInterface|static {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): mixed {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type): PostInterface|Post|static {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName(): TranslatableMarkup {
    if ($this->hasField('field_post_image') && !$this->get('field_post_image')
      ->isEmpty()) {
      return $this->t('photo');
    }

    return $this->t('post');
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility(): mixed {
    $visibility = NULL;
    $allowed_values = $this->getPostVisibilityAllowedValues();

    if ($this->hasField('field_visibility')) {
      foreach ($allowed_values as $key => $allowed_value) {
        if ($this->get('field_visibility')->value === $allowed_value['value']) {
          // Default visibility options.
          $visibility = $this->getDefaultVisibilityByLabel($allowed_value['label']);

          // If default visibility doesn't exist it means we use the role
          // as visibility option and we should set the role id as visibility.
          if (!$visibility) {
            $roles = $this->entityTypeManager()
              ->getStorage('user_role')
              ->getQuery()
              ->condition('label', $allowed_value['label'])
              ->execute();
            $role_id = reset($roles);
            // If role_id is empty it means we have an uninspected visibility
            // option, because this option does not default and not from
            // the role.
            if (!empty($role_id)) {
              $visibility = $role_id;
            }
          }
        }
      }

    }

    return $visibility;
  }

  /**
   * Get default visibility option.
   *
   * @param string $label
   *   The visibility label.
   * @param bool $reverse
   *   For setting or getting data.
   *
   * @return string
   *   Visibility label.
   */
  public function getDefaultVisibilityByLabel(string $label, bool $reverse = FALSE): string {
    $default_visibilities = [
      [
        'id' => 'community',
        'label' => $this->t('Community'),
      ],
      [
        'id' => 'public',
        'label' => $this->t('Public'),
      ],
      [
        'id' => 'group',
        'label' => $this->t('Group members'),
      ],
    ];

    if ($reverse) {
      foreach ($default_visibilities as $visibility) {
        if ($visibility['id'] === $label) {
          return $visibility['label'];
        }
      }
    }
    else {
      foreach ($default_visibilities as $visibility) {
        if ($visibility['label'] == $label) {
          return $visibility['id'];
        }
      }
    }

    return '';
  }

  /**
   * Get post visibility options.
   *
   * @return array
   *   Field allowed values.
   */
  private function getPostVisibilityAllowedValues(): array {
    // Post visibility field storage.
    $post_storage = 'field.storage.post.field_visibility';
    return \Drupal::configFactory()->getEditable($post_storage)->getOriginal('settings.allowed_values');
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibility(string $visibility): static {
    $allowed_values = $this->getPostVisibilityAllowedValues();
    $visibility_label = $this->getDefaultVisibilityByLabel($visibility, TRUE);

    if (!$visibility_label) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = $this->entityTypeManager()
        ->getStorage('user_role')
        ->load($visibility);
      if ($role instanceof RoleInterface) {
        foreach ($allowed_values as $key => $value) {
          if ($value['label'] === $role->label()) {
            $this->set('field_visibility', $key);
          }
        }
      }
    }
    else {
      foreach ($allowed_values as $key => $allowed_value) {
        if ($visibility_label === $allowed_value['label']) {
          $this->set('field_visibility', (int) $allowed_value['value']);
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $defaults = parent::getCacheContexts();

    // @todo Change this to custom cache context, may edit/delete post.
    if (!in_array('user', $defaults)) {
      $defaults[] = 'user';
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Post entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Post entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Post entity.'))
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
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE);
    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Post entity.'))
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
