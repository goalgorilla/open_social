<?php

namespace Drupal\social_course\Plugin\Join;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\social_course\CourseWrapperInterface;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\Plugin\Join\SocialGroupDirectJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for joining directly.
 */
class SocialCourseDirectJoin extends SocialGroupDirectJoin {

  /**
   * The course wrapper.
   */
  private CourseWrapperInterface $wrapper;

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
    $plugin_definition,
  ): self {
    /** @var self $instance */
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->wrapper = $container->get('social_course.course_wrapper');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = parent::actions($entity, $variables);

    if (
      !$items &&
      $this->currentUser->isAnonymous() &&
      $entity->getEntityTypeId() === 'group' &&
      in_array($entity->bundle(), $this->wrapper->getAvailableBundles()) &&
      $entity->hasField('field_flexible_group_visibility') &&
      $entity->hasField('field_group_allowed_join_method') &&
      $entity->getFieldValue('field_flexible_group_visibility', 'value') === 'public'
    ) {
      $url = Url::fromRoute('social_course.join', ['group' => $entity->id()]);

      if ($url->access()) {
        $items[] = [
          'label' => $this->t('Join'),
          'url' => $url,
          'attributes' => [
            'class' => ['btn-accent', 'use-ajax'],
          ],
        ];

        $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';

        if ($this->moduleHandler->moduleExists('social_group_request')) {
          $variables['#attached']['library'][] = 'social_group_request/social_group_popup';
        }
      }
    }

    return $items;
  }

}
