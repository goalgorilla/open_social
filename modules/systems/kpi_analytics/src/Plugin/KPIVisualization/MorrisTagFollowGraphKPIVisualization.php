<?php

namespace Drupal\kpi_analytics\Plugin\KPIVisualization;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\kpi_analytics\Plugin\KPIVisualizationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MorrisTagFollowGraphKPIVisualization' KPI Visualization.
 *
 * @KPIVisualization(
 *  id = "morris_tag_follow_graph_kpi_visualization",
 *  label = @Translation("Morris tag follow graph KPI visualization"),
 * )
 */
class MorrisTagFollowGraphKPIVisualization extends KPIVisualizationBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): KPIVisualizationBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $data): array {
    $uuid = $this->uuid->generate();

    $xkey = 'label';
    $ykeys = ['current'];

    $tids = array_map(fn($value) => $value['tid'], $data);

    $flagging_storage = $this->entityTypeManager->getStorage('flagging');

    $flags = 0;
    $new_followers = 0;
    if (!empty($tids)) {
      $flags = $flagging_storage->getAggregateQuery()
        ->accessCheck(TRUE)
        ->condition('entity_id', $tids, 'IN')
        ->condition('entity_type', 'taxonomy_term')
        ->groupBy('uid')
        ->count()
        ->execute();

      $new_followers = $flagging_storage->getAggregateQuery()
        ->accessCheck(TRUE)
        ->condition('entity_id', $tids, 'IN')
        ->condition('entity_type', 'taxonomy_term')
        ->condition('created', strtotime('first day of this month'), '>=')
        ->groupBy('uid')
        ->count()
        ->execute();
    }

    $ymax = 0;

    $diff = 0;

    foreach ($data as $value) {
      if ($value['current'] > $ymax) {
        $ymax = $value['current'];
      }

      $diff += $value['difference'];
    }

    $increase = ($ymax * 0.3) < 1 ? 1 : floor($ymax * 0.3);
    $ymax += $increase;

    // Data to render and Morris options.
    $options = [
      'element' => $uuid,
      'data' => $data,
      'xkey' => $xkey,
      'ykeys' => $ykeys,
      'parseTime' => FALSE,
      'labels' => $this->labels,
      'plugin' => 'Bar',
      'barColors' => $this->colors,
      'barSizeRatio' => '0.5',
      'horizontal' => TRUE,
      'hideHover' => TRUE,
      'ymax' => $ymax,
    ];

    return [
      '#theme' => 'kpi_analytics_morris_tag_follow_chart',
      '#type' => 'bar',
      '#uuid' => $uuid,
      '#followers' => $flags,
      '#difference' => $new_followers,
      '#attached' => [
        'library' => [
          'kpi_analytics/morris',
        ],
        'drupalSettings' => [
          'kpi_analytics' => [
            'morris' => [
              'chart' => [
                $uuid => [
                  'options' => $options,
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
