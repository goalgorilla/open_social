<?php

namespace Drupal\ginvite\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PreventDuplicated constraint.
 */
class PreventDuplicatedConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Group invitations loader.
   *
   * @var \Drupal\ginvite\GroupInvitationLoader
   */
  protected $groupInvitationLoader;

  /**
   * Constructs PreventDuplicatedConstraintValidator.
   *
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   */
  public function __construct(GroupMembershipLoaderInterface $group_membership_loader, GroupInvitationLoader $invitation_loader) {
    $this->groupMembershipLoader = $group_membership_loader;
    $this->groupInvitationLoader = $invitation_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group.membership_loader'),
      $container->get('ginvite.invitation_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    // Validate only group_invitation bundle.
    if (!($entity instanceof GroupContentInterface) || $entity->getContentPlugin()->getPluginId() != 'group_invitation') {
      return;
    }

    $mail = $entity->get('invitee_mail')->getString();
    $group = $entity->get('gid')->first()->get('entity')->getTarget()->getValue();
    if ($user = user_load_by_mail($mail)) {
      // Check if user already a member.
      $membership = $this->groupMembershipLoader->load($group, $user);
      if (!empty($membership)) {
        $this->context->addViolation($this->t('User with such email already a member of this group.'));
        return;
      }
    }

    if ($this->groupInvitationLoader->loadByGroup($group, NULL, $mail)) {
      $this->context->addViolation($this->t('Invitation to this user already sent.'));
    }
  }

}
