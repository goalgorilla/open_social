<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Retrieves the user ID for an account instance.
 *
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
   * Resolves the request to the requested values.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to get the id for.
   *
   * @return mixed
   *   The user id.
   */
  public function resolve(AccountInterface $account) {
    return $account->id();
  }

}
