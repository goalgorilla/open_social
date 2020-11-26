<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * @DataProducer(
 *   id = "account_id",
 *   name = @Translation("Account identifier"),
 *   description = @Translation("Returns the account identifier."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Identifier")
 *   ),
 *   consumes = {
 *     "account" = @ContextDefinition("any",
 *       label = @Translation("AccountInterface instance")
 *     )
 *   }
 * )
 */
class AccountId extends DataProducerPluginBase {

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return mixed
   */
  public function resolve(AccountInterface $account) {
    return $account->id();
  }

}
