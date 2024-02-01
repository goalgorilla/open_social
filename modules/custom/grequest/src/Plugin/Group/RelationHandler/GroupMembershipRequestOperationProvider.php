<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the grequest relation plugin.
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
  public function __construct(OperationProviderInterface $parent, AccountProxyInterface $current_user, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = $this->parent->getGroupOperations($group);

    if (!$group->getMember($this->currentUser) && $group->hasPermission('request group membership', $this->currentUser)) {
      $operations['group-request-membership'] = [
        'title' => $this->t('Request group membership'),
        'url' => new Url(
          'grequest.request_membership',
          ['group' => $group->id()],
          ['query' => ['destination' => Url::fromRoute('<current>')->toString()]]
        ),
        'weight' => 0,
      ];
    }

    return $operations;
  }

}
