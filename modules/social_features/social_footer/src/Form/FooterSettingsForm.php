<?php

namespace Drupal\social_footer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form for configuring footer block.
 */
class FooterSettingsForm extends FormBase {

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Creates a FooterSettingsForm instance.
   *
   * @param \Drupal\file\FileStorageInterface $file_storage
   *   The file storage.
   */
  public function __construct(FileStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'social_footer_config_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $block = self::configFactory()->get('block.block.socialblue_footer_powered');
    if ($block) {
      $settings = $block->get('settings');
    }

    $default_scheme = self::config('system.file')->get('default_scheme');

    $form['logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Logo'),
      '#upload_location' => $default_scheme . '://',
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
      '#default_value' => [$settings['logo']] ?? NULL,
    ];

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text'),
      '#default_value' => $settings['text']['value'] ?? NULL,
      '#format' => $settings['text']['format'] ?? 'basic_html',
    ];

    $form['link'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Link') ?? NULL,
    ];

    $form['link']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $settings['link']['url'] ?? NULL,
    ];

    $form['link']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $settings['link']['title'] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $logo = '';
    $values = $form_state->getValues();

    if ($values['logo'] = $form_state->getValue('logo')) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->fileStorage->load($logo = $values['logo'][0]);

      $file->setPermanent();
      $file->save();
    }
    $block = self::configFactory()->getEditable('block.block.socialblue_footer_powered');
    if ($block) {
      $settings = $block->get('settings');
      $settings['logo'] = $logo;
      $settings['text'] = $values['text'];
      $settings['link']['url'] = $values['url'];
      $settings['link']['title'] = $values['title'];
      $block->set('settings', $settings)->save();
    }
    $this->messenger()->addStatus(t('Your footer settings have been updated'));
  }

}
