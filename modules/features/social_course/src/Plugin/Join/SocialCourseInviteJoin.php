<?php

namespace Drupal\social_course\Plugin\Join;

use Drupal\social_course\CourseWrapperInterface;
use Drupal\social_group_invite\Plugin\Join\SocialGroupInviteJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a join plugin instance for joining after invitation.
 */
class SocialCourseInviteJoin extends SocialGroupInviteJoin {

  /**
   * The course wrapper.
   */
  private CourseWrapperInterface $wrapper;

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

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function types(): array {
    return array_merge(parent::types(), $this->wrapper->getAvailableBundles());
  }

}
