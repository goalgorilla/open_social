<?php

declare(strict_types = 1);

namespace Drupal\social_group\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modifies entity views based on social group relationships.
 *
 * This class alters views to consider specific cache contexts and ensures
 * appropriate handling of entity relationship definitions related to social
 * groups. It integrates with dependency injection for better maintainability
 * and utilizes hooks to adjust view presave operations.
 */
final class SocialGroupEntityAlter implements ContainerInjectionInterface {

  /**
   * Constructs a new SocialGroupEntityAlter class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation plugin manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly GroupRelationTypeManagerInterface $groupRelationTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group_relation_type.manager'),
    );
  }

  /**
   * Set "user.social_group_membership" cache context to view if needed.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity that is being saved.
   *
   * @see \hook_ENTITY_TYPE_presave()
   */
  #[Hook('view_presave')]
  public function viewPresave(ViewEntityInterface $view): void {
    $base_table = $view->get('base_table');

    $definitions = array_filter(
      array: $this->entityTypeManager->getDefinitions(),
      callback: fn ($definition) => $definition instanceof ContentEntityType &&
        (
          $definition->get('base_table') === $base_table ||
          $definition->get('data_table') === $base_table
        )
    );

    if (empty($definitions)) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityType[] $definitions */
    $definitions = array_filter(
      array: $definitions,
      callback: fn ($definition) => $definition->id() === 'group' ||
        $this->groupRelationTypeManager->getPluginIdsByEntityTypeId($definition->id())
    );

    if (empty($definitions)) {
      return;
    }

    // Add "user.social_group_membership" cache context to displays.
    $displays = $view->get('display');
    foreach ($displays as $id => $display) {
      if (($display['display_options']['cache']['type'] ?? '') === 'none') {
        continue;
      }

      if (in_array('user.social_group_membership', $display['cache_metadata']['contexts'] ?? [])) {
        continue;
      }

      $displays[$id]['cache_metadata']['contexts'][] = 'user.social_group_membership';
    }

    // Set value if displays were changed.
    if ($displays != $view->get('display')) {
      $view->set('display', $displays);
    }
  }

}
