<?php

namespace Drupal\social_event\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Updates a pending enrollment request.
 *
 * @package Drupal\social_event\Controller
 */
class UpdateEnrollRequestController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * UpdateEnrollRequestController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Updates the enrollment request.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current event node.
   * @param \Drupal\social_event\EventEnrollmentInterface $event_enrollment
   *   The entity event_enrollment.
   * @param int $approve
   *   Approve the enrollment request, TRUE(1) or FALSE(0).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return to the original destination from the current request.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEnrollmentRequest(NodeInterface $node, EventEnrollmentInterface $event_enrollment, $approve) {
    // Just some sanity checks.
    if ($node instanceof Node && !empty($event_enrollment)) {
      // When the user approved, we set the field_request_status to approved.
      if ($approve === '1') {
        $event_enrollment->field_request_status->value = 'approved';
        $this->messenger()->addStatus(t('The event enrollment request has been approved.'));
      }
      // When the user declined, we set the field_request_status to decline.
      elseif ($approve === '0') {
        $event_enrollment->field_request_status->value = 'declined';
        $this->messenger()->addStatus(t('The event enrollment request has been declined.'));
      }
      // And finally save (update) this updated $event_enrollment.
      // @todo: maybe think of deleting approved/declined records from the db?
      $this->entityTypeManager()->getStorage('event_enrollment')->save($event_enrollment);
    }

    // Get the redirect destination we're given in the request for the response.
    $destination = $this->requestStack->getCurrentRequest()->query->get('destination');

    return new RedirectResponse($destination);
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('manage event enrollment requests'));
  }

}
