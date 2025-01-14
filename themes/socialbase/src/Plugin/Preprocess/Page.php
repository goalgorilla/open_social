<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHander;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The storage handler class for nodes.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $account_proxy,
    EntityTypeManagerInterface $entity,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->moduleHander = $module_handler;
    $this->currentUser = $account_proxy;
    $this->nodeStorage = $entity->getStorage('node');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    $attributes = $variables['content_attributes'] instanceof Attribute ? $variables['content_attributes'] : new Attribute();
    // Default classes.
    $attributes->addClass('row', 'container');
    // If page has title.
    if ($variables['page']['title']) {
      $attributes->addClass('with-title-region');
      $variables['display_page_title'] = TRUE;
    }

    // If we have the admin toolbar permission.
    // Check for permission.
    if ($this->currentUser->hasPermission('access toolbar')) {
      $variables['#attached']['library'][] = 'socialbase/admin-toolbar';
    }

    // Add plain title for node preview page templates.
    if (!empty($variables['page']['#title'])) {
      $variables['plain_title'] = strip_tags($variables['page']['#title']);
    }

    // Hide page title for pages where we want to
    // display it in the Hero instead, like event, topic, basic page.
    // First determine if we are looking at a node.
    $nid = $this->routeMatch->getRawParameter('node');
    $node = FALSE;

    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();

    if ($nid !== NULL) {
      $node = $this->nodeStorage->load($nid);
    }

    if ($node instanceof Node) {

      // List pages where we want to hide the default page title.
      $page_to_exclude = [
        'event',
        'topic',
        'page',
        'book',
      ];

      // Alter list of content types where need to hide the default page title.
      $this->moduleHander->alter('social_content_type', $page_to_exclude);

      $paths_to_exclude = [
        'edit',
        'add',
        'delete',
      ];

      $in_path = str_replace($paths_to_exclude, '', $current_path) != $current_path;

      if (!$in_path) {

        // If there is a title and node type is excluded remove class.
        if (in_array($node->bundle(), $page_to_exclude, TRUE)) {
          $attributes->removeClass('with-title-region');
          $variables['display_page_title'] = FALSE;
        }

      }

    }

    // Check complementary_top and complementary_bottom variables.
    if ($variables['page']['complementary_top'] || $variables['page']['complementary_bottom']) {
      $attributes->addClass('layout--with-complementary');
    }
    // Check if sidebars are empty.
    if (empty($variables['page']['sidebar_first']) && empty($variables['page']['sidebar_second'])) {
      if (!$attributes->hasClass('layout--with-complementary')) {
        $attributes->addClass('layout--with-complementary');
      }
    }
    // Sidebars logic.
    if (empty($variables['page']['complementary_top']) && empty($variables['page']['complementary_bottom'])) {
      if ($variables['page']['sidebar_first'] && $variables['page']['sidebar_second']) {
        $attributes->addClass('layout--with-three-columns');
      }
      if (!empty($variables['page']['sidebar_second']) xor !empty($variables['page']['sidebar_first'])) {
        $attributes->addClass('layout--with-two-columns');
      }
    }

    // This behavior should be fixed in the if statements above on the checks
    // for empty sidebars and complementary top/bottom parts. Due to time
    // restrains for TB-4116 I've added an additional rule to this quick-fix
    // solution.
    // @see https://www.drupal.org/project/social/issues/3119191
    // @todo remove the if statement below and fix logic mentioned above.
    $route = $this->routeMatch->getRouteName();

    $routes_remove_complementary_class = [
      'view.event_manage_enrollments.page_manage_enrollments',
      'view.group_manage_members.page_group_manage_members',
      'view.group_pending_members.membership_requests',
      'view.event_manage_enrollment_requests.page_manage_enrollment_requests',
      'view.event_manage_enrollment_invites.page_manage_enrollment_invites',
      'view.user_event_invites.page_user_event_invites',
      'view.social_group_invitations.page_1',
      'view.social_group_user_invitations.page_1',
    ];

    if (in_array($route, $routes_remove_complementary_class)) {
      $attributes->removeClass('row', 'layout--with-complementary');
    }

    if (
      $route === 'entity.node.canonical' &&
      $this->routeMatch->getParameter('node')->bundle() === 'album'
    ) {
      $attributes->removeClass('row', 'layout--with-complementary');
    }

    // Let's grab all entities available from the route params.
    foreach ($this->routeMatch->getParameters() as $param) {
      // If it is an Entity, lets see if layout_builder is enabled
      // and remove or add necessary classes.
      if ($param instanceof EntityInterface &&
        $this->moduleHander->moduleExists('layout_builder') &&
        $this->moduleHander->moduleExists('social_core')
      ) {
        if (\Drupal::hasService('social_core.layout') && 
          \Drupal::service('social_core.layout')->isTrueLayoutCompatibleEntity($param)
        ) {
          $attributes->removeClass('row', 'layout--with-complementary');
          $attributes->addClass('layout--full');
        }
      }
    }

    $variables['content_attributes'] = $attributes;
  }

}
