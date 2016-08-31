<?php

namespace Drupal\download_count\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Top Downloaded Files' block.
 *
 * @Block(
 *   id = "top_download",
 *   admin_label = @Translation("Top Downloaded Files")
 * )
 */
class TopDownload extends BlockBase
{
    /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account)
  {
      return AccessResult::allowedIfHasPermission($account, 'access top download');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
      $config = $this->getConfiguration();
      $limit = isset($config['download_count_top_block_limit']) ? $config['download_count_top_block_limit'] : 10;
      $rows = array();
      $connection = Database::getConnection();
      $sql = $connection->select('download_count_cache', 'dc');
      $sql->join('file_managed', 'f', 'f.fid = dc.fid');
      $sql->fields('dc', array('fid', 'count'));
      $sql->fields('f', array('filename', 'filesize'));
      $sql->orderBy('dc.count', 'DESC');
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
          $row[] = $file->count;
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

      $form['download_count_top_block_limit'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of items to display'),
      '#default_value' => isset($config['download_count_top_block_limit']) ? $config['download_count_top_block_limit'] : 10,
    );

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
      // $this->setConfigurationValue('download_count_top_block_limit', $form_state->getValue('download_count_top_block_limit');.
    $this->configuration['download_count_top_block_limit'] = $form_state->getValue('download_count_top_block_limit');
  }
}
