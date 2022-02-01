<?php

namespace Drupal\social_mailer\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Listens events of outgoing emails.
 */
class MessageEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a MessageEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'Symfony\\Component\\Mailer\\Event\\MessageEvent' => 'onMessage',
    ];
  }

  /**
   * Saves outgoing emails to a spool directory.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The event instance.
   */
  public function onMessage(MessageEvent $event): void {
    $message = $event->getMessage();

    if (!$message instanceof Email) {
      return;
    }

    $headers = $message->getHeaders();
    $transport = $headers->get('X-Transport');

    if (!$transport || $transport->getBody() != 'FileSpool') {
      return;
    }

    $spool_directory = $headers->getHeaderBody('X-Spool-Directory');
    $headers->remove('X-Transport');
    $headers->remove('X-Spool-Directory');

    $path = $this->getFilePath($spool_directory);

    if (($fp = @fopen($path, 'w+')) !== FALSE) {
      fwrite($fp, serialize($message));
      fclose($fp);
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
