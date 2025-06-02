<?php

namespace Drupal\social_group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Access\GroupAccessControlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extended access controller for the Group entity.
 *
 * @see social_group_entity_type_alter()
 */
class SocialGroupAccessControlHandler extends GroupAccessControlHandler implements EntityHandlerInterface {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the group access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The  Entity Type Manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);

    $this->configFactory = $config_factory;
    // Adding Entity type manager to keep this object that is defined in parent
    // but in GroupAccessControlHandler::createInstance().
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (
      !$account->hasPermission('bypass create group access') &&
      !$this->configFactory->get('social_group.settings')->get('allow_group_create')
    ) {
      return AccessResult::forbidden();
    }

    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
