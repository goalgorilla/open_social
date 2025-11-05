<?php

namespace Drupal\social_pwa\Plugin\Push;

use Drupal\activity_send_push_notification\PushBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_pwa\BrowserDetector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a concrete class for PWA Push.
 *
 * @Push(
 *   id = "pwa_push",
 *   title = @Translation("Current device"),
 *   description = @Translation("Show notifications in the corner of your computer screen, even if the website is closed."),
 * )
 */
class PwaPush extends PushBase {

  /**
   * TRUE if target and current user is the same.
   *
   * @var bool
   */
  protected $isValidUser;

  /**
   * Constructs a PwaPush object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param int $current_user_id
   *   The current active user ID.
   * @param int $route_user_id
   *   The user ID from route parameters.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $string_translation,
    ConfigFactoryInterface $config_factory,
    $current_user_id,
    $route_user_id
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $string_translation,
      $config_factory,
      $current_user_id
    );

    $this->isValidUser = $route_user_id === $current_user_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('current_user')->id(),
      $container->get('current_route_match')->getRawParameter('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $config = $this->configFactory->get('social_pwa.settings');

    if (!$push_enabled = $config->get('status.all')) {
      return FALSE;
    }

    // Hide the Push notifications fieldset if target and current user is not
    // the same.
    if (!$this->isValidUser) {
      return FALSE;
    }

    // Get the uploaded icon.
    $icon = $config->get('icons.icon');

    if ($icon === NULL || !isset($icon[0])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = parent::buildForm();

    // Get the device and subscription information about this user.
    $useragent = $_SERVER['HTTP_USER_AGENT'];

    // Browser detector.
    $bd = new BrowserDetector($useragent);

    // The device type for the icon.
    $device_type = $bd->getDeviceType();

    // The device/browser description.
    $device_description = $bd->getFormattedDescription();

    // Prepare the toggle element. We determine default value in the frontend.
    $form['toggle'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="control-label__icon--bg icon-' . $device_type . '"></span>' . $device_description,
      '#disabled' => FALSE,
      '#default_value' => FALSE,
      '#attributes' => [
        'data-switch' => TRUE,
      ],
    ];

    // Get the help text for enabling push notifications per (major) browser.
    switch ($bd->getBrowserName()) {
      case 'Chrome':
        $url = 'https://support.google.com/chrome/answer/3220216?co=GENIE.Platform%3D';

        $operating_systems = ['Android', 'iOS'];
        $operating_system = $bd->getOsName();

        if (in_array($operating_system, $operating_systems)) {
          $url .= $operating_system;
        }
        else {
          $url .= 'Desktop';
        }

        $url .= '&oco=1';
        break;

      case 'Chrome Mobile':
        $url = 'https://support.google.com/chrome/answer/3220216?co=GENIE.Platform%3DAndroid&oco=1';
        break;

      case 'Chrome Mobile iOS':
        $url = 'https://support.google.com/chrome/answer/3220216?co=GENIE.Platform%3DiOS&oco=1';
        break;

      case 'Firefox':
      case 'Firefox Focus':
      case 'Firefox Mobile':
        $url = 'https://support.mozilla.org/en-US/kb/push-notifications-firefox';
        break;

      case 'Microsoft Edge':
        $url = 'https://blogs.windows.com/msedgedev/2016/05/16/web-notifications-microsoft-edge';
        break;

      case 'Opera':
      case 'Opera Mini':
      case 'Opera Mobile':
      case 'Opera Next':
        $url = 'http://help.opera.com/opera/Windows/1656/en/controlPages.html#manageNotifications';
        break;
    }

    // Prepare the text. If we have an url we can point the user in the right
    // direction.
    if (isset($url)) {
      $text = $this->t('You have denied receiving push notifications through your browser. Please <a href="@url" target="_blank">reset your browser setting</a> for receiving notifications.', [
        '@url' => $url,
      ]);
    }
    else {
      $text = $this->t('You have denied receiving push notifications through your browser. Please reset your browser setting for receiving notifications.');
    }

    // Prepare the blocked notice element with the help text.
    $form['blocked_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['help-block', 'blocked-notice', 'hide'],
      ],
      '#value' => $text,
    ];

    return $form;
  }

}
