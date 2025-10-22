<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\Entity\CourseEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CourseMaterialNavigationBlock' block.
 *
 * @Block(
 *   id = "course_material_navigation",
 *   admin_label = @Translation("Course material navigation block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseMaterialNavigationBlock extends CourseSectionNavigationBlock {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $translation = $this->entityRepository->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      $this->courseWrapper->setCourseFromMaterial($node);
      $section = $this->courseWrapper->getSectionFromMaterial($node);
      $items = [];
      $storage = $this->entityTypeManager->getStorage('course_enrollment');
      $course_enrollments = $storage->loadByProperties([
        'sid' => $section->id(),
        'uid' => $this->currentUser->id(),
        'gid' => $this->courseWrapper->getCourse()->id(),
      ]);

      foreach ($course_enrollments as $key => $course_enrollment) {
        unset($course_enrollments[$key]);
        $course_enrollments[$course_enrollment->get('mid')->target_id] = $course_enrollment;
      }

      /** @var \Drupal\node\NodeInterface $material */
      foreach ($this->courseWrapper->getMaterials($section) as $material) {
        $item = [
          'label' => $material->label(),
          'url' => FALSE,
          'type' => $material->bundle(),
          'active' => FALSE,
          'number' => $this->courseWrapper->getMaterialNumber($material) + 1,
          'finished' => FALSE,
        ];

        if ($material->id() === $node->id()) {
          $item['active'] = TRUE;
        }

        if (isset($course_enrollments[$material->id()]) && $course_enrollments[$material->id()]->getStatus() === CourseEnrollmentInterface::FINISHED) {
          $item['finished'] = TRUE;
        }

        if ($this->courseWrapper->materialAccess($material, $this->currentUser, 'view')->isAllowed()) {
          $item['url'] = $material->toUrl();
        }

        $items[] = $item;
      }

      return [
        '#theme' => 'course_material_navigation',
        '#items' => $items,
        '#parent_course' => [
          'label' => $this->courseWrapper->getCourse()->label(),
          'url' => $this->courseWrapper->getCourse()->toUrl(),
        ],
        '#parent_section' => [
          'label' => $section->label(),
          'url' => $section->toUrl(),
        ],
      ];
    }
    else {
      if ($route = $this->request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = $this->titleResolver->getTitle($this->request, $route);

        return [
          '#type' => 'page_title',
          '#title' => $title,
        ];
      }
      else {
        return [
          '#type' => 'page_title',
          '#title' => '',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $this->courseWrapper->setCourseFromMaterial($node);
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getCourse()->getCacheTags());
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getSectionFromMaterial($node)->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $group = $this->courseWrapper->setCourseFromMaterial($node)->getCourse();

      return AccessResult::allowedIf($group instanceof GroupInterface);
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
