<?php

namespace Drupal\download_count\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Recent Download Count' block.
 *
 * @Block(
 *   id = "recent_download",
 *   admin_label = @Translation("Recently Downloaded Files")
 * )
 */
class RecentDownload extends BlockBase
{
    /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account)
  {
      return AccessResult::allowedIfHasPermission($account, 'access recent download');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
      $config = $this->getConfiguration();
      $limit = isset($config['download_count_recent_block_limit']) ? $config['download_count_recent_block_limit'] : 10;
      $rows = array();
      $connection = Database::getConnection();

      $sql = $connection->select('download_count', 'dc');
      $sql->join('file_managed', 'f', 'f.fid = dc.fid');
      $sql->addExpression('MAX(dc.timestamp)', 'date');
      $sql->fields('dc', array('fid'));
      $sql->fields('f', array('filename', 'filesize'));
      $sql->groupBy('dc.fid');
      $sql->groupBy('f.filename');
      $sql->groupBy('f.filesize');
      $sql->orderBy('date', 'DESC');
      $header = array(
        array(
          'data' => $this->t('Name'),
          'class' => 'filename',
        ),
        array(
          'data' => $this->t('Size'),
          'class' => 'size',
        ),
        array(
          'data' => $this->t('Last Downloaded'),
          'class' => 'last',
        ),
    );

      $result = $connection->queryRange($sql, 0, $limit);
      foreach ($result as $file) {
          $row = array();
          $row[] = Html::escape($file->filename);
          $row[] = format_size($file->filesize);
          $row[] = $this->t('%time ago', array('%time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $file->date)));
          $rows[] = $row;
      }

      if (count($rows)) {
          return array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      );
      }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
      $form = parent::blockForm($form, $form_state);

      $config = $this->getConfiguration();

      $form['download_count_recent_block_limit'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of items to display'),
      '#default_value' => isset($config['download_count_recent_block_limit']) ? $config['download_count_recent_block_limit'] : 10,
    );

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
      // $this->setConfigurationValue('download_count_recent_block_limit', $form_state->getValue('download_count_recent_block_limit');.
    $this->configuration['download_count_recent_block_limit'] = $form_state->getValue('download_count_recent_block_limit');
  }
}
