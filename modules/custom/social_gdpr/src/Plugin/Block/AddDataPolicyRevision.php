<?php

namespace Drupal\social_gdpr\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'AddDataPolicyRevision' block.
 *
 * @Block(
 *  id = "add_data_policy_revision",
 *  admin_label = @Translation("Add Data Policy Revision"),
 * )
 */
class AddDataPolicyRevision extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'content' => [
        '#type' => 'link',
        '#title' => $this->t('Add new revision'),
        '#url' => Url::fromRoute('social_gdpr.data_policy.add'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-primary',
            'btn-raised',
            'btn-full',
            'waves-effect',
            'brand-bg-primary',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'edit data policy');
  }

}
