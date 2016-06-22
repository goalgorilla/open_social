<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupContentController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for GroupContent routes.
 */
class GroupContentController extends ControllerBase {

  /**
   * Provides the group submission form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param string $plugin_id
   *   The group content enabler to add content with.
   *
   * @return array
   *   A group submission form.
   */
  public function add(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);

    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
    ]);

    return $this->entityFormBuilder()->getForm($group_content, 'add');
  }

  /**
   * The _title_callback for the entity.group_content.add_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param string $plugin_id
   *   The group content enabler to add content with.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
    $group_content_type = GroupContentType::load($plugin->getContentTypeConfigId());
    return $this->t('Create @name', ['@name' => $group_content_type->label()]);
  }

}
