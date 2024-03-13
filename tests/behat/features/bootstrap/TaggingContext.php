<?php

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Database\StatementWrapper;
use Drupal\taxonomy\TermInterface;

/**
 * Defines test steps around the usage of user.
 */
class TaggingContext extends RawMinkContext {

  /**
   * Fill placement data to show tag for entities.
   *
   * @Given I enable content tag :term_name for all entities
   */
  public function enableContentTagForAllEntities(string $term_name): void {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name]);
    $term = reset($term);
    if (!$term instanceof TermInterface) {
      throw new \Exception("Term '{$term_name}' does not exist.");
    }
    /** @var \Drupal\social_tagging\SocialTaggingServiceInterface $helper */
    $helper = \Drupal::service('social_tagging.tag_service');
    $options = $helper->getKeyValueOptions();
    // Option contains key=>value array where values are a label.
    // Get keys, and serialize like in TaggingUsageWidget.
    $values = array_keys($options);
    $term->set('field_category_usage', serialize($values))->save();
  }

}
