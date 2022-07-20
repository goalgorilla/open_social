<?php

namespace Drupal\social_group_invite\Plugin\Join;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitation as GroupInvitationWrapper;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation as GroupInvitationEnabler;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\Plugin\Join\SocialGroupDirectJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for joining after invitation.
 *
 * @Join(
 *   id = "social_group_invite_join",
 *   entityTypeId = "group",
 *   method = "added",
 *   weight = 30,
 * )
 */
class SocialGroupInviteJoin extends SocialGroupDirectJoin {

  /**
   * The group invitation loader.
   */
  private GroupInvitationLoaderInterface $loader;

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

    $instance->loader = $container->get('ginvite.invitation_loader');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = [];
    $invited = FALSE;

    // Only for groups that have invites enabled.
    /** @var \Drupal\social_group\SocialGroupInterface $entity */
    if (
      $entity->getGroupType()->hasContentPlugin('group_invitation') &&
      $this->currentUser->isAuthenticated()
    ) {
      // Check if the user has a pending invite for the group.
      $invitations = $this->loader->loadByProperties([
        'entity_id' => $this->currentUser->id(),
        'gid' => $entity->id(),
        'invitation_status' => GroupInvitationEnabler::INVITATION_PENDING,
      ]);

      // We have pending invites, let's build a button to accept or decline one.
      if (count($invitations) > 0) {
        $invitation = reset($invitations);

        if ($invitation instanceof GroupInvitationWrapper) {
          // Let's grab the group content, so we can build the URL.
          $group_content = $invitation->getGroupContent();

          if ($group_content instanceof GroupContentInterface) {
            $invited = TRUE;
          }
        }
      }
    }

    if ($invited && isset($group_content)) {
      $items[] = [
        'label' => $this->t('Accept'),
        'url' => Url::fromRoute('ginvite.invitation.accept', [
          'group_content' => $group_content->id(),
        ]),
      ];

      $items[] = Link::createFromRoute(
        $this->t('Decline'),
        'ginvite.invitation.decline',
        ['group_content' => $group_content->id()],
      );

      $variables['user_is_invited'] = TRUE;
      $variables['#cache']['contexts'][] = 'user';
      $variables['#cache']['tags'][] = 'group_content_list:entity:' . $this->currentUser->id();
      $variables['#cache']['tags'][] = 'group_content_list:plugin:group_invitation:entity:' . $this->currentUser->id();
    }
    elseif (
      count($items = parent::actions($entity, $variables)) === 1 &&
      in_array($entity->bundle(), $this->types()) ||
      $entity->bundle() === 'closed_group' &&
      !$entity->hasPermission('manage all groups', $this->currentUser)
    ) {
      if (count($items) === 0) {
        $items[] = ['attributes' => ['class' => ['btn-accent']]];
      }
      else {
        unset($items[0]['url']);
      }

      $items[0]['label'] = $variables['cta'] = $this->t('Invitation only');
      $variables['closed_group'] = TRUE;
    }

    return $items;
  }

  /**
   * Gets a list of group types to which a user can be invited.
   */
  protected function types(): array {
    return ['flexible_group'];
  }

}
