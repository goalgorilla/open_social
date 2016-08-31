<?php

namespace Drupal\download_count\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

/**
 * Implements the reset form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\FormStateInterface
 */
class DownloadCountResetForm extends ConfirmFormBase
{
    /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
      return 'download_count_reset_form';
  }

  /**
   * The confirm tag.
   */
  protected $confirm;

  /**
   * The dc entry.
   */
  public $dc_entry;

  /**
   * The question tag.
   */
  protected $question;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $download_count_entry = null)
  {
      if ($download_count_entry != null) {
          $connection = Database::getConnection();
          $query = $connection->select('download_count', 'dc');
          $query->join('file_managed', 'f', 'dc.fid = f.fid');
          $query->fields('dc', array('dcid', 'fid', 'uid', 'type', 'id', 'ip_address', 'referrer', 'timestamp'));
          $query->fields('f', array('filename', 'uri', 'filemime', 'filesize'));
          $query->condition('dc.dcid', $download_count_entry);
          $this->dc_entry = $query->execute()->fetchObject();
      } else {
          $this->dc_entry = 'all';
      }
      if ($dc_entry != 'all') {
          $form['dcid'] = array(
        '#type' => 'value',
        '#value' => $this->dc_entry->dcid,
      );
          $form['filename'] = array(
        '#type' => 'value',
        '#value' => Html::escape($this->dc_entry->filename),
      );
          $form['fid'] = array(
        '#type' => 'value',
        '#value' => $this->dc_entry->fid,
      );
          $form['type'] = array(
        '#type' => 'value',
        '#value' => Html::escape($this->dc_entry->type),
      );
          $form['id'] = array(
        '#type' => 'value',
        '#value' => $this->dc_entry->id,
      );
          $this->confirm = true;
          $this->question = true;

          return parent::buildForm($form, $form_state);
      } else {
          $form['dcid'] = array(
        '#type' => 'value',
        '#value' => 'all',
      );
          $this->confirm = true;
          $this->question = true;

          return parent::buildForm($form, $form_state);
      }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription()
  {
      return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText()
  {
      if ($this->dc_entry != 'all') {
          return $this->t('Reset');
      } else {
          return $this->t('Reset All');
      }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText()
  {
      return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion()
  {
      if ($this->dc_entry != 'all') {
          return $this->t('Are you sure you want to reset the download count for %filename on %entity #%id?', array('%filename' => $this->dc_entry->filename, '%entity' => $this->dc_entry->type, '%id' => $this->dc_entry->id));
      } else {
          return $this->t('Are you sure you want to reset all download counts?');
      }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl()
  {
      return new Url('download_count.reports');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
      $result = null;
      if ($form['dcid']['#value'] == 'all') {
          $result = db_truncate('download_count')->execute();
          if ($result) {
              db_truncate('download_count_cache')->execute();
              drupal_set_message(t('All download counts have been reset.'));
              \Drupal::logger('download_count')->notice('All download counts have been reset.');
          } else {
              drupal_set_message(t('Unable to reset all download counts.'), 'error');
              \Drupal::logger('download_count')->error('Unable to reset all download counts.');
          }
      } else {
          $result = db_delete('download_count')
          ->condition('fid', $form['fid']['#value'])
          ->condition('type', $form['type']['#value'])
          ->condition('id', $form['id']['#value'])
          ->execute();
          if ($result) {
              db_delete('download_count_cache')
            ->condition('fid', $form['fid']['#value'])
            ->condition('type', $form['type']['#value'])
            ->condition('id', $form['id']['#value'])
            ->execute();
              drupal_set_message(t('Download count for %filename on %type %id was reset.', array('%filename' => $form['filename']['#value'], '%type' => $form['type']['#value'], '%id' => $form['id']['#value'])));
              \Drupal::logger('download_count')->notice('Download count for %filename on %type %id was reset.', array('%filename' => $form['filename']['#value'], '%type' => $form['type']['#value'], '%id' => $form['id']['#value']));
          } else {
              drupal_set_message(t('Unable to reset download count for %filename on %type %id.', array('%filename' => $form['filename']['#value'], '%type' => $form['type']['#value'], '%id' => $form['id']['#value'])), 'error');
              \Drupal::logger('download_count')->error('Unable to reset download count for %filename on %type %id.', array('%filename' => $form['filename']['#value'], '%type' => $form['type']['#value'], '%id' => $form['id']['#value']));
          }
      }
      $form_state->setRedirect('download_count.reports');
  }
}
