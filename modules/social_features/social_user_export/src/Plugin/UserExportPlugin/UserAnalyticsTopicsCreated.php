<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsTopicsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_topics_created",
 *  label = @Translation("Topics created"),
 *  weight = -230,
 * )
 */
class UserAnalyticsTopicsCreated extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Topics created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    $query = $this->database->select('node', 'n');
    $query->join('node_field_data', 'nfd', 'nfd.nid = n.nid');
    $query
      ->condition('nfd.uid', $entity->id())
      ->condition('nfd.type', 'topic');

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
