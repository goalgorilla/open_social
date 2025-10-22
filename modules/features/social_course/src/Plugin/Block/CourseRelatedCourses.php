<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CourseRelatedCourses' block.
 *
 * @Block(
 *   id = "related_courses",
 *   admin_label = @Translation("Related courses"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class CourseRelatedCourses extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a CourseMaterialNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ids = [];
    $items = [];
    $group = $this->getContextValue('group');

    foreach ($group->get('field_course_related_courses')->getValue() as $field_value) {
      $ids[] = $field_value['target_id'];
    }

    $storage = $this->entityTypeManager->getStorage('group');
    $related_courses = $storage->loadMultiple($ids);
    foreach ($related_courses as $related_course) {
      $items[] = [
        'label' => $related_course->label(),
        'url' => $related_course->toUrl(),
      ];
    }

    return [
      '#theme' => 'course_related_courses',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $group = $this->getContextValue('group');
    if (!empty($group->get('field_course_related_courses')->getValue())) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
