<?php

namespace Drupal\social_group_request\Plugin\Join;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for joining after sending a request.
 *
 * @Join(
 *   id = "social_group_request_join",
 *   entityTypeId = "group",
 *   method = "request",
 *   weight = 20,
 * )
 */
class SocialGroupRequestJoin extends JoinBase {

  /**
   * The group invitation loader.
   */
  private ?GroupInvitationLoaderInterface $loader = NULL;

  /**
   * The entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    /** @var self $instance */
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    if ($container->has($id = 'ginvite.invitation_loader')) {
      /** @var \Drupal\ginvite\GroupInvitationLoaderInterface $loader */
      $loader = $container->get($id);

      $instance->loader = $loader;
    }

    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = [];

    /** @var \Drupal\social_group\SocialGroupInterface $group */
    $group = $entity;

    $group_type = $group->getGroupType();

    if (!$group_type->hasPlugin('group_membership_request')) {
      return $items;
    }

    // If user has a pending invite we should skip the request button.
    if ($this->loader !== NULL) {
      $group_invites = $this->loader->loadByProperties([
        'gid' => $group->id(),
        'uid' => $this->currentUser->id(),
      ]);

      if ($group_invites !== []) {
        return $items;
      }
    }

    $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $variables['#attached']['library'][] = 'social_group_request/social_group_popup';

    if ($this->currentUser->isAnonymous()) {
      $items[] = [
        'label' => $this->t('Request to join'),
        'url' => Url::fromRoute(
          'social_group_request.anonymous_request_membership',
          ['group' => $group->id()],
        ),
        'attributes' => [
          'class' => ['btn-accent', 'use-ajax'],
        ],
      ];

      return $items;
    }

    if (
      !$group->hasPermission('request group membership', $this->currentUser) ||
      !$group->hasField('allow_request')
    ) {
      return $items;
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_type_id = (string) $group->getGroupType()->id();
    $relation_type_id = $storage->getRelationshipTypeId($group_type_id, 'group_membership_request');

    $count = $this->entityTypeManager->getStorage('group_content')
      ->getQuery()
      ->condition('type', $relation_type_id)
      ->condition('gid', $group->id())
      ->condition('entity_id', $this->currentUser->id())
      ->condition('grequest_status', GroupMembershipRequest::REQUEST_PENDING)
      // Currently, we can't give permissions for outsider (that is synced with
      // AU role) user to view any entity relations of Group membership request.
      // So, disable access checking for this query.
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->count()
      ->execute();

    if ($count > 0) {
      $items[] = $this->t('Request sent');

      $items[] = Link::createFromRoute(
        $this->t('Cancel request'),
        'social_group_request.cancel_request',
        ['group' => $group->id()],
      );
    }
    else {
      $link = $group->toLink(
        $this->t("Request to join"),
        'group-request-membership',
      );
      // We convert to an array manually rather than use `toRenderable` because
      // JoinManager is very particular about its array shape. Additionally, we
      // can't pass the `Link` instance because it can't transfer the needed
      // attributes.
      $items[] = [
        'label' => $link->getText(),
        'url' => $link->getUrl(),
        'attributes' => [
          'class' => ['btn-accent', 'use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'width' => '582px',
            'dialogClass' => 'social_group-popup',
          ]),
        ],
      ];
    }

    $variables['#attached']['library'][] = 'social_group_request/social_group_request_popup';
    $variables['#cache']['tags'][] = 'group:' . $group->id();

    return $items;
  }

}
