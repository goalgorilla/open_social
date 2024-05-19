<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;
use OpenSocial\TestBridge\Attributes\Command;
use Psr\Container\ContainerInterface;

class TaggingBridge {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Enable content tagging for all entities.
   *
   * @param string $term_name
   *
   * @return string[]
   *   The result.
   */
  #[Command('enable-content-tagging-for-all-entities')]
  public function enableContentTagForAllEntities(string $term_name) : array {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name]);
    $term = reset($term);
    if (!$term instanceof TermInterface) {
      return ['status' => 'error', 'error' => "Term '{$term_name}' does not exist."];
    }
    /** @var \Drupal\social_tagging\SocialTaggingServiceInterface $helper */
    $helper = \Drupal::service('social_tagging.tag_service');
    $options = $helper->getKeyValueOptions();
    // Option contains key=>value array where values are a label.
    // Get keys, and serialize like in TaggingUsageWidget.
    $values = array_keys($options);
    $term->set('field_category_usage', serialize($values))->save();

    return ['status' => 'ok'];
  }
}
