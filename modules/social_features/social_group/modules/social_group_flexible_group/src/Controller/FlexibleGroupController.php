<?php

namespace Drupal\social_group_flexible_group\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\group\Entity\GroupInterface;

/**
 * Class FlexibleGroupController.
 */
class FlexibleGroupController extends EntityController {

  /**
   * Callback function of group page.
   */
  public function canonical(GroupInterface $group) {
    return $this->redirect('view.group_information.page_group_about', [
      'group' => $group->id(),
    ]);
  }

}
