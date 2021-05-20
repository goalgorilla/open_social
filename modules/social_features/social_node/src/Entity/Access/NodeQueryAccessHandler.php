<?php

namespace Drupal\social_node\Entity\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;

/**
 * Controls query access for node entities.
 *
 * @see \Drupal\entity\QueryAccess\QueryAccessHandler
 */
class NodeQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account) {
    $conditions = parent::buildConditions($operation, $account);

    // The user doesn't have access to any entities by default, but in this case
    // anonymous can view some content, so then following logic added below.
    // See social_node_query_entity_query_alter.
    if ($account->isValidOauth2Account() && $conditions->isAlwaysFalse()) {
      return $conditions->alwaysFalse(FALSE);
    }

    return $conditions;
  }

}
