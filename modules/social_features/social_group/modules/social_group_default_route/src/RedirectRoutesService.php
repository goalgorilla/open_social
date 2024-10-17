<?php

namespace Drupal\social_group_default_route;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a redirect routes service class for manage group redirects.
 */
class RedirectRoutesService {

  use StringTranslationTrait;

  /**
   * RedirectRoutesService constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler
  ) {
  }

  /**
   * Get default route for non-members.
   *
   * @return string
   *   The default route.
   */
  public function getDefaultNonMemberRoute(): string {
    // About.
    return 'view.group_information.page_group_about';
  }

  /**
   * Get default route for members.
   *
   * @return string
   *   The default route.
   */
  public function getDefaultMemberRoute(): string {
    // Stream.
    return 'social_group.stream';
  }

  /**
   * Get allowed routes for non-member.
   *
   * @return array
   *   The array of routes.
   */
  public function getNonMemberRoutes(): array {
    $routes = [
      'view.group_information.page_group_about' => $this->t('About'),
      'view.albums.page_albums_overview' => $this->t('Albums'),
      'view.group_books.page_group_books' => $this->t('Books'),
      'view.user_discussions.page_user_discussions' => $this->t('Discussions'),
      'view.group_events.page_group_events' => $this->t('Events'),
      'social_media_directory.group_files' => $this->t('Files'),
      'view.user_tasks.page_tasks' => $this->t('Tasks'),
      'view.group_topics.page_group_topics' => $this->t('Topic'),
    ];

    $this->moduleHandler->alter('social_group_default_route_non_member_routes', $routes);

    return $routes;
  }

  /**
   * Get allowed routes for group member.
   *
   * @return array
   *   The array of routes.
   */
  public function getMemberRoutes(): array {
    $routes = [
      'view.group_information.page_group_about' => $this->t('About'),
      'social_group.stream' => $this->t('Stream'),
      'view.albums.page_albums_overview' => $this->t('Albums'),
      'view.group_books.page_group_books' => $this->t('Books'),
      'view.user_discussions.page_user_discussions' => $this->t('Discussions'),
      'view.group_events.page_group_events' => $this->t('Events'),
      'social_media_directory.group_files' => $this->t('Files'),
      'view.group_members.page_group_members' => $this->t('Members'),
      'view.user_tasks.page_tasks' => $this->t('Tasks'),
      'view.group_topics.page_group_topics' => $this->t('Topics'),
    ];

    $this->moduleHandler->alter('social_group_default_route_member_routes', $routes);

    return $routes;
  }

}
