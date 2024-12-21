<?php

namespace Drupal\social_comment_upload\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialCommentUploadSettingsForm.
 *
 * @package Drupal\social_comment_upload\Form
 */
class SocialCommentUploadSettingsForm extends ConfigFormBase {

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected null|ModuleHandlerInterface|TypedConfigManagerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_comment_upload_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_comment_upload.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Get the configuration file.
    $config = $this->config('social_comment_upload.settings');

    $form['allow_upload_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow file uploads in comments.'),
      '#default_value' => $config->get('allow_upload_comments'),
      '#required' => FALSE,
      '#description' => $this->t("Determine whether users can upload documents to comments."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Get the configuration file.
    $config = $this->config('social_comment_upload.settings');
    $config->set('allow_upload_comments', $form_state->getValue('allow_upload_comments'))->save();

    parent::submitForm($form, $form_state);
  }

}
