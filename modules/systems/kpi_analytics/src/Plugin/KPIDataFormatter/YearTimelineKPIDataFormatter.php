<?php

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'YearTimelineKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "year_timeline_kpi_data_formatter",
 *  label = @Translation("Year Timeline KPI data formatter"),
 * )
 */
class YearTimelineKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $block = NULL): array {
    $months = [];
    $current_month = $data ? date('n') : 12;

    for ($i = 1; $i <= $current_month; $i++) {
      $months[] = date('Y-m', mktime(0, 0, 0, $i, 1));
    }

    $formatted_data = [];

    if ($data) {
      foreach ($data as $value) {
        $date = $value['created'];
        $time = strtotime($value['created']);
        $value['created'] = $this->dateFormatter->format($time, '', 'F');
        $formatted_data[$date] = $value;
      }
    }

    $last_item = current($data);
    $current_date = date('Y-m');

    foreach ($months as $month) {
      if (!isset($formatted_data[$month])) {
        $time = strtotime($month);
        $formatted_data[$month] = $last_item;
        $formatted_data[$month]['created'] = $this->dateFormatter->format($time, '', 'F');
      }
      else {
        $last_item = $formatted_data[$month];
      }

      if ($current_date == $month) {
        $formatted_data[$month]['highlight'] = TRUE;
      }
    }

    ksort($formatted_data);

    return array_values($formatted_data);
  }

}
