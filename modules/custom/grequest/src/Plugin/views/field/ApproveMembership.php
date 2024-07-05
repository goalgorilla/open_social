<?php

namespace Drupal\grequest\Plugin\views\field;

/**
 * Field handler to present a link to approve a membership request.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("approve_membership_request")
 */
class ApproveMembership extends MembershipEntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'group-approve-membership';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Approve membership');
  }

}
