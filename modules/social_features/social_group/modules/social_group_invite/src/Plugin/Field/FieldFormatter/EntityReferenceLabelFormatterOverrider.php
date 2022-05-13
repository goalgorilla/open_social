<?php

namespace Drupal\social_group_invite\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides overrider for 'entity reference label' formatter.
 */
class EntityReferenceLabelFormatterOverrider extends EntityReferenceLabelFormatter {

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityRepository = $container->get('entity.repository');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    if ($items->getName() !== 'gid' || $this->routeMatch->getRouteName() !== 'view.social_group_user_invitations.page_1') {
      return parent::getEntitiesToView($items, $langcode);
    }

    $entities = [];
    /**
     * @var int $delta
     * @var \Drupal\group\Entity\GroupInterface $entity
     */
    foreach ($items->referencedEntities() as $delta => $entity) {
      $entities[$delta] = $this->entityRepository->getTranslationFromContext($entity, $langcode);
    }

    return $entities;
  }

}
