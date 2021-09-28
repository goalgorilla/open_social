<?php

namespace Drupal\social_footer\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileStorageInterface;
use Drupal\file\FileUsage\FileUsageInterface;
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
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Creates a FooterSettingsForm instance.
   *
   * @param \Drupal\file\FileStorageInterface $file_storage
   *   The file storage.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(FileStorageInterface $file_storage, EntityRepositoryInterface $entity_repository, FileUsageInterface $file_usage) {
    $this->fileStorage = $file_storage;
    $this->entityRepository = $entity_repository;
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('entity.repository'),
      $container->get('file.usage')
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
    $block = $this->configFactory()->get('block.block.socialblue_footer_powered');
    if (!empty($block)) {
      $settings = $block->get('settings');
    }

    $default_scheme = $this->config('system.file')->get('default_scheme');

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

    // Set all images used within the ckeditor to have permanent status.
    $text = $form_state->getValue('text')['value'];
    $this->setInlineImagesAsPermanent($text);

    $block = $this->configFactory()->getEditable('block.block.socialblue_footer_powered');
    if (!empty($block)) {
      $settings = $block->get('settings');
      $settings['logo'] = $logo;
      $settings['text'] = $values['text'];
      $settings['link']['url'] = $values['url'];
      $settings['link']['title'] = $values['title'];
      $block->set('settings', $settings)->save();
    }

    $this->messenger()->addStatus($this->t('Your footer settings have been updated'));
  }

  /**
   * Set the inline images status to permanent.
   *
   * @param string $text
   *   Text editor value.
   */
  public function setInlineImagesAsPermanent($text) {
    $uuids = _editor_parse_file_uuids($text);
    foreach ($uuids as $uuid) {
      $file = $this->entityRepository->loadEntityByUuid('file', $uuid);

      /** @var \Drupal\file\FileInterface $file */
      if (empty($file) || !$file->isTemporary()) {
        continue;
      }

      $file->setPermanent();
      $file->save();
      $this->fileUsage->add($file, 'social_footer', 'file', $file->id());
    }
  }

}
