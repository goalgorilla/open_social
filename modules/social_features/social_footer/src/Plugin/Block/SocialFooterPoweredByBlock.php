<?php

namespace Drupal\social_footer\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileStorageInterface;
use Drupal\system\Plugin\Block\SystemPoweredByBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Powered by' block.
 *
 * @Block(
 *   id = "social_footer_powered_by_block",
 *   admin_label = @Translation("Powered by")
 * )
 */
class SocialFooterPoweredByBlock extends SystemPoweredByBlock implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $storage;

  /**
   * Creates a SocialFooterPoweredByBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\file\FileStorageInterface $storage
   *   The file storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    FileStorageInterface $storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'logo' => '',
      'text' => [
        'value' => '',
        'format' => 'basic_html',
      ],
      'link' => [
        'url' => '',
        'title' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $default_scheme = $this->configFactory->get('system.file')
      ->get('default_scheme');

    $form['logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Logo'),
      '#upload_location' => $default_scheme . '://',
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
      '#default_value' => $config['logo'] ? [$config['logo']] : NULL,
    ];

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text'),
      '#default_value' => $config['text']['value'],
      '#format' => $config['text']['format'],
    ];

    $form['link'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Link'),
    ];

    $form['link']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $config['link']['url'],
    ];

    $form['link']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['link']['title'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $logo = '';

    if ($items = $form_state->getValue('logo')) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->storage->load($logo = $items[0]);

      $file->setPermanent();
      $file->save();
    }

    $this->configuration['logo'] = $logo;
    $this->configuration['text'] = $form_state->getValue('text');
    $this->configuration['link'] = $form_state->getValue('link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['content'],
        'block' => 'block-socialblue-footer-powered',
      ],
    ];

    if ($this->configuration['logo']) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->storage->load($this->configuration['logo']);

      $build['logo'] = [
        '#theme' => 'image',
        '#uri' => $file->getFileUri(),
      ];
    }
    elseif ($this->configFactory->get('system.theme')
      ->get('default') === 'socialblue') {
      // Add default image.
      // Only when socialblue is default we continue.
      $file_path = drupal_get_path('module', 'social_footer') . DIRECTORY_SEPARATOR . 'open_social_logo.png';
      $file_system = \Drupal::service('file_system');
      $uri = $file_system->copy($file_path, 'public://open_social_logo.png', FileSystemInterface::EXISTS_REPLACE);

      // Create a file media.
      /** @var \Drupal\file\FileInterface $file */
      $media = File::create([
        'uri' => $uri,
      ]);
      $media->setPermanent();
      $media->save();
      $build['logo'] = [
        '#theme' => 'image',
        '#uri' => $media->getFileUri(),
      ];
    }

    $build['text'] = [
      '#type' => 'processed_text',
      '#text' => $this->configuration['text']['value'],
      '#format' => $this->configuration['text']['format'],
      '#prefix' => '<div class="footer-block--body">',
      '#suffix' => '</div>',
    ];

    if ($this->configuration['link']['url']) {
      $options = [
        'attributes' => $build['#attributes'] + [
          'target' => '_blank',
        ],
      ];

      if ($this->configuration['link']['title']) {
        $options['attributes']['title'] = $this->configuration['link']['title'];
      }

      $build = [
        '#type' => 'link',
        '#title' => isset($build['logo']) ? [
          'logo' => $build['logo'],
          'text' => $build['text'],
        ] : $build['text'],
        '#url' => Url::fromUri($this->configuration['link']['url'], $options),
      ];
    }

    return [
      '#attached' => [
        'library' => ['social_footer/block'],
      ],
      'content' => $build,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf(
      !empty($this->configuration['text']['value']) ||
      !empty($this->configuration['logo'])
    );
  }

}
