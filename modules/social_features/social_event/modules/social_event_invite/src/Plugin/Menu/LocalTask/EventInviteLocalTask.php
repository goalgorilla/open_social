<?php

namespace Drupal\social_event_invite\Plugin\Menu\LocalTask;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a local task that shows the amount of group invites.
 */
class EventInviteLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct the UnapprovedComments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    /** @var \Drupal\social_event\EventEnrollmentStatusHelper $enrollments */
    $enrollments = \Drupal::service('social_event.status_helper');

    if ($enrollments->getAllUserEventEnrollments(NULL)) {
      // We don't need plural because users will be redirected
      // if there is no invite.
      return $this->t('Event invites (@count)', ['@count' => count($enrollments->getAllUserEventEnrollments(NULL))]);
    }

    return $this->t('Event invites');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];
    $user = $this->routeMatch->getParameter('user');

    // Add cache tags for event invite.
    if ($user instanceof UserInterface) {
      $tags[] = 'event_content_list:entity:' . $user->id();
    }

    if (is_string($user)) {
      $tags[] = 'event_content_list:entity:' . $user;
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

}
