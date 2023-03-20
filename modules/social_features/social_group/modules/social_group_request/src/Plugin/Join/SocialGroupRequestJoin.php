<?php

namespace Drupal\social_group_request\Plugin\Join;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
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
    $plugin_definition
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

    if (!$group_type->hasContentPlugin('group_membership_request')) {
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

    $types = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $count = $this->entityTypeManager->getStorage('group_content')
      ->getQuery()
      ->condition('type', $types)
      ->condition('gid', $group->id())
      ->condition('entity_id', $this->currentUser->id())
      ->condition('grequest_status', GroupMembershipRequest::REQUEST_PENDING)
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
      $items[] = [
        'label' => $this->t('Request to join'),
        'url' => Url::fromRoute(
          'grequest.request_membership',
          ['group' => $group->id()],
        ),
        'attributes' => [
          'class' => ['btn-accent', 'use-ajax'],
        ],
      ];
    }

    $variables['#attached']['library'][] = 'social_group_request/social_group_request_popup';
    $variables['#cache']['tags'][] = 'request-membership:' . $group->id();

    return $items;
  }

}
