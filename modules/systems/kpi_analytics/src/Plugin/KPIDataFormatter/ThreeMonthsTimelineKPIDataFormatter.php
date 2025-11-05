<?php

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'ThreeMonthsTimelineKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "three_months_timeline_kpi_data_formatter",
 *  label = @Translation("3 Months (+ current) Timeline KPI data formatter"),
 * )
 */
class ThreeMonthsTimelineKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $block = NULL): array {
    $months = [];

    for ($i = 0; $i < 4; $i++) {
      $months[] = date('Y-m', strtotime('first day of this month - ' . $i . ' month'));
    }

    $formatted_data = [];

    if ($data) {
      foreach ($data as $value) {
        if (!in_array($value['created'], $months)) {
          continue;
        }

        $date = $value['created'];
        $time = strtotime($value['created']);
        $value['created'] = $this->dateFormatter->format($time, '', 'F');
        $formatted_data[$date] = $value;
      }
    }

    $current_date = date('Y-m');

    foreach ($months as $month) {
      if (!isset($formatted_data[$month])) {
        $time = strtotime($month);
        $value = reset($data);
        $keys = array_keys($value ?: []);
        $formatted_data[$month] = array_fill_keys($keys, 0);
        $formatted_data[$month]['created'] = $this->dateFormatter->format($time, '', 'F');
      }

      if ($current_date == $month) {
        $formatted_data[$month]['highlight'] = TRUE;
      }
    }

    ksort($formatted_data);

    return array_values($formatted_data);
  }

}
