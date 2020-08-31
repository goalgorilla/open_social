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
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $config_factory = \Drupal::service('config.factory');
    $config = $config_factory->get('mentions.settings');
    $suggestion_format = $config->get('suggestions_format');
    $suggestion_amount = $config->get('suggestions_amount');
    $ids = $this->getUserIdsFromName($match, $suggestion_amount, $suggestion_format);

    if (empty($ids)) {
      return parent::buildEntityQuery($match, $match_operator);
    }

    $query = parent::buildEntityQuery(NULL, $match_operator);
    $query->condition('uid', $ids, 'IN');

    return $query;
  }

}
