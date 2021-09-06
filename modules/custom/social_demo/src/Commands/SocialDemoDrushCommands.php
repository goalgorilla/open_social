<?php

namespace Drupal\social_demo\Commands;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_demo\DemoContentManager;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Drush\Utils\StringUtils;

/**
 * A Drush command file.
 *
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a
 * drush.services.yml in root of your module like this module does.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class SocialDemoDrushCommands extends DrushCommands {

  /**
   * The current account service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * Demo content plugin manager.
   *
   * @var \Drupal\social_demo\DemoContentManager
   */
  private DemoContentManager $demoContentManager;

  /**
   * Constructs a new UpdateVideosStatsController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Current user service.
   * @param \Drupal\social_demo\DemoContentManager $demoContentManager
   *   Demo content plugin manager.
   */
  public function __construct(
    AccountProxyInterface $accountProxy,
    DemoContentManager $demoContentManager
  ) {
    $this->currentUser = $accountProxy;
    $this->demoContentManager = $demoContentManager;
    $this->loadDrushStub();
    parent::__construct();
  }

  /**
   * Create demo content.
   *
   * @param $content_types
   *   Content types for content creation.
   *
   * @command social-demo:add
   * @aliases sda
   * @option profile
   *   Profile to install.
   * @usage social-demo:add foo
   *   foo is the type of content type
   *
   * @bootstrap root
   */
  public function addDemoContent(array $content_types, $options = ['profile' => '']) {
    $content_types = StringUtils::csvToArray($content_types);
    $this->convertContentTypes($content_types);
    $this->currentUser->setAccount(User::load(1));
    $plugins = $this->demoContentManager->createInstances($content_types);

    /** @var \Drupal\social_demo\DemoContentInterface $plugin */
    foreach ($plugins as $plugin) {
      $definition = $plugin->getPluginDefinition();
      $plugin->setProfile($options['profile']);
      $plugin->createContent();
      $count = $plugin->count();

      if ($count !== FALSE) {
        $this->logger()->success(dt("{$count} {$definition['label']}(s) created"));
      }
    }
  }

  /**
   * Removes demo content.
   *
   * @param $content_types
   *   Type of node to update
   *   Argument provided to the drush command.
   *
   * @command social-demo:remove
   * @aliases sdr
   * @option profile
   *   Profile to install.
   * @usage social-demo:remove foo
   *   foo is the type of content type
   */
  public function removeDemoContent(array $content_types = [], $options = ['profile' => '']) {
    $content_types = StringUtils::csvToArray($content_types);
    $this->convertContentTypes($content_types);
    $this->currentUser->setAccount(User::load(1));
    $plugins = $this->demoContentManager->createInstances($content_types);

    /** @var \Drupal\social_demo\DemoContentInterface $plugin */
    foreach ($plugins as $plugin) {
      $definition = $plugin->getPluginDefinition();
      $plugin->setProfile($options['profile']);
      $plugin->removeContent();
      $count = $plugin->count();
      if ($count !== FALSE) {
        $this->logger()->success(dt("{$definition['label']}(s) removed"));
      }
    }
  }

  /**
   * Convert old keys of content types to new.
   *
   * @param array $content_types
   *   Array containing content types.
   */
  private function convertContentTypes(array &$content_types) {
    $replacements = [
      'eventenrollment' => 'event_enrollment',
      'eventtype' => 'event_type',
      'likes' => 'like',
    ];

    foreach ($content_types as &$content_type) {
      if (isset($replacements[$content_type])) {
        $content_type = $replacements[$content_type];
      }
    }
  }

  /**
   * Load the Drush stub class.
   */
  private function loadDrushStub() {
    require_once DRUPAL_ROOT . '/profiles/contrib/social/modules/custom/social_demo/social_demo.drush_testing.inc';
  }

}
