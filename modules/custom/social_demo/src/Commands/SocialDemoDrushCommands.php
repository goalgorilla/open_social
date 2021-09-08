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
    parent::__construct();
  }

  /**
   * Create demo content.
   *
   * @param array $content_types
   *   A space-separated list of content types.
   * @param array $options
   *   Options for drush command.
   *
   * @command social-demo:add
   * @aliases sda
   * @option profile
   *   Profile to install.
   * @usage social-demo:add user topic --profile=EEA
   *   Generates demo content for users and topics from the EEA profile.
   *
   * @bootstrap root
   */
  public function addDemoContent(array $content_types, array $options = ['profile' => '']) {
    $content_types = StringUtils::csvToArray($content_types);
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
   * @param array $content_types
   *   A space-separated list of content types.
   * @param array $options
   *   Options for drush command.
   *
   * @command social-demo:remove
   * @aliases sdr
   * @option profile
   *   Profile to install.
   * @usage social-demo:remove user topic --profile=EEA
   *   Removes demo content for users and topics from the EEA profile.
   */
  public function removeDemoContent(array $content_types, array $options = ['profile' => '']) {
    $content_types = StringUtils::csvToArray($content_types);
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
   * Removes demo content.
   *
   * @param array $input_args
   *   Types of content and amount.
   *
   * @command social-demo:generate
   * @aliases sdg
   *
   * @usage drush social-demo:generate user:100 topic:2000 event:500 group:100
   *   Generates 100 demo users and 2000 topics.
   */
  public function generateBulkDemoContent(array $input_args) {
    $input_args = StringUtils::csvToArray($input_args);
    $this->currentUser->setAccount(User::load(1));

    $content_types = [];

    // Separate content types and their count.
    foreach ($input_args as $input_arg) {
      $pieces = explode(':', $input_arg);
      $content_type = $pieces[0];
      $content_types[] = $content_type;
      $num_content_types[$content_type] = $pieces[1];
    }

    $plugins = $this->demoContentManager->createInstances($content_types);

    /** @var \Drupal\social_demo\DemoContentInterface $plugin */
    foreach ($plugins as $plugin) {
      $num = $num_content_types[$plugin->getPluginId()];
      $definition = $plugin->getPluginDefinition();
      $plugin->createContent(TRUE, $num);
      $count = $plugin->count();

      if ($count !== FALSE) {
        $this->logger()->success(dt("{$count} {$definition['label']}(s) created"));
      }
    }
  }

}
