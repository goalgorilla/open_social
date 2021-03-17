<?php

namespace Drupal\social_profile\Plugin\EntityReferenceSelection;

use Drupal\social_profile\SocialProfileTrait;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection as UserSelectionBase;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "social:user",
 *   label = @Translation("Social user selection"),
 *   entity_types = {"user"},
 *   group = "social",
 *   weight = 1
 * )
 */
class UserSelection extends UserSelectionBase {

  use SocialProfileTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['include_anonymous'] = FALSE;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->buildEntityQuery(NULL, 'CONTAINS', $ids);
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   * @param array $ids
   *   (Optional) $ids that are coming from an earlier request.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS', array $ids = []) {
    // If an earlier request already had the ids don't query them again.
    if (empty($ids)) {
      $config_factory = \Drupal::service('config.factory');
      $config = $config_factory->get('mentions.settings');
      $suggestion_format = $config->get('suggestions_format');
      $suggestion_amount = $config->get('suggestions_amount');
      $ids = $this->getUserIdsFromName($match, $suggestion_amount, $suggestion_format);
    }

    // Add the ability to search users also by mail.
    if (empty($ids) && $this->currentUser->hasPermission('view profile email')) {
      $query = parent::buildEntityQuery(NULL, $match_operator);
      $query->condition('mail', $match, $match_operator);
      $ids = $query->execute();
    }

    if (empty($ids)) {
      return parent::buildEntityQuery($match, $match_operator);
    }

    $query = parent::buildEntityQuery(NULL, $match_operator);
    $query->condition('uid', $ids, 'IN');

    return $query;
  }

}
