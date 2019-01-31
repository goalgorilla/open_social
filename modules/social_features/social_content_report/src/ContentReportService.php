<?php

namespace Drupal\social_content_report;

use Drupal\flag\Entity\Flag;

/**
 * Provides a content report service.
 */
class ContentReportService {

  /**
   * Gets all the 'report_' flag types.
   *
   * This makes it more flexible so when new flags are
   * added, it automatically gets them as well.
   *
   * @return array
   *   List of flag type IDs that are used for reporting.
   */
  public function getReportFlagTypes() {
    $all_flags = Flag::loadMultiple();
    $report_flags = [];
    if (!empty($all_flags)) {
      // Check if this is a report flag.
      foreach ($all_flags as $flag) {
        if (strpos($flag->id, 'report_') === 0) {
          $report_flags[] = $flag->id;
        }
      }
    }

    return $report_flags;
  }

}
