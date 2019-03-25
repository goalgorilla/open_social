<?php

namespace Drupal\social_content_report;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ContentReportServiceInterface.
 *
 * @package Drupal\social_content_report
 */
interface ContentReportServiceInterface {

  /**
   * Gets all the 'report_' flag types.
   *
   * This makes it more flexible so when new flags are
   * added, it automatically gets them as well.
   *
   * @return array
   *   List of flag type IDs that are used for reporting.
   */
  public function getReportFlagTypes(): array;

  /**
   * Returns a modal link to the reporting form to use in a #links array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create the report for.
   * @param string $flag_id
   *   The flag ID.
   *
   * @return array|null
   *   A renderable array to be used in a #links array or FALSE if the user has
   *   no access.
   */
  public function getModalLink(EntityInterface $entity, $flag_id): ?array;

}
