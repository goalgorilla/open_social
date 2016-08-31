<?php

/**
 * @file
 * Contains \Drupal\download_count\Controller\DownloadCountController.
 */

namespace Drupal\download_count\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
* Returns responses for download_count module routes.
*/
class DownloadCountController extends ControllerBase {

    /**
     * Builds the fields info overview page.
     *
     * @return array
     *   Array of page elements to render.
     */
    public function downloadCountReport() {
        $build = array();
        $config = \Drupal::config('download_count.settings');
        $build['#title'] = $config->get('download_count_view_page_title');

        $total_downloads = 0;
        $item = 1;
        $limit = $config->get('download_count_view_page_limit');
        $items_per_page = $config->get('download_count_view_page_items');
        $page_header = $config->get('download_count_view_page_header');
        $page_footer = $config->get('download_count_view_page_footer');
        $output = '<div id="download-count-page">';

        $header = array(
            array(
                'data' =>'#',
            ),
            array(
                'data' => $this->t('Count'),
                'field' => 'count',
                'sort' => 'desc',
            ),
            array(
                'data' => $this->t('FID'),
                'field' => 'FID',
            ),
            array(
                'data' => $this->t('Entity Type'),
                'field' => 'type',
            ),
            array(
                'data' => $this->t('Entity ID'),
                'field' => 'id',
            ),
            array(
                'data' => $this->t('File name'),
                'fi eld' => 'filename',
            ),
            array(
                'data' => $this->t('File Size'),
                'field' => 'file-size',
            ),
            array(
                'data' => $this->t('Total Size'),
                'field' => 'total-size',
            ),
            array(
                'data' => $this->t('Last Downloaded'),
                'field' => 'last',
            ),
        );
        $connection = Database::getConnection();
        $query = $connection->select('download_count', 'dc')
            ->fields('dc', array( 'type', 'id'))
            ->fields('f', array('filename', 'fid', 'filesize'))
            ->groupBy('dc.type')
            ->groupBy('dc.id')
            ->groupBy('dc.fid')
            ->groupBy('f.filename')
            ->groupBy('f.filesize')
            ->groupBy('f.fid');
        $query->addExpression('COUNT(*)', 'count');
        $query->addExpression('COUNT(*) * f.filesize', 'total-size');
        $query->addExpression('MAX(dc.timestamp)', 'last');
        $query->join('file_managed', 'f', 'dc.fid = f.fid');
        if ($limit > 0) {
            $query->range(0, $limit);
        }
        $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);


