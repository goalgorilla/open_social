<?php

namespace Drupal\social_course\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_access
 */
class ContentAccessCheck extends EntityCreateAccessCheck {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The key used by the routing requirement.
   *
   * @var string
   */
  protected $requirementsKey = '_course_content_add_access';

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    [$entity_type, $bundle] = explode(':', $route->getRequirement($this->requirementsKey) . ':');

    // Forbidden access on /node/add/{type} for following types.
    $forbidden = ['course_article', 'course_section', 'course_video'];
    // Allow other modules to register their own types.
    $this->moduleHandler->alter('social_course_material_types', $forbidden);

    // The bundle argument can contain request argument placeholders like
    // {name}, loop over the raw variables and attempt to replace them in the
    // bundle name. If a placeholder does not exist, it won't get replaced.
    if ($bundle && strpos($bundle, '{') !== FALSE) {
      foreach ($route_match->getRawParameters()->all() as $name => $value) {
        $bundle = str_replace('{' . $name . '}', $value, $bundle);
      }
      // If we were unable to replace all placeholders, deny access.
      if (strpos($bundle, '{') !== FALSE) {
        return AccessResult::neutral(sprintf("Could not find '%s' request argument, therefore cannot check create access.", $bundle));
      }

      // Deny access if bundle is in the forbidden list.
      if ($entity_type === 'node' && in_array($bundle, $forbidden, TRUE)) {
        return AccessResult::forbidden();
      }
    }
    return $this->entityTypeManager->getAccessControlHandler($entity_type)->createAccess($bundle, $account, [], TRUE);
  }

}
