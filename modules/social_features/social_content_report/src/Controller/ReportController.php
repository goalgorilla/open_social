<?php

namespace Drupal\social_content_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for social_content_report.
 *
 * @package Drupal\social_content_report\Controller
 */
class ReportController extends ControllerBase {

  /**
   * Function for suggestions.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The Flagging object to close.
   *
   *   A simple response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function close(FlaggingInterface $flagging): RedirectResponse {

    if ($flagging->hasField('field_status')) {
      // Disable the status field.
      $flagging->set('field_status', 0);
      $flagging->save();
    }

    return new RedirectResponse('view.report_overview.overview');
  }

}
