<?php

namespace Drupal\social_event\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Social Event Controller.
 *
 * @package Drupal\social_event\Controller
 */
class SocialEventController extends ControllerBase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SocialEventController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Redirects users to their events page.
   *
   *   Returns a redirect to the events of the currently logged in user.
   */
  public function redirectMyEvents(): RedirectResponse {
    return $this->redirect('view.events.events_overview', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * Function that checks access on the my event pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we need to check access for.
   *
   *   If access is allowed.
   */
  public function myEventAccess(AccountInterface $account): AccessResult {
    // Fetch user from url.
    $user = $this->requestStack->getCurrentRequest()->get('user');
    // If we don't have a user in the request, assume it's my own profile.
    if (is_null($user)) {
      // Usecase is the user menu, which is generated on all LU pages.
      $user = User::load($account->id());
    }

    // If not a user then just return neutral.
    if (!$user instanceof User) {
      $user = User::load($user);

      if (!$user instanceof User) {
        return AccessResult::neutral();
      }
    }

    // Own profile?
    if ($user->id() === $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'view events on my profile');
    }
    return AccessResult::allowedIfHasPermission($account, 'view events on other profiles');
  }

  /**
   * Function to get the decline request title.
   *
   *   The decline title markup.
   */
  public function getTitleDeclineRequest(): string {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->requestStack->getCurrentRequest()->get('node');

    return $this->t('Decline enrollment request for the event @event_title', ['@event_title' => $node->getTitle()]);
  }

}
