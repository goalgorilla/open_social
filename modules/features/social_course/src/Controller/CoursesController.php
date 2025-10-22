<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\Entity\CourseEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Course general routes.
 */
class CoursesController extends ControllerBase {

  /**
   * The course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapperInterface
   */
  protected $courseWrapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseWrapper = $container->get('social_course.course_wrapper');

    return $instance;
  }

  /**
   * Controller action.
   *
   * @return mixed
   *   Renderable drupal array.
   */
  public function content() {
    $content = [];
    $bundles = $this->courseWrapper->getAvailableBundles();

    foreach ($this->entityTypeManager()->getStorage('group_type')->loadMultiple() as $type) {
      if (in_array($type->id(), $bundles)) {
        $content[$type->id()] = $type;
      }
    }

    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity.group.add_form', ['group_type' => $type->id()]);
    }

    return [
      '#theme' => 'course_add_list',
      '#content' => $content,
    ];
  }

  /**
   * Determines if user has access to course creation page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $bundles = $this->courseWrapper->getAvailableBundles();

    foreach ($bundles as $bundle) {
      if ($account->hasPermission("create {$bundle} group")) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Callback of "/group/{group}/section/{node}/start".
   */
  public function startSection(GroupInterface $group, NodeInterface $node) {
    // Get first material.
    $material = $node->get('field_course_section_content')->get(0)->entity;
    $material_redirect = $this->redirect('entity.node.canonical', [
      'node' => $material->id(),
    ]);

    $storage = $this->entityTypeManager()->getStorage('course_enrollment');

    $exists = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $this->currentUser()->id())
      ->condition('sid', $node->id())
      ->execute();

    // If user has already started or finished section, we just redirect them
    // to the material instead of creating a new enrollment.
    if ($exists) {
      return $material_redirect;
    }

    // Join user to course.
    $enrollment = $storage->create([
      'gid' => $group->id(),
      'sid' => $node->id(),
      'mid' => $material->id(),
      'status' => CourseEnrollmentInterface::IN_PROGRESS,
    ]);
    $enrollment->save();

    $this->messenger()->addMessage($this->t('You have successfully enrolled'));

    $tags = $group->getCacheTags();
    $tags = Cache::mergeTags($tags, $node->getCacheTags());
    $tags = Cache::mergeTags($tags, $material->getCacheTags());
    Cache::invalidateTags($tags);

    return $material_redirect;
  }

  /**
   * Access callback function for "/group/{group}/section/{node}/start" page.
   */
  public function startSectionAccess(GroupInterface $group, NodeInterface $node) {
    $this->courseWrapper->setCourse($group);
    return $this->courseWrapper->sectionAccess($node, $this->currentUser(), 'start');
  }

  /**
   * Callback of "/group/{group}/section/{node}/next".
   */
  public function nextMaterial(GroupInterface $group, NodeInterface $node) {
    $storage = $this->entityTypeManager()->getStorage('course_enrollment');
    $field = $node->get('field_course_section_content');
    $this->courseWrapper->setCourse($group);
    $current_material = NULL;
    $next_material = NULL;
    $account = $this->currentUser();

    foreach ($field->getValue() as $key => $value) {
      $exists = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('gid', $group->id())
        ->condition('sid', $node->id())
        ->condition('mid', $value['target_id'])
        ->condition('uid', $account->id())
        ->execute();

      if (!$exists) {
        $enrollment = $storage->create([
          'gid' => $group->id(),
          'sid' => $node->id(),
          'mid' => $value['target_id'],
          'status' => CourseEnrollmentInterface::IN_PROGRESS,
        ]);
        $enrollment->save();

        $next_material = $field->get($key)->entity;
        break;
      }
      elseif (!$current_material) {
        /** @var \Drupal\social_course\Entity\CourseEnrollment[] $course_enrollments */
        $course_enrollments = $storage->loadMultiple($exists);

        // For some reasons user can have more than one enrollment. So, here
        // we need to update status for all if the current attempt is finished.
        foreach ($course_enrollments as $enrollment) {
          // Set the correct status for all previous materials.
          if ($enrollment->getStatus() !== CourseEnrollmentInterface::FINISHED) {
            $enrollment->setStatus(CourseEnrollmentInterface::FINISHED);
            $enrollment->save();

            if ($field->get($key + 1)) {
              $current_material = $field->get($key)->entity;
              $next_material = $field->get($key + 1)->entity;
            }
          }
        }
      }
    }

    // Redirect after finishing course.
    if (!$group->get('field_course_redirect_url')->isEmpty()) {
      $uri = $group->get('field_course_redirect_url')->uri;
      $parsed_url = parse_url($uri);

      if (isset($parsed_url['host'])) {
        $response = new TrustedRedirectResponse($uri);
        $response->addCacheableDependency($uri);
      }
      else {
        try {
          $url = Url::fromUri($uri);
        }
        catch (\InvalidArgumentException $exception) {
          $url = Url::fromUserInput($uri);
        }
        $response = new RedirectResponse($url->toString());
      }
    }
    else {
      $response = new RedirectResponse($group->toUrl()->setAbsolute()->toString(), Response::HTTP_FOUND);
    }

    // Check if user has already seen last material.
    if (!$next_material) {
      $next_section = $this->courseWrapper->getSection($node, 1);

      if ($next_section) {
        $course_enrollment = $storage->loadByProperties([
          'gid' => $group->id(),
          'sid' => $next_section->id(),
          'uid' => $account->id(),
        ]);
        $course_enrollment = current($course_enrollment);
        $finish_section = !$course_enrollment || $course_enrollment->getStatus() !== CourseEnrollmentInterface::FINISHED;

        if ($finish_section) {
          $this->messenger()->addStatus($this->t('You have successfully finished the @title section', [
            '@title' => $node->label(),
          ]));
        }

        // Redirect to a specific page which set in section.
        $current_section = $this->courseWrapper->getSection($node, 0);
        if (!$this->courseWrapper->courseIsSequential() && !$current_section->get('field_course_section_redirect')->isEmpty()) {
          $uri = $current_section->get('field_course_section_redirect')->uri;
          $parsed_url = parse_url($uri);

          if (isset($parsed_url['host'])) {
            $response = new TrustedRedirectResponse($uri);
            $response->addCacheableDependency($uri);
          }
          else {
            try {
              $url = Url::fromUri($uri);
            }
            catch (\InvalidArgumentException $exception) {
              $url = Url::fromUserInput($uri);
            }
            $response = new RedirectResponse($url->toString());
          }
        }
        // Redirect to the next section when it exists and not marked as
        // completed.
        elseif ($finish_section) {
          // Cleanup related entity of "next section".
          $course_enrollment = $storage->loadByProperties([
            'gid' => $group->id(),
            'sid' => $next_section->id(),
            'uid' => $account->id(),
          ]);
          if ($course_enrollment) {
            $storage->delete($course_enrollment);
          }
          $response = self::nextMaterial($group, $next_section);
        }
        // Redirect to next step even if the next step was completed but current
        // material does not have a link of specific next material.
        else {
          $response = $this->redirect('entity.node.canonical', [
            'node' => $next_section->id(),
          ]);
        }

      }
    }
    else {
      $response = $this->redirect('entity.node.canonical', [
        'node' => $next_material->id(),
      ]);
    }

    $tags = $group->getCacheTags();
    $tags = Cache::mergeTags($tags, $node->getCacheTags());

    if ($next_material) {
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getMaterial($next_material, -1)->getCacheTags());
    }

    Cache::invalidateTags($tags);

    return $response;
  }

  /**
   * Access callback of "/group/{group}/section/{node}/next" page.
   */
  public function nextMaterialAccess(GroupInterface $group, NodeInterface $node) {
    $bundles = $this->courseWrapper->getAvailableBundles();

    // Forbid if group is not a course.
    if (!in_array($group->bundle(), $bundles)) {
      return AccessResult::forbidden();
    }

    // Forbid if node is not a section.
    if ($node->bundle() != 'course_section') {
      return AccessResult::forbidden();
    }

    // Forbid if section does not contain materials.
    $field = $node->get('field_course_section_content');

    if ($field->isEmpty()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Redirects users to their courses page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the courses of the currently logged in user.
   */
  public function redirectMyCourses(): RedirectResponse {
    return $this->redirect('view.courses_user.page', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
