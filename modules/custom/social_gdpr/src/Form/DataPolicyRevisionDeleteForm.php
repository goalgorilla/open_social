<?php

namespace Drupal\social_gdpr\Form;

use Drupal\Core\Url;
use Drupal\data_policy\Form\DataPolicyRevisionDeleteForm as DataPolicyRevisionDeleteFormBase;

/**
 * Provides a form for deleting a Data policy revision.
 *
 * @ingroup social_gdpr
 */
class DataPolicyRevisionDeleteForm extends DataPolicyRevisionDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('social_gdpr.data_policy.revisions');
  }

}
