<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'CourseAddSectionBlock' block.
 *
 * @Block(
 *   id = "course_add_section",
 *   admin_label = @Translation("Course add section block"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class CourseAddSectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  protected function blockAccess(AccountInterface $account) {
    $group = $this->getContextValue('group');

    if ($group->hasPermission('create group_node:course_section entity', $account)) {
      return AccessResult::allowed();
    }

    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $group = $this->getContextValue('group');
    $build = [];

    $url = Url::fromRoute('entity.group_content.create_form', [
      'group' => $group->id(),
      'plugin_id' => 'group_node:course_section',
    ], [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-raised',
          'waves-effect',
          'brand-bg-primary',
        ],
      ],
    ]);

    $build['content'] = Link::fromTextAndUrl($this->t('Create Section'), $url)->toRenderable();

    return $build;
  }

}
