<?php

namespace Drupal\social_mailer\Form;

use Drupal\activity_creator\Plugin\ActivityDestinationManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;

/**
 * Configuration form for the module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The 'email' activity destination plugin.
   */
  protected EmailActivityDestination $emailActivityDestination;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The batch builder.
   */
  protected BatchBuilder $batchBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.activity_destination.processor'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\activity_creator\Plugin\ActivityDestinationManager $activity_destination_manager
   *   The activity destination manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ActivityDestinationManager $activity_destination_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    if (($plugin = $activity_destination_manager->createInstance('email')) instanceof EmailActivityDestination) {
      $this->emailActivityDestination = $plugin;
    }

    $this->batchBuilder = new BatchBuilder();
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'social_mailer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_mailer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('social_mailer.settings');

    $form['notification'] = [
      '#type' => 'details',
      '#title' => $this->t('Default email notification settings'),
      '#open' => FALSE,
    ];

    // Settings helper for admins.
    $form['notification']['helper'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('<b>Please note:</b> the change below will not impact the users who have changed their default email notification settings.'),
    ];

    // Get grouped default templates.
    $items = _activity_send_email_default_template_items();

    // Get all message templates.
    $email_message_templates = $this->emailActivityDestination->getSendEmailMessageTemplates();

    // Alter message templates and add them to specific group.
    $this->moduleHandler->alter('activity_send_email_notifications', $items, $email_message_templates);

    // Sort a list of email frequencies by weight.
    $email_frequencies = sort_email_frequency_options();

    $notification_options = [];
    // Place the sorted data in an actual form option.
    foreach ($email_frequencies as $option) {
      $notification_options[$option['id']] = $option['name'];
    }

    $template_frequencies = $config->get('template_frequencies') ?: [];

    foreach ($items as $item_id => $item) {
      $rows = [];

      foreach ($item['templates'] as $template) {
        $rows[] = $this->buildRow($template, $notification_options, $template_frequencies);
      }

      $form['notification'][$item_id] = [
        '#type' => 'table',
        '#caption' => [
          '#markup' => '<h6>' . $item['title'] . '</h6>',
        ],
        '#rows' => $rows,
      ];
    }

    $form['template'] = [
      '#type' => 'details',
      '#title' => $this->t('Template configuration'),
      '#open' => FALSE,
    ];

    $template_header = $config->get('template_header');
    $form['template']['template_header'] = [
      '#title' => $this->t('Template header'),
      '#type' => 'text_format',
      '#default_value' => $template_header['value'] ?? '',
      '#format' => $template_header['format'] ?? 'mail_html',
      '#allowed_formats' => [
        'mail_html',
      ],
      '#description' => $this->t('Enter information you want to show in the email notifications header'),
    ];

    $template_footer = $config->get('template_footer');
    $form['template']['template_footer'] = [
      '#title' => $this->t('Template footer'),
      '#type' => 'text_format',
      '#default_value' => $template_footer['value'] ?? '',
      '#format' => $template_footer['format'] ?? 'mail_html',
      '#allowed_formats' => [
        'mail_html',
      ],
      '#description' => $this->t('Enter information you want to show in the email notifications footer'),
    ];

    $form['remove_open_social_branding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Open Social Branding'),
      '#description' => $this->t('Open Social Branding will be replaced by site name (and slogan if available).'),
      '#default_value' => $config->get('remove_open_social_branding'),
    ];

    $form['do_not_send_emails_new_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Don't send email notifications to users who have never logged in"),
      '#description' => $this->t('When this setting is enabled, users who have never logged in will not receive email notifications, they still receive notifications via the notification centre within the community.'),
      '#default_value' => $config->get('do_not_send_emails_new_users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $config = $this->config('social_mailer.settings');
    $config
      ->set('remove_open_social_branding', $form_state->getValue('remove_open_social_branding'))
      ->set('do_not_send_emails_new_users', $form_state->getValue('do_not_send_emails_new_users'))
      ->set('template_header', $form_state->getValue('template_header'))
      ->set('template_footer', $form_state->getValue('template_footer'));

    $templates = $this->emailActivityDestination->getSendEmailMessageTemplates();
    $user_input = $form_state->getUserInput();

    foreach (array_keys($templates) as $template) {
      if (isset($user_input[$template])) {
        $config->set('template_frequencies.' . $template, $user_input[$template]);
      }
    }

    $config->save();
  }

  /**
   * Returns row for table.
   *
   * @param string $template
   *   Template ID.
   * @param array $notification_options
   *   Array of options.
   * @param array $template_frequencies
   *   Frequencies for all templates from config.
   */
  private function buildRow($template, array $notification_options, array $template_frequencies): array {
    $email_message_templates = $this->emailActivityDestination->getSendEmailMessageTemplates();
    $row = [
      [
        'width' => '50%',
        'data' => [
          '#plain_text' => $email_message_templates[$template],
        ],
      ],
    ];

    $default_value = $template_frequencies[$template] ?? 'immediately';

    foreach ($notification_options as $notification_id => $notification_option) {
      $parents_for_id = [$template, $notification_id];
      $row[] = [
        'data' => [
          '#type' => 'radio',
          '#title' => $notification_option,
          '#return_value' => $notification_id,
          '#value' => $default_value === $notification_id ? $notification_id : FALSE,
          '#name' => $template,
          '#id' => Html::getUniqueId('edit-' . implode('-', $parents_for_id)),
        ],
      ];
    }

    return $row;
  }

}
