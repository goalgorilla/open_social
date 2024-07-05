<?php

namespace Drupal\grequest\Plugin\views\field;

/**
 * Field handler to present a link to reject a membership request.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("reject_membership_request")
 */
class RejectMembership extends MembershipEntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'group-reject-membership';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Reject membership');
  }

}
