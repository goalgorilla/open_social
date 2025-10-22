<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Plugin\Block\PageTitleBlock;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'CourseMaterialHeroBlock' block.
 *
 * @Block(
 *   id = "course_material_hero",
 *   admin_label = @Translation("Course material hero block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseMaterialHeroBlock extends PageTitleBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $parent_course_type = NULL;
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $translation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      $title = $node->getTitle();

      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $course = $course_wrapper->getCourse();
      if ($course instanceof GroupInterface && !$course->get('field_course_type')->isEmpty()) {
        $parent_course_type = Term::load($course->field_course_type->target_id)->getName();
      }
      return [
        '#theme' => 'course_material_hero',
        '#title' => $title,
        '#node' => $node,
        '#section_class' => 'page-title',
        '#parent_course_type' => $parent_course_type,
      ];
    }
    else {
      $request = \Drupal::request();

      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);

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
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $group = \Drupal::service('social_course.course_wrapper')
        ->setCourseFromMaterial($node)
        ->getCourse();

      return AccessResult::allowedIf($group instanceof GroupInterface);
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
