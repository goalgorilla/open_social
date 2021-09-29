<?php

namespace Drupal\social_core\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
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
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Protected var alternativeFrontpageSettings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $alternativeFrontpageSettings;

  /**
   * Protected var siteSettings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $siteSettings;

  /**
   * Constructs SocialInviteSubscriber.
   *
   * @param \Drupal\social_core\InviteService $inviteService
   *   Invitations loader service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(InviteService $inviteService, AccountInterface $current_user, MessengerInterface $messenger, CurrentRouteMatch $route_match, ConfigFactory $config_factory) {
    $this->inviteService = $inviteService;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->currentRoute = $route_match;
    $this->alternativeFrontpageSettings = $config_factory->get('alternative_frontpage.settings');
    $this->siteSettings = $config_factory->get('system.site');
  }

  /**
   * Notify user about Pending invitations.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The GetResponseEvent to process.
   */
  public function notifyAboutPendingInvitations(GetResponseEvent $event) {
    // Only show this message when a user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      $data = $this->inviteService->getInviteData();
      /** @var \Symfony\Component\HttpFoundation\Request $request */
      $request = $event->getRequest();
      $request_path = $request->getPathInfo();
      $route_name = $this->currentRoute->getRouteName();
      $default_front = $this->siteSettings->get('page.front');
      $frontpage_lu = $this->alternativeFrontpageSettings->get('frontpage_for_authenticated_user') ?: '';

      // Either allow on paths.
      $paths_allowed = [
        $default_front,
        $frontpage_lu,
        '/',
      ];
      // Or allow on route names.
      $routes_allowed = [
        'social_core.homepage',
      ];

      // We want to show this message either on:
      // the default site front page
      // the front page set for authenticated users
      // or the stream being the social_core.homepage.
      if (in_array($request_path, $paths_allowed) || in_array($route_name, $routes_allowed)) {
        if (!empty($data['name']) && !empty($data['amount'])) {
          $replacement_url = [
            '@url' => Url::fromRoute($data['name'], ['user' => $this->currentUser->id()])
              ->toString(),
          ];
          $message = $this->formatPlural($data['amount'],
            'You have 1 pending invite <a href="@url">visit your invite overview</a> to see it.',
            'You have @count pending invites <a href="@url">visit your invite overview</a> to see them.', $replacement_url);

          $this->messenger->addMessage($message, 'warning');
        }
      }
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
