<?php

declare(strict_types=1);

namespace Drupal\social_group_default_route\Hooks;

use Drupal\Core\Url;
use Drupal\hux\Attribute\Alter;

/**
 * Provides hooks for altering Social Group default routes.
 *
 * This class contains methods that modify the behavior of default routes
 * for social groups, ensuring appropriate redirections and accessibility
 * for various tasks in the system.
 */
final class SocialGroupDefaultRouteHooks {

  /**
   * Alters the "social_group.stream" tab link in the menu local tasks.
   *
   * The problem is that we can redirect a user to a default route only in case
   * if the user accessing the group canonical route. In our case it is the
   * group stream page. When the user clicks on the stream tab, the current
   * module is redirecting the user to the group landing page and makes the
   * group stream page inaccessible for users.
   * This method fixes the issue by adding a "skipDefaultRoute"
   * query parameter to the stream tab URL to bypass the default route
   * redirection.
   *
   * @param array $data
   *   The menu data array to be altered. The method modifies the structure by
   *   adding parameters to the query of the stream tab URL.
   * @param string $route_name
   *   The current route name, used for identifying the scope of the alteration.
   *
   * @see hook_menu_local_tasks_alter()
   */
  #[Alter('menu_local_tasks')]
  public function accessToGroupStream(array &$data, string $route_name): void {
    if (!isset($data['tabs'][0]['social_group.stream']['#link']['url'])) {
      return;
    }

    $url = $data['tabs'][0]['social_group.stream']['#link']['url'];
    assert($url instanceof Url);

    $query = (array) $url->getOption('query');
    // To not overload the path, we need just to put this parameter and check if
    // it exists.
    $query['skipDefaultRoute'] = NULL;
    $url->setOption('query', $query);
  }

}
