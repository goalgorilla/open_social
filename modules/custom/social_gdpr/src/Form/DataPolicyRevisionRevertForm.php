<?php

namespace Drupal\social_gdpr\Form;

use Drupal\Core\Url;
use Drupal\gdpr_consent\Form\DataPolicyRevisionRevertForm as DataPolicyRevisionRevertFormBase;

/**
 * Provides a form for reverting a Data policy revision.
 *
 * @ingroup social_gdpr
 */
class DataPolicyRevisionRevertForm extends DataPolicyRevisionRevertFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('social_gdpr.data_policy.revisions');
  }

}
