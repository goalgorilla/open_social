<?php

namespace Drupal\download_count\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Remove form for book module.
 */
class DownloadCountClearForm extends ConfirmFormBase
{
    /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
      return 'download_count_clear_form';
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
      return $this->t('Clear Cache');
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
      return $this->t('Are you sure you want to clear the download count cache?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl()
  {
      return new Url('download_count.file_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
      \Drupal::service('config.factory')->getEditable('download_count.settings')->set('download_count_last_cron', 0)->save();
      \Drupal::database()->truncate('download_count_cache')->execute();
      drupal_set_message($this->t('The download count cache has been cleared.'));
      \Drupal::logger('download_count')->notice($this->t('The download count cache has been cleared.'));
      $form_state->setRedirect('download_count.file_settings');
  }
}
