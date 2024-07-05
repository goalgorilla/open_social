<?php

declare(strict_types=1);

namespace Drupal\grequest\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\MembershipRequestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for group membership actions.
 */
abstract class MembershipRequestActionBase extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Membership request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MembershipRequestManager $membership_request_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->membershipRequestManager = $membership_request_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('grequest.membership_request_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $entity->getGroup()->hasPermission('administer membership requests', $account);
    $result = $access ? AccessResult::allowed() : AccessResult::forbidden();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
