<?php

namespace Drupal\social_core\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\social_core\InviteService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Social event subscriber.
 *
 * @package Drupal\social_core\SocialInviteSubscriber
 */
class SocialInviteSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Group invitations loader.
   *
   * @var \Drupal\social_core\InviteService
   */
  protected $inviteService;

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
   * Constructs SocialInviteSubscriber.
   *
   * @param \Drupal\social_core\InviteService $inviteService
   *   Invitations loader service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(InviteService $inviteService, AccountInterface $current_user, MessengerInterface $messenger) {
    $this->inviteService = $inviteService;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * Notify user about Pending invitations.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The GetResponseEvent to process.
   */
  public function notifyAboutPendingInvitations(GetResponseEvent $event) {
    $route = $this->inviteService->baseRoute();
    if (!empty($route['name']) && !empty($route['amount'])) {
      $replacement_url = ['@url' => Url::fromRoute($route['name'], ['user' => $this->currentUser->id()])->toString()];
      $message = $this->formatPlural($route['amount'],
        'You have 1 pending invite <a href="@url">Visit your profile</a> to see it.',
        'You have @count pending invites <a href="@url">Visit your profile</a> to see them.', $replacement_url);

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
