<?php

namespace Drupal\social_mailer\Plugin\MailerTransport;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\symfony_mailer\Plugin\MailerTransport\TransportBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Defines the file spool Mail Transport plugin.
 *
 * @MailerTransport(
 *   id = "file_spool",
 *   label = @Translation("File Spool"),
 *   description = @Translation("Saves emails to the file storage."),
 * )
 */
class FileSpool extends TransportBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * Constructs a FileSpool object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'spool_directory' => 'temporary://symfony-mailer-spool',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $directory = $form_state->getValue('spool_directory');

    if ($this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $form_state->setValue('spool_directory', $directory);
    }
    else {
      $form_state->setErrorByName('spool_directory', $this->t('Cannot create a spool directory.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['spool_directory'] = $form_state->getValue('spool_directory');
  }

  /**
   * {@inheritdoc}
   */
  public function getDsn(): string {
    return 'null://file-spool:/' . $this->configuration['spool_directory'];
  }

}
