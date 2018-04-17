<?php

namespace Drupal\social_gdpr\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\gdpr_consent\Controller\DataPolicyController;

/**
 * Class DataPolicy.
 *
 *  Returns responses for Data policy route.
 */
class DataPolicy extends DataPolicyController {

  /**
   * {@inheritdoc}
   */
  public function revisionOverview() {
    $build = parent::revisionOverview();

    if ($this->allowRevert()) {
      $current_revision = TRUE;

      foreach ($build['data_policy_revisions_table']['#rows'] as &$row) {
        if ($current_revision) {
          $current_revision = FALSE;
          continue;
        }

        $row[1]['data']['#links']['revert']['attributes'] = [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'title' => $this->t('Are you sure to revert this revision'),
            'width' => 700,
          ]),
        ];
      }
    }

    return $build;
  }

}
