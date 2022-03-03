<?php

namespace Drupal\social_event_max_enroll\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_invite\Controller\UserEnrollInviteController;
use Drupal\social_event_max_enroll\Service\EventMaxEnrollService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Accepts or declines an event enrollment invite.
 *
 * @package Drupal\social_event_max_enroll\Controller
 */
class UserEnrollInviteControllerAlter extends UserEnrollInviteController {

  /**
   * The event maximum enroll service.
   *
   * @var \Drupal\social_event_max_enroll\Service\EventMaxEnrollService
   */
  protected EventMaxEnrollService $eventMaxEnrollService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestStack $requestStack,
    AccountProxyInterface $currentUser,
    EventMaxEnrollService $eventMaxEnrollService
  ) {
    parent::__construct($requestStack, $currentUser);

    $this->eventMaxEnrollService = $eventMaxEnrollService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('social_event_max_enroll.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateEnrollmentInvite(EventEnrollmentInterface $event_enrollment, string $accept_decline): RedirectResponse {
    // We should move forward only when the user is accepting the invite.
    if ($accept_decline === '1') {
      // Retrieve event ID.
      $event_id = $event_enrollment->field_event->target_id;

      /** @var \Drupal\node\NodeInterface $node */
      $node = $this->entityTypeManager()->getStorage('node')->load($event_id);

      if (
        $node !== NULL &&
        $this->eventMaxEnrollService->isEnabled($node)
      ) {
        // If there are no spots left then we should prevent approving
        // invite.
        if ($this->eventMaxEnrollService->getEnrollmentsLeft($node) === 0) {
          // Let's delete all messages to keep the messages clean.
          $this->messenger()->deleteAll();
          $this->messenger()->addWarning($this->t('No spots left.'));

          // Get the redirect destination we're given in the request for the
          // response.
          $destination = Url::fromRoute('view.user_event_invites.page_user_event_invites', ['user' => $this->currentUser->id()])->toString();

          return new RedirectResponse($destination);
        }
      }
    }

    // If there are spots left in the event, do nothing and let the original
    // controller do what it should.
    return parent::updateEnrollmentInvite($event_enrollment, $accept_decline);
  }

}
