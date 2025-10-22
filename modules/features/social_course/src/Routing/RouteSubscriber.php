<?php

namespace Drupal\social_course\Routing;

use Drupal\Core\Url;
use Drupal\social_course\CourseWrapperInterface;
use Drupal\social_group\Routing\RouteSubscriber as RouteSubscriberBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber in 'social_course' module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapperInterface
   */
  protected $courseWrapper;

  /**
   * Constructs RouteSubscriber.
   *
   * @param \Drupal\social_course\CourseWrapperInterface $course_wrapper
   *   Course wrapper.
   */
  public function __construct(CourseWrapperInterface $course_wrapper) {
    $this->courseWrapper = $course_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('node.add')) {
      $route->setRequirements([
        '_course_content_add_access' => 'node:{node_type}',
      ]);
    }

    if ($route = $collection->get('node.add_page')) {
      $route->setDefault('_controller', '\Drupal\social_course\Controller\NodeController::addPage');
    }

    parent::alterRoutes($collection);

    $paths = [
      '/group/{group}/stream',
      '/group/{group}/about',
      '/group/{group}/events',
      '/group/{group}/topics',
      '/group/{group}/members',
    ];

    foreach ($collection->all() as $route) {
      if (in_array($route->getPath(), $paths)) {
        $route->setRequirement('_custom_access', '\Drupal\social_course\Controller\GroupController::access');
      }
    }

    if ($route = $collection->get('entity.group.join')) {
      $route->setRequirement('_course_enroll_access', 'TRUE');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events = array_merge_recursive($events, [
      KernelEvents::REQUEST => [
        ['redirectSectionNode'],
      ],
    ]);

    return $events;
  }

  /**
   * Redirect Course Section node.
   */
  public function redirectSectionNode(RequestEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }

    // Only redirect a certain content type.
    if ($request->attributes->get('node')->getType() !== 'course_section') {
      return;
    }

    $node = $request->attributes->get('node');
    $this->courseWrapper->setCourseFromSection($node);

    if (!$this->courseWrapper->getCourse()) {
      return;
    }

    $materials = $this->courseWrapper->getMaterials($node);

    if (($material = current($materials))) {
      $redirect_url = Url::fromRoute('entity.node.canonical', [
        'node' => $material->id(),
      ]);
      $response = new RedirectResponse($redirect_url->toString(), 301);
      $event->setResponse($response);
    }
  }

}
