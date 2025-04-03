<?php

namespace Drupal\grequest\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides request membership link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_request_membership")
 */
final class RequestMembership extends FieldPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RequestMembership constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally override query to do nothing.
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\group\Entity\Group $group */
    $group = $values->_entity;
    if (!($group instanceof GroupInterface) && !empty($values->_relationship_entities['gid'])) {
      $group = $values->_relationship_entities['gid'];
    }

    $build = NULL;
    if (empty($group) || !$group->getGroupType()->hasPlugin('group_membership_request')) {
      return $build;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (empty($user)) {
      return $build;
    }
    $membership_requests = $group->getRelationshipsByEntity($user, 'group_membership_request');
    if (!empty($group->getMember($this->currentUser))) {
      $build['#markup'] = $this->t('Already member');
    }
    elseif (empty($membership_requests)) {
      $link = $group->toLink($this->t('Request Membership'), 'group-request-membership');
      if($link->getUrl()->access($this->currentUser)){
        $build = $link->toString();
      }
    }
    else {
      $membership_request = reset($membership_requests);
      if ($membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value == GroupMembershipRequest::REQUEST_PENDING) {
        $build['#markup'] = $this->t('Pending membership request');
      }
      else {
        $build['#markup'] = $this->t('Rejected membership request');
      }
    }
    return $build;
  }

}
