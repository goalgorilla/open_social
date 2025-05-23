<?php

namespace Drupal\social_group_invite\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\hux\Attribute\Hook;

/**
 * Hooks related to the entity.
 */
final class Entity {

  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Mail manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected MailManagerInterface $mailManager,
    protected MessengerInterface $messenger,
    protected LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * Executes the ginvite presave logic in entity insert.
   */
  #[Hook('entity_insert')]
  public function socialGroupInviteGroupContentInsert(EntityInterface $entity): void {
    if (!$entity instanceof GroupRelationshipInterface) {
      return;
    }

    $plugin_id = $entity->getPluginId();

    if ($plugin_id != 'group_invitation') {
      return;
    }

    // This is mostly the same as the code in
    // Drupal\ginvite\GroupInvitationHandler
    // just without the isNew() check.
    $entity->set('invitation_status', GroupInvitation::INVITATION_PENDING);

    $mail = $entity->get('invitee_mail')->value;

    // If invited user has no mail, don't try to send one.
    if (empty($mail)) {
      // "Invitee mail" can be hidden.
      // More info: https://www.drupal.org/project/ginvite/issues/3206103
      // Try to get user from "Invitee" field.
      /** @var \Drupal\Core\Session\AccountInterface|null $invitee */
      $invitee = $entity->get('entity_id')->entity;
      // We want to be sure user exists.
      if ($invitee) {
        $mail = $invitee->getEmail();
        $entity->set('invitee_mail', $mail);
        $this->sendmail($entity, (string) $mail, TRUE);
      }

    }
    else {
      $invitee = user_load_by_mail($mail);
      if ($invitee) {
        $entity->set('entity_id', $invitee);
        $this->sendmail($entity, (string) $mail, TRUE);
      }
      else {
        $this->sendmail($entity, (string) $mail);
      }
    }

    $entity->save();
  }

  /**
   * Sends mail.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Group relationship.
   * @param string $mail
   *   Mail.
   * @param bool $existing_user
   *   Send email to existing user.
   */
  protected function sendmail(GroupRelationshipInterface $group_relationship, $mail, $existing_user = FALSE): void {
    $group_invite_config = $group_relationship->getPlugin()->getConfiguration();

    $send_email_existing_users = $existing_user && $group_invite_config['send_email_existing_users'];
    $send_email_not_existing_users = !$existing_user && $group_invite_config['send_email_not_existing_users'];

    if (!$send_email_existing_users && !$send_email_not_existing_users) {
      // Skip email sending, just show a message.
      $this->messenger->addMessage($this->t('Invitation has been created.'));
      return;
    }

    $group = $group_relationship->getGroup();

    if ($existing_user) {
      $langcode = $group_relationship->getEntity()->language()->getId();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $params = [
      'group' => $group,
      'group_content' => $group_relationship,
      'existing_user' => $existing_user,
    ];

    $this->mailManager->mail('ginvite', 'invite', $mail, $langcode, $params);
    $this->messenger->addMessage($this->t('Invite sent to %mail', ['%mail' => $mail]));
  }

}
