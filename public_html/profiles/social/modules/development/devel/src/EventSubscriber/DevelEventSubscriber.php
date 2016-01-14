<?php

/**
 * @file
 * Contains \Drupal\devel\EventSubscriber\DevelEventSubscriber.
 */

namespace Drupal\devel\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class DevelEventSubscriber implements EventSubscriberInterface {

  /**
   * The devel.settings config object.
   *
   * @var \Drupal\Core\Config\Config;
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a DevelEventSubscriber object.
   */
  public function __construct(ConfigFactoryInterface $config, AccountInterface $account, ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator) {
    $this->config = $config->get('devel.settings');
    $this->account = $account;
    $this->moduleHandler = $module_handler;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Initializes devel module requirements.
   */
  public function onRequest(GetResponseEvent $event) {

    if ($this->account->hasPermission('access devel information')) {
      devel_set_handler(devel_get_handlers());

      // See http://www.firephp.org/HQ/Install.htm
      $path = NULL;
      if ((@include_once 'fb.php') || (@include_once 'FirePHPCore/fb.php')) {
        // FirePHPCore is in include_path. Probably a PEAR installation.
        $path = '';
      }
      elseif ($this->moduleHandler->moduleExists('libraries')) {
        // Support Libraries API - http://drupal.org/project/libraries
        $firephp_path = libraries_get_path('FirePHPCore');
        $firephp_path = ($firephp_path ? $firephp_path . '/lib/FirePHPCore/' : '');
        $chromephp_path = libraries_get_path('chromephp');
      }
      else {
        $firephp_path = DRUPAL_ROOT . '/libraries/FirePHPCore/lib/FirePHPCore/';
        $chromephp_path = './' . drupal_get_path('module', 'devel') . '/chromephp';
      }

      // Include FirePHP if it exists.
      if (!empty($firephp_path) && file_exists($firephp_path . 'fb.php')) {
        include_once $firephp_path . 'fb.php';
        include_once $firephp_path . 'FirePHP.class.php';
      }

      // Include ChromePHP if it exists.
      if (!empty($chromephp_path) && file_exists($chromephp_path .= '/ChromePhp.php')) {
        include_once $chromephp_path;
      }

    }


    if ($this->config->get('rebuild_theme')) {
      drupal_theme_rebuild();

      // Ensure that the active theme object is cleared.
      $theme_name = \Drupal::theme()->getActiveTheme()->getName();
      \Drupal::state()->delete('theme.active_theme.' . $theme_name);
      \Drupal::theme()->resetActiveTheme();

      /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler*/
      $theme_handler = \Drupal::service('theme_handler');
      $theme_handler->refreshInfo();
      // @todo This is not needed after https://www.drupal.org/node/2330755
      $list = $theme_handler->listInfo();
      $theme_handler->addTheme($list[$theme_name]);

      if (\Drupal::service('flood')->isAllowed('devel.rebuild_theme_warning', 1)) {
        \Drupal::service('flood')->register('devel.rebuild_theme_warning');
        if ($this->account->hasPermission('access devel information')) {
          drupal_set_message(t('The theme information is being rebuilt on every request. Remember to <a href=":url">turn off</a> this feature on production websites.', array(':url' => $this->urlGenerator->generateFromRoute('devel.admin_settings'))));
        }
      }
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Set a low value to start as early as possible.
    $events[KernelEvents::REQUEST][] = array('onRequest', -100);

    return $events;
  }

}
