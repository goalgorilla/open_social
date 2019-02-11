<?php

namespace Drupal\social_content_report\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to close a report.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flagging_link_close")
 */
class LinkReportClose extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $this
      ->getEntity($row);
    return Url::fromRoute('social_content_report.close_report', [
      'flagging' => $flagging->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this
      ->t('Close');
  }

}
