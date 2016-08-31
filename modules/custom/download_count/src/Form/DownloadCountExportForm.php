<?php

namespace Drupal\download_count\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Database;

/**
 * Implements the Export form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class DownloadCountExportForm extends FormBase
{
    /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
      return 'download_count_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $download_count_entry = null)
  {
      if ($download_count_entry != null) {
          $connection = Database::getConnection();
          $query = $connection->select('download_count', 'dc');
          $query->join('file_managed', 'f', 'dc.fid = f.fid');
          $query->fields('dc', array(
        'dcid',
        'fid',
        'uid',
        'type',
        'id',
        'ip_address',
        'referrer',
        'timestamp',
      ));
          $query->fields('f', array('filename', 'uri', 'filemime', 'filesize'));
          $query->condition('dc.dcid', $download_count_entry);
          $dc_entry = $query->execute()->fetchObject();
      } else {
          $dc_entry = 'all';
      }
      $config = $this->config('download_count.settings');
      $form['#attached']['library'][] = 'download_count/export-form-styling';
      if ($dc_entry == 'all') {
          $form['#title'] = $this->t('Download Count Export CSV - All Files');
      } else {
          $form['#title'] = $this->t("Download Count Export CSV - '@filename' from '@type' '@id'", array(
        '@filename' => $dc_entry->filename,
        '@type' => $dc_entry->type,
        '@id' => $dc_entry->id,
      ));
      }

      $form['download_count_export_note'] = array(
      '#prefix' => '<div id="download-count-export-note">',
      '#suffix' => '</div>',
      '#markup' => Link::fromTextAndUrl($this->t('Back to summary'), Url::fromRoute('download_count.reports', array('html' => true)))
        ->toString()
      .'<br /><br />'
      .$this->t('The following data will be exported:')
      .'<ul>'
      .'<li>'.$this->t('Download count id')
      .'<li>'.$this->t('File id')
      .'<li>'.$this->t('File name')
      .'<li>'.$this->t('File size')
      .'<li>'.$this->t('Entity type')
      .'<li>'.$this->t('Entity id')
      .'<li>'.$this->t('Downloading user id')
      .'<li>'.$this->t('Downloading username')
      .'<li>'.$this->t('Downloading user ip address')
      .'<li>'.$this->t('HTTP referrer')
      .'<li>'.$this->t('Date - time (YYYY-MM-DD  HH:MM:SS)')
      .'</ul>',
    );
      $form['download_count_export_range'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Export Range'),
      '#options' => array(
        $this->t('export all data'),
        $this->t('export data for a specified date range'),
      ),
      '#default_value' => $config->get('download_count_export_range') ? 1 : 0,
    );
      $form['download_count_export_date_range_from'] = array(
      '#type' => 'date',
      '#title' => $this->t('Export Range From Date'),
      '#description' => $this->t('This field will be ignored if the Export Range \'export all data\' option is selected above.'),
    );
      $form['download_count_export_date_range_to'] = array(
      '#type' => 'date',
      '#title' => $this->t('Export Range To Date'),
      '#description' => $this->t('This field will be ignored if the Export Range \'export all data\' option is selected above.'),
    );
      $form['download_count_file_info'] = array(
      '#type' => 'value',
      '#value' => $dc_entry,
    );
      $form['download_count_export_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    );
      $form['download_count_export_cancel'] = array(
      '#value' => '<a href="javascript:history.back(-1)">'.$this->t('Cancel').'</a>',
    );

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
      \Drupal::service('config.factory')
      ->getEditable('download_count.settings')
      ->set('download_count_export_range', $form_state->getValue('download_count_export_range'))
      ->save();
      $filename = 'download_count_export_'.($form_state->getValue('download_count_file_info')->filename).'_'.date('Y-m-d').'.csv';
      $range = $form_state->getValue('download_count_export_range');
      $start = $end = '';
      if ($range > 0) {
          $start = $form_state->getValue('download_count_export_date_range_from');
          $end = $form_state->getValue('download_count_export_date_range_to');
          $file = $form_state->getValue('download_count_file_info')->filename;
          $filename = 'download_count_export_'.$file.'_FROM-'.$start.'-TO-'.$end.'.csv';
      }
      $file_info = $form_state->getValue('download_count_file_info');
      $result = $this->downloadCountExportData($filename, $range, $file_info, $start, $end);
      drupal_set_message($filename.' has been successfully exported.', 'status');

      return;
  }

  /**
   *
   */
  public function downloadCountExportData($filename, $range, $file_info, $start, $end)
  {
      $response = new Response();
      $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
      $response->headers->set('Content-Type', 'application/csv');
      $response->sendHeaders();

      $connection = Database::getConnection();
      $query = $connection->select('download_count', 'dc');
      $query->join('file_managed', 'f', 'dc.fid = f.fid');
      $query->join('users_field_data', 'u', 'dc.uid = u.uid');
      $query->fields('dc', array(
      'dcid',
      'fid',
      'type',
      'id',
      'uid',
      'ip_address',
      'referrer',
      'timestamp',
    ));
      $query->fields('f', array('filename', 'filesize', 'uri'));
      $query->fields('u', array('name'));

      if ($file_info != 'all') {
          $query->condition('dc.type', $file_info->type, '=');
          $query->condition('dc.id', $file_info->id, '=');
          $query->condition('dc.fid', $file_info->fid, '=');
      }
      if ($range > 0) {
          $from = mktime(0, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)));
          $to = mktime(23, 59, 59, date('m', strtotime($end)), date('d', strtotime($end)), date('Y', strtotime($end)));

          if ($from == $to) {
              $to += 86400;
          }
          $query->condition('dc.timestamp', array($from, $to), 'BETWEEN');
      }
      $query->orderBy('dc.timestamp', 'DESC');
      $result = $query->execute();
      $column_names = '"Download count id","File id","File name","File URI","File size","Entity type","Entity id","Downloading user id","Downloading username","Downloading user ip address","HTTP referrer","Date time"'."\n";
      echo $column_names;
      foreach ($result as $record) {
          $row = '"'.$record->dcid.'"'.',';
          $row .= '"'.$record->fid.'"'.',';
          $row .= '"'.$record->filename.'"'.',';
          $row .= '"'.$record->uri.'"'.',';
          $row .= '"'.$record->filesize.'"'.',';
          $row .= '"'.$record->type.'"'.',';
          $row .= '"'.$record->id.'"'.',';
          $row .= '"'.$record->uid.'"'.',';
          $row .= '"'.$record->name.'"'.',';
          $row .= '"'.$record->ip_address.'"'.',';
          $row .= '"'.$record->referrer.'"'.',';
          $row .= '"'.date('Y-m-d H:i:s', $record->timestamp).'"';
          $row .= "\n";
          echo $row;
      }
      exit;
  }
}
