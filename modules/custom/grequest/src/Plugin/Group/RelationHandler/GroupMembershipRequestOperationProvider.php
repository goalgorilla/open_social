<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the group_membership_request relation plugin.
 */
class GroupMembershipRequestOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * Constructs a new GroupMembershipRequestOperationProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface $parent
   *   The default operation provider.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    OperationProviderInterface $parent,
    AccountProxyInterface $current_user,
    TranslationInterface $string_translation
  ) {
    $this->parent = $parent;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = $this->parent->getGroupOperations($group);
    $url = $group->toUrl('group-request-membership');
    if ($url->access($this->currentUser())) {
      $entity_instances = $this->getRelationships($group);
      if (count($entity_instances) == 0) {
        $operations['group-request-membership'] = [
          'title' => $this->t('Request group membership'),
          'url' => $url,
          'weight' => 99,
        ];
      }
    }

    // @todo With the new VariationCache, we can use the above context.
    $operations['#cache']['contexts'] = ['user'];

    return $operations;
  }

  /**
   * Get relationship for the current plugin in the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *  Group.
   *
   * @return array|\Drupal\group\Entity\GroupRelationshipInterface[]
   *   List of group relationships.
   */
  protected function getRelationships(GroupInterface $group) {
    // We can use loadByEntityAndGroup, but for this we need load user entity.
    // @see https://www.drupal.org/project/group/issues/3310605
    $properties = [
      'entity_id' => $this->currentUser()->id(),
      'plugin_id' => $this->pluginId,
      'gid' => $group->id(),
    ];
    return $this->entityTypeManager()->getStorage('group_content')->loadByProperties($properties);
  }


}
