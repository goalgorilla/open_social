<?php

namespace Drupal\social_group_request\Plugin\Join;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for the group entity type.
 *
 * @Join(
 *   id = "social_group_request_join",
 *   entityTypeId = "group",
 * )
 */
class SocialGroupRequestJoin extends JoinBase {

  /**
   * The group invitation loader.
   */
  private ?GroupInvitationLoaderInterface $loader = NULL;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

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

    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, UserInterface $account): array {
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
        'uid' => $account->id(),
      ]);

      if ($group_invites !== []) {
        return $items;
      }
    }

    $group_types = ['flexible_group'];
    $this->moduleHandler->alter('social_group_request', $group_types);

    if (in_array($group_type->id(), $group_types)) {
      $join_methods = $group->get('field_group_allowed_join_method')->getValue();
      $request_option = in_array('request', array_column($join_methods, 'value'));

      if (!$request_option) {
        return $items;
      }
    }
    else {
      $allow_request = $group->get('allow_request');

      if ($allow_request->isEmpty() || $allow_request->value == 0) {
        return $items;
      }
    }

    if ($account->isAnonymous()) {
      $items[] = [
        'label' => $this->t('Request to join'),
        'url' => Url::fromRoute(
          'social_group_request.anonymous_request_membership',
          ['group' => $group->id()],
        ),
        'attributes' => [
          'class' => ['btn-accent'],
        ],
      ];

      return $items;
    }

    if (
      !$group->hasPermission('request group membership', $account) ||
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
      ->condition('entity_id', $account->id())
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
          'class' => ['btn-accent'],
        ],
      ];
    }

    return $items;
  }

}