        if ($items_per_page > 0) {
            $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($items_per_page);
        }
        $view_all = '';
        if(\Drupal::currentUser()->hasPermission('view all download count')) {
            $view_all = Link::fromTextAndUrl($this->t('View All'), Url::fromRoute('download_count.details', array('download_count_entry' => 'all')))->toString();
            $header[] = array(
                'data' => $view_all,
            );
        }
        $export_all = '';
        if(\Drupal::currentUser()->hasPermission('export all download count')) {
            $export_all =  Link::fromTextAndUrl($this->t('Export All'), Url::fromRoute('download_count.export', array('download_count_entry' => 'all')))->toString();
            $header[] = array(
                'data' => $export_all,
            );

        }
        $reset_all='';
        if(\Drupal::currentUser()->hasPermission('reset all download count')) {
            $reset_all =  Link::fromTextAndUrl($this->t('View All'), Url::fromRoute('download_count.reset', array('download_count_entry' => 'all')))->toString();
            $header[] = array(
                'data' => $reset_all,
            );

        }
        $rows = array();
        $result = $query->execute();
        foreach ($result as $file) {
            $row = array();
            $row[] = $item;
            $row[] = number_format($file->count);
            $row[] = $file->fid;
            $row[] = Html::escape($file->type);
            $row[] = $file->id;
            $row[] = Html::escape($file->filename);
            $row[] = format_size($file->filesize);
            $row[] = format_size($file->count * $file->filesize);
            $row[] = t('@time ago', array('@time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $file->last)));

            $query = $connection->select('download_count', 'dc')
                ->fields('dc', array( 'dcid'))
                ->groupBy('dc.dcid')
            ->condition('id',$file->id)
            ->condition('fid',$file->fid);
            $query->addExpression('MAX(dc.timestamp)', 'last');
            $dcid = $query->execute()->fetchField();
            if($view_all) {
                $row[] = Link::fromTextAndUrl($this->t('Details'), Url::fromRoute('download_count.details', array('download_count_entry' => $dcid)))->toString();
            }
            if ($export_all) {
                $row[] = Link::fromTextAndUrl($this->t('Export'), Url::fromRoute('download_count.export', array('download_count_entry' => $dcid)))->toString();
            }
            if ($reset_all) {
                $row[] = Link::fromTextAndUrl($this->t('Reset'), Url::fromRoute('download_count.reset', array('download_count_entry' => $dcid)))->toString();
            }
            $rows[] = $row;
            $item++;
            $total_downloads += $file->count;
        }
            $build['#attached'] = array(
                'library' => array(
                    "download_count/global-styling-css",
                ),
            );
        if (!empty($page_header['value'])) {
            $output .= '<div id="download-count-header">' . Html::escape($page_header['value'], $page_header['format']) . '</div>';
        }
        $output .= '<div id="download-count-total-top">' . $this->t('Total Downloads:') . ' ' . number_format($total_downloads) . '</div>';
         $table = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array('id' => 'download-count-table'),
             '#empty'=>$this->t('No files have been downloaded.'),
        );

        $output .= render($table);

        $output .= '<div id="download-count-total-bottom">' . $this->t('Total Downloads:') . ' ' . number_format($total_downloads) . '</div>';
        if ($items_per_page > 0) {
            $pager = array(
                '#theme' => 'pager',
                'attributes' => array('tags' => array())
            );
            $output .= render($pager);
        }
        if (!empty($page_footer['value'])) {
            $output .= '<div id="download-count-footer">' . Html::escape($page_footer['value'], $page_footer['format']) . '</div>';
        }
        $output .= '</div>';
        $build['#markup'] = $output;
        return  $build;
    }

    /**
     * Download_count details page callback.
     */
    public function downloadCountDetails($download_count_entry = NULL) {
        $config = \Drupal::config('download_count.settings');
        $build = array();
        $build['#attached'] = array(
            'library' => array(
                "download_count/global-styling-css",
            ),
        );
        $last_cron =  $config->get('download_count_last_cron');

        if($download_count_entry != NULL){
            $connection = Database::getConnection();
            $query = $connection->select('download_count', 'dc');
            $query->innerjoin('file_managed','f','dc.fid = f.fid');
            $query->fields('dc', array('dcid','fid','uid','type','id','ip_address','referrer','timestamp'));
            $query->fields('f', array('filename','uri','filemime','filesize'));
            $query->condition('dc.dcid',$download_count_entry);
            $dc_entry =   $query->execute()->fetchObject();
        }else{
            $dc_entry  = 'all';
        }

        $output = Link::fromTextAndUrl($this->t('&#0171; Back to summary'), Url::fromRoute('download_count.reports'))->toString();
        $connection = Database::getConnection();
        $query = $connection->select('download_count_cache', 'dc');
        $query->addExpression('COUNT(dc.count)', 'count');

        if (!is_object($dc_entry)) {
            $build['#title'] = $this->t('Download Count Details - All Files');
        }
        else {
            $build['#title'] = $this->t('Download Count Details - @filename from @type @id', array('@filename' => $dc_entry->filename, '@type' => $dc_entry->type, '@id' => $dc_entry->id));
            $query->condition('dc.type',$dc_entry->type);
            $query->condition('dc.id', $dc_entry->id);
            $query->condition('dc.fid',$dc_entry->fid);
        }

        $result = $query->execute()->fetchField();
        $total =  number_format($result);

        if ($last_cron > 0) {
            $output .= '<p>Current as of ' . \Drupal::service('date.formatter')->format($last_cron, 'long') . ' with ' . number_format(\Drupal::queue('download_count')->numberOfItems()) . ' items still queued to cache.</p>';
        }
        else {
            $output .= '<p>No download count data has been cached. You may want to check Drupal cron.</p>';
        }

        $output .= '<div id="download-count-total-top"><strong>' . $this->t('Total Downloads:') . '</strong> ' . $total . '</div>';

        // determine first day of week (from date module if set, 'Sunday' if not).
        if ( $config->get('date_first_day') == 0) {
            $week_format = '%U';
        }
        else {
            $week_format = '%u';
        }

        $sparkline_type =  $config->get('download_count_sparklines');
        //base query for all files for all intervals
        $query = $connection->select('download_count_cache','dc')
                  ->groupBy('time_interval');
        $query->addExpression('SUM(dc.count)', 'count');
        $query->orderBy('dc.date', 'DESC');


        // Details for a specific download and entity.
        if ($dc_entry != 'all') {
            $query->condition('type', $dc_entry->type, '=');
            $query->condition('id', $dc_entry->id, '=');
            $query->condition('fid', $dc_entry->fid, '=');
        }

        // daily data
        $query->addExpression("FROM_UNIXTIME(date, '%Y-%m-%d')", 'time_interval');
        $query->range(0,  $config->get('download_count_details_daily_limit'));
        $result = $query->execute();
        $daily =  $this->download_count_details_table($result, 'Daily', 'Day');
        $output .= render($daily['output']);
        if ($sparkline_type != 'none') {
            $values['daily'] = implode(',', array_reverse($daily['values']));
            $output .= '<div class="download-count-sparkline-daily">' . t('Rendering Sparkline...') . '</div>';
        }

        $expressions =& $query->getExpressions();
        // weekly data
        $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '$week_format')";
        $query->range(0,  $config->get('download_count_details_weekly_limit'));
        $result = $query->execute();
        $weekly = $this->download_count_details_table($result, 'Weekly', 'Week');
        $output .= render($weekly['output']);
        if ($sparkline_type != 'none') {
            $values['weekly'] = implode(',', array_reverse($weekly['values']));
            $output .= '<div class="download-count-sparkline-weekly">' . t('Rendering Sparkline...') . '</div>';
        }

