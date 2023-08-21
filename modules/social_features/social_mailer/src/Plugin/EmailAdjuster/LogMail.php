<?php

namespace Drupal\social_mailer\Plugin\EmailAdjuster;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailAdjusterBase;

/**
 * Defines the file spool Mail Transport plugin.
 *
 * @EmailAdjuster(
 *   id = "log_mail",
 *   label = @Translation("Log Email"),
 *   description = @Translation("Saves emails to the file storage."),
 * )
 */
class LogMail extends EmailAdjusterBase {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['spool_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spool directory'),
      '#description' => $this->t('The absolute path to the spool directory.'),
      '#default_value' => $this->configuration['spool_directory'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(EmailInterface $email): void {
    $path = $this->getFilePath($this->configuration['spool_directory']);

    try {
      if (($fp = @fopen($path, 'w+')) !== FALSE) {
        fwrite($fp, serialize($email));
        fclose($fp);
      }
    }
    catch (\Exception $ex) {
      $this->getLogger($ex->getMessage());
    }
  }

  /**
   * Generates a unique file path for the email.
   *
   * @param string $spool_directory
   *   A path in the file system where emails are stored.
   *
   * @return string
   *   A full file path.
   */
  protected function getFilePath(string $spool_directory): string {
    $random = new Random();

    do {
      $path = $spool_directory . DIRECTORY_SEPARATOR . $random->name(10) . '.message';
    } while (file_exists($path));

    return $path;
  }

}
