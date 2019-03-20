<?php

namespace Drupal\social_content_report\Access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\flag\FlagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlagAccessCheck.
 */
class FlagAccessCheck implements AccessInterface, ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * FlagAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Checks if user is allowed to flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag type.
   * @param int $entity_id
   *   The entity ID which is being reported.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed if user may use the flag and hasn't reported it yet.
   */
  public function access(AccountInterface $account, FlagInterface $flag, $entity_id) {
    $ids = $this->moduleHandler->invokeAll('social_content_report_flags');

    $this->moduleHandler->alter('social_content_report_flags', $ids);

    if (in_array($flag->id(), $ids)) {
      // Make sure user is allowed to use the flag.
      if (!$account->hasPermission('flag ' . $flag->id())) {
        return AccessResult::forbidden();
      }

      $entity = $this->entityTypeManager->getStorage($flag->getFlaggableEntityTypeId())->load($entity_id);
      $flagged = $flag->isFlagged($entity, $account);

      // If the user already flagged the content they aren't allowed to do it
      // again.
      if ($flagged) {
        return AccessResult::forbidden();
      }
      else {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

}
