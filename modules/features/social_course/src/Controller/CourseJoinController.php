<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_course\Form\CourseJoinAnonymousForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Course join routes.
 */
class CourseJoinController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);

    $instance->formBuilder = $container->get('form_builder');

    return $instance;
  }

  /**
   * Callback to request membership for anonymous.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity object.
   */
  public function anonymousRequestMembership(GroupInterface $group): AjaxResponse {
    return (new AjaxResponse())
      ->addCommand(new OpenModalDialogCommand(
        $this->t(
          'Join a "@group_title" course',
          ['@group_title' => $group->label()],
        ),
        $this->formBuilder()->getForm(
          CourseJoinAnonymousForm::class,
          $group,
        ),
        [
          'width' => '337px',
          'dialogClass' => 'social_group-popup social_group-popup--anonymous',
        ],
      ),
    );
  }

}
