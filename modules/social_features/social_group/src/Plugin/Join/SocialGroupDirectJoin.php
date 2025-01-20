<?php

namespace Drupal\social_group\Plugin\Join;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for joining directly.
 *
 * @Join(
 *   id = "social_group_direct_join",
 *   entityTypeId = "group",
 *   method = "direct",
 *   weight = 10,
 * )
 */
class SocialGroupDirectJoin extends JoinBase {

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = [];

    if (!$this->access($entity)) {
      return $items;
    }

    $entity_type_id = $entity->getEntityTypeId();

    $classes = ['btn-accent'];
    $url = Url::fromRoute(
      'entity.' . $entity_type_id . '.join',
      [$entity_type_id => $entity->id()],
    );

    // Override join link behaviour for anonymous users to display popup window.
    // So, anonymous users can easily login or sign-up to a group.
    if ($this->currentUser->isAnonymous()) {
      $url = Url::fromRoute('social_group.anonymous_join', ['group' => $entity->id()]);

      array_push($classes, 'use-ajax');
      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';

      if ($this->moduleHandler->moduleExists('social_group_request')) {
        $variables['#attached']['library'][] = 'social_group_request/social_group_popup';
      }
    }

    $items[] = [
      'label' => $this->t('Join'),
      'url' => $url,
      'attributes' => [
        'class' => $classes,
      ],
    ];

    $variables['group_operations_url'] = $url;

    return $items;
  }

  /**
   * Check if a user can join directly.
   *
   * @param \Drupal\social_group\EntityMemberInterface $entity
   *   The membership entity object.
   */
  protected function access(EntityMemberInterface $entity): bool {
    /** @var \Drupal\social_group\SocialGroupInterface $entity */
    return $entity->hasPermission('join group', $this->currentUser) ||
      $this->currentUser->isAnonymous() &&
      $entity->bundle() === 'flexible_group';
  }

}
