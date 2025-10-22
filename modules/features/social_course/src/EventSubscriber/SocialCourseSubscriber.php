<?php

namespace Drupal\social_course\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_course\CourseWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Social event subscriber.
 *
 * @package Drupal\social_core\SocialInviteSubscriber
 */
class SocialCourseSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapper
   */
  protected $courseWrapper;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

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
   * Constructs SocialCourseSubscriber.
   *
   * @param \Drupal\social_course\CourseWrapper $course_wrapper
   *   The current route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    CourseWrapper $course_wrapper,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_manager,
    AccountInterface $current_user,
    MessengerInterface $messenger,
  ) {
    $this->courseWrapper = $course_wrapper;
    $this->routeMatch = $route_match;
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * Notify user course access rules.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The GetResponseEvent to process.
   */
  public function notifyAboutCourseAccess(RequestEvent $event) {

    if (!$gid = $this->routeMatch->getRawParameter('group')) {
      return;
    }

    $message = '';
    $account = $this->currentUser;
    $field = 'field_course_opening_status';
    $bundles = $this->courseWrapper->getAvailableBundles();
    $group = $this->entityManager->getStorage('group')->load($gid);

    // Anonymous users can't enroll in courses anyway.
    // Only show a message to the user when they are actually authenticated.
    if (!$group instanceof GroupInterface || $account->isAnonymous()) {
      return;
    }

    // Display course access warning message only on the "about" course page.
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name !== 'view.group_information.page_group_about') {
      return;
    }

    if (
      $group->hasField($field) &&
      !$group->get($field)->value &&
      !$group->get($field)->isEmpty()
    ) {
      $message = $this->t('Course sections can only be accessed after the course starts. You can only enrol in this course before the course has started.');
    }
    elseif (in_array($group->bundle(), $bundles) && !$group->getMember($account)) {
      $message = $this->t('Course sections and other information can only be accessed after enrolling for this course.');
    }

    if ($message) {
      $this->messenger->addMessage($message, 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['notifyAboutCourseAccess'];
    return $events;
  }

}