        // monthly data
        $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '%Y-%m')";
        $query->range(0,  $config->get('download_count_details_monthly_limit'));
        $result = $query->execute();
        $monthly = $this->download_count_details_table($result, 'Monthly', 'Month');
        $output .= render($monthly['output']);
        if ($sparkline_type != 'none') {
            $values['monthly'] = implode(',', array_reverse($monthly['values']));
            $output .= '<div class="download-count-sparkline-monthly">' . t('Rendering Sparkline...') . '</div>';
        }

        // yearly data
        $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '%Y')";
        $query->range(0,  $config->get('download_count_details_yearly_limit'));
        $result = $query->execute();
        $yearly = $this->download_count_details_table($result, 'Yearly', 'Year');
        $output .= render($yearly['output']);
        if ($sparkline_type != 'none') {
            $values['yearly'] = implode(',', array_reverse($yearly['values']));
            $output .= '<div class="download-count-sparkline-yearly">' . t('Rendering Sparkline...') . '</div>';
        }
        $output .= '<div id="download-count-total-bottom"><strong>' . t('Total Downloads:') . '</strong> ' . $total . '</div>';

        if ($sparkline_type != 'none') {
            $build['#attached']['library'][] = "download_count/sparkline";
            $build['#attached']['drupalSettings']['download_count'] = array(
                'values' => $values,
                'type' => $sparkline_type,
                'min' =>  $config->get('download_count_sparkline_min'),
                'height' =>  $config->get('download_count_sparkline_height'),
                'width' =>  $config->get('download_count_sparkline_width'),
            );

        }
        $build['#markup'] = $output;
        return $build;
    }

    /**
     * Create and output details table.
     */
    public function download_count_details_table($result, $caption, $range) {
        $header = array(
            array(
                'data' => t('#'),
                'class' => 'number',
            ),
            array(
                'data' => t($range),
                'class' => 'range',
            ),
            array(
                'data' => t('Downloads'),
                'class' => 'count',
            ),
        );
        $count = 1;
        $rows = array();
        $values = array();
        foreach ($result as $download) {
            $row = array();
            $row[] = $count;
            $row[] = $download->time_interval;
            $row[] = number_format($download->count);
            $values[] = $download->count;
            $rows[] = $row;
            $count++;
        }
        $output = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array('id' => 'download-count-' . \Drupal\Component\Utility\Unicode::strtolower($caption), 'class' => 'download-count-details download-count-table'),
            '#caption' => $caption,
            '#sticky' => FALSE
        );

        return array('output' => $output, 'values' => $values);
    }

}
