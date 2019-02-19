<?php

namespace Drupal\social_user;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\User\ToolbarLinkBuilder;

/**
 * SocialUserToolbarLinkBuilder fills out the placeholders generated in user_toolbar().
 */
class SocialUserToolbarLinkBuilder extends ToolbarLinkBuilder {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountProxyInterface $account, EntityTypeManager $entity_type_manager) {
    parent::__construct($account);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Lazy builder callback for rendering toolbar links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderToolbarLinks() {
    $build = parent::renderToolbarLinks();
    $links = [
      'account' => [
        'title' => $this->t('My profile'),
        'url' => Url::fromRoute('user.page'),
        'attributes' => [
          'title' => $this->t('My profile'),
        ],
      ],
      'account_edit_profile' => [
        'title' => $this->t('Edit profile'),
        'url' => Url::fromRoute('entity.profile.type.user_profile_form', [
          'user' => $this->account->id(),
          'profile_type' => 'profile',
        ]),
        'attributes' => [
          'title' => $this->t('Edit profile'),
        ],
      ],
      'account_edit' => [
        'title' => $this->t('Settings'),
        'url' => Url::fromRoute('entity.user.edit_form', ['user' => $this->account->id()]),
        'attributes' => [
          'title' => $this->t('Settings'),
        ],
      ],
      'logout' => $build['#links']['logout'],
    ];
    $build['#links'] = $links;
    return $build;
  }

}
