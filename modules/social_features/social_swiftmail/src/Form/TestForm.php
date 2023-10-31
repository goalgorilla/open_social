<?php

namespace Drupal\social_swiftmail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Social Mailer test form.
 */
class TestForm extends FormBase {

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The config factory.
   *
   * Defined as var because of the base class in form base class.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a TestForm object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_swiftmail_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;

    $form['description'] = [
      '#markup' => '<p>' . $this->t('This page allows you to send a test e-mail to a recipient of your choice.') . '</p>',
    ];

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recipient'),
      '#description' => '<p>' . $this->t('You can send a test e-mail to a recipient of your choice. The e-mail will be sent using the default values as provided by the Social Mailer module or as configured by you.') . '</p>',
    ];

    $form['test']['recipient'] = [
      '#title' => $this->t('E-mail'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->currentUser()->getEmail(),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $params = [
      'site_name' => $this->configFactory->get('system.site')->get('name'),
    ];

    $this->mailManager->mail(
      'social_swiftmail',
      'test',
      $form_state->getValue(['test', 'recipient']),
      $this->languageManager->getDefaultLanguage()->getId(),
      $params
    );
    $this->messenger()->addMessage($this->t('An attempt has been made to send an e-mail to @email.', [
      '@email' => $form_state->getValue(['test', 'recipient']),
    ]));
  }

}
