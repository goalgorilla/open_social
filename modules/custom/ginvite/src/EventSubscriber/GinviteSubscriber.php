<?php

namespace Drupal\ginvite\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitationLoader;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ginvite module event subscriber.
 *
 * @package Drupal\ginvite\EventSubscriber
 */
class GinviteSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Group invitations loader.
   *
   * @var \Drupal\ginvite\GroupInvitationLoader
   */
  protected $groupInvitationLoader;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs GinviteSubscriber.
   *
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(GroupInvitationLoader $invitation_loader, AccountInterface $current_user, MessengerInterface $messenger) {
    $this->groupInvitationLoader = $invitation_loader;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * Notify user about Pending invitations.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The RequestEvent to process.
   */
  public function notifyAboutPendingInvitations(RequestEvent $event) {
    if ($this->groupInvitationLoader->loadByUser()) {
      $replace = ['@url' => Url::fromRoute('view.my_invitations.page_1', ['user' => $this->currentUser->id()])->toString()];
      $message = $this->t('You have pending group invitations. <a href="@url">Visit your profile</a> to see them.', $replace);

      $this->messenger->addMessage($message, 'warning', FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['notifyAboutPendingInvitations'];
    return $events;
  }

}
