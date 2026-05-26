<?php

namespace Drupal\social_private_message\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\social_private_message\Service\SocialPrivateMessageService;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hux class for hook_entity_view_alter() in social_private_message.
 */
class SocialPrivateMessageEntityViewAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected SocialPrivateMessageService $socialPrivateMessageService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('social_private_message.service'),
    );
  }

  /**
   * Implements hook_entity_view_alter() for private_message_thread + private_message.
   */
  #[Alter('entity_view')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    // Threads.
    if ($entity->getEntityTypeId() === 'private_message_thread') {
      /** @var \Drupal\private_message\Entity\PrivateMessageThread $thread */
      $thread = $entity;

      $only_exist_members = $thread->getMembers();
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');

      // View mode inbox.
      if ($build['#view_mode'] === 'inbox') {
        $members_with_deleted = $thread->get('members')->getValue();
        $members_string = [];
        $display_name = '';

        foreach (array_keys($members_with_deleted) as $key) {
          if (isset($only_exist_members[$key])) {
            /** @var \Drupal\user\UserInterface $member */
            $member = $only_exist_members[$key];

            if ($this->currentUser->id() === $member->id()) {
              unset($members_with_deleted[$key], $only_exist_members[$key]);
            }
            else {
              $user_profile = $profile_storage->loadByUser($member, 'profile');
              if ($user_profile === NULL) {
                $members_string[] = ['#markup' => $this->t('Deleted user')];
              }
              else {
                $members_string[] = $profile_view_builder->view($user_profile, 'name_raw');
              }
            }
          }
          else {
            $members_string[] = ['#markup' => $this->t('Deleted user')];
          }
        }
        // Count the amount of members.
        $member_count = count($members_with_deleted);

        $profile_picture = [];

        if ($member_count === 1) {
          $recipient = end($only_exist_members);

          if ($recipient instanceof UserInterface) {
            $user_profile = $profile_storage->loadByUser($recipient, 'profile');
            if ($user_profile === NULL) {
              $display_name = ['#markup' => $this->t('Deleted user')];
            }
            else {
              $display_name = $profile_view_builder->view($user_profile, 'name_raw');
            }
          }
          else {
            // Make this an array, since we no longer render in here.
            $display_name = [
              '#markup' => $this->t('Deleted user'),
            ];
            $recipient = $this->entityTypeManager->getStorage('user')->load(1);
          }

          // Load compact notification view mode of the attached profile.
          if ($recipient instanceof User) {
            $user_profile = $profile_storage->loadByUser($recipient, 'profile');
            if ($user_profile !== NULL) {
              $profile_picture = $profile_view_builder->view($user_profile, 'compact_notification');
            }
          }
        }

        // Add either the profile picture or the group picture.
        if ($member_count > 1) {
          $build['members']['#markup'] = '<div class="avatar-icon avatar-group-icon avatar-group-icon--medium"></div>';
          // Add members names.
          $build['membernames'] = $members_string;
          $build['membernames']['#prefix'] = '<strong>';
          $build['membernames']['#suffix'] = '</strong>';
        }
        else {
          if (!empty($profile_picture)) {
            $build['members'] = $profile_picture;
          }
          if (!empty($display_name)) {
            // Add members name.
            $build['membernames'] = $display_name;
            $build['membernames']['#prefix'] = '<strong>';
            $build['membernames']['#suffix'] = '</strong>';
          }
        }
      }
      elseif ($build['#view_mode'] === 'full') {
        $this->socialPrivateMessageService->updateLastThreadCheckTime($entity);
        $build['#prefix'] = '';
        $build['#suffix'] = '';

        if ($display->getComponent('private_message_form') && count($only_exist_members) === 1) {
          $build['private_message_form'] = NULL;
        }
      }
    }

    // Private message entity.
    elseif ($entity->getEntityTypeId() === 'private_message') {
      // Default view mode.
      if ($build['#view_mode'] === 'default') {
        /** @var \Drupal\private_message\Entity\PrivateMessage $entity */
        if ($this->currentUser->id() === $entity->getOwnerId()) {
          // Current user is 'You'.
          $build['owner'][0]['#plain_text'] = $this->t('You');
        }
      }
    }
  }

}
