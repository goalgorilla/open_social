<?php

/**
 * @file
 * Contains \Drupal\config_update_ui\Controller\ConfigUpdateController.
 */

namespace Drupal\config_update_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\config_update\ConfigDiffInterface;
use Drupal\config_update\ConfigListInterface;
use Drupal\config_update\ConfigRevertInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Configuration Revert module operations.
 */
class ConfigUpdateController extends ControllerBase {

  /**
   * The config differ.
   *
   * @var \Drupal\config_update\ConfigDiffInterface
   */
  protected $configDiff;

  /**
   * The config lister.
   *
   * @var \Drupal\config_update\ConfigListInterface
   */
  protected $configList;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigRevertInterface
   */
  protected $configRevert;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a ConfigUpdateController object.
   *
   * @param \Drupal\config_update\ConfigDiffInterface $config_diff
   *   The config differ.
   * @param \Drupal\config_update\ConfigListInterface $config_list
   *   The config lister.
   * @param \Drupal\config_update\ConfigREvertInterface $config_update
   *   The config reverter.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   The diff formatter to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigDiffInterface $config_diff, ConfigListInterface $config_list, ConfigRevertInterface $config_update, DiffFormatter $diff_formatter, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->configDiff = $config_diff;
    $this->configList = $config_list;
    $this->configRevert = $config_update;
    $this->diffFormatter = $diff_formatter;
    $this->diffFormatter->show_header = FALSE;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_update.config_diff'),
      $container->get('config_update.config_list'),
      $container->get('config_update.config_update'),
      $container->get('diff.formatter'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * Imports configuration from a module, theme, or profile.
   *
   * Configuration is assumed not to currently exist.
   *
   * @param string $config_type
   *   The type of configuration.
   * @param string $config_name
   *   The name of the config item, without the prefix.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the updates report.
   */
  public function import($config_type, $config_name) {
    $this->configRevert->import($config_type, $config_name);

    drupal_set_message($this->t('The configuration was imported.'));
    return $this->redirect('config_update_ui.report');
  }

  /**
   * Shows the diff between active and provided configuration.
   *
   * @param string $config_type
   *   The type of configuration.
   * @param string $config_name
   *   The name of the config item, without the prefix.
   *
   * @return array
   *   Render array for page showing differences between them.
   */
  public function diff($config_type, $config_name) {
    $diff = $this->configDiff->diff(
      $this->configRevert->getFromExtension($config_type, $config_name),
      $this->configRevert->getFromActive($config_type, $config_name)
    );

    $build = array();
    $definition = $this->configList->getType($config_type);
    $config_type_label = ($definition) ? $definition->getLabel() : $this->t('Simple configuration');
    $build['#title'] = $this->t('Config difference for @type @name', array('@type' => $config_type_label, '@name' => $config_name));
    $build['#attached']['library'][] = 'system/diff';

    $build['diff'] = array(
      '#type' => 'table',
      '#header' => array(
        array('data' => $this->t('Source config'), 'colspan' => '2'),
        array('data' => $this->t('Site config'), 'colspan' => '2'),
      ),
      '#rows' => $this->diffFormatter->format($diff),
      '#attributes' => array('class' => array('diff')),
    );

    $url = new Url('config_update_ui.report');

    $build['back'] = array(
      '#type' => 'link',
      '#attributes' => array(
        'class' => array(
          'dialog-cancel',
        ),
      ),
      '#title' => $this->t("Back to 'Updates report' page."),
      '#url' => $url,
    );

    return $build;
  }

  /**
   * Generates the config updates report.
   *
   * @param string $report_type
   *   (optional) Type of report to run:
   *   - type: Configuration entity type.
   *   - module: Module.
   *   - theme: Theme.
   *   - profile: Install profile.
   * @param string $name
   *   (optional) Name of specific item to run report for (config entity type
   *   ID, module machine name, etc.). Ignored for profile.
   *
   * @return array
   *   Render array for report, with section at the top for selecting another
   *   report to run. If either $report_type or $name is missing, the report
   *   itself is not generated.
   */
  public function report($report_type = NULL, $name = NULL) {
    $links = $this->generateReportLinks();

    $report = $this->generateReport($report_type, $name);
    if (!$report) {
      return $links;
    }

    // If there is a report, extract the title, put table of links in a
    // details element, and add report to build.
    $build = array();
    $build['#title'] = $report['#title'];
    unset($report['#title']);

    $build['links_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Generate new report'),
      '#children' => $links,
    );

    $build['report'] = $report;

    $build['#attached']['library'][] = 'config_update/report_css';

    return $build;
  }

  /**
   * Generates the operations links for running individual reports.
   *
   * @return array
   *   Render array for the operations links for running reports.
   */
  protected function generateReportLinks() {

    // These links are put into an 'operations' render array element. They do
    // not look good outside of tables. Also note that the array index in
    // operations links is used as a class on the LI element. Some classes are
    // special in the Seven CSS, such as "contextual", so avoid hitting these
    // accidentally by prefixing.

    $build = array();

    $build['links'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Report type'),
        $this->t('Report on'),
      ),
      '#rows' => array(),
    );

    $definitions = $this->configList->listTypes();
    $links = array();
    foreach ($definitions as $entity_type => $definition) {
      $links['type_' . $entity_type] = array(
        'title' => $definition->getLabel(),
        'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'type', 'name' => $entity_type)),
      );
    }

    uasort($links, array($this, 'sortLinks'));

    $links = array(
      'type_all' => array(
        'title' => $this->t('All types'),
        'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'type', 'name' => 'system.all')),
      ),
      'type_system.simple' => array(
        'title' => $this->t('Simple configuration'),
        'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'type', 'name' => 'system.simple')),
      ),
    ) + $links;

    $build['links']['#rows'][] = array(
      $this->t('Configuration type'),
      array('data' => array(
        '#type' => 'operations',
        '#links' => $links,
      )),
    );

    // Make a list of installed modules.
    $profile = Settings::get('install_profile');
    $modules = $this->moduleHandler->getModuleList();
    $links = array();
    foreach ($modules as $machine_name => $module) {
      if ($machine_name != $profile) {
        $links['module_' . $machine_name] = array(
          'title' => $this->moduleHandler->getName($machine_name),
          'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'module', 'name' => $machine_name)),
        );
      }
    }
    uasort($links, array($this, 'sortLinks'));

    $build['links']['#rows'][] = array(
      $this->t('Module'),
      array('data' => array(
        '#type' => 'operations',
        '#links' => $links,
      )),
    );

    // Make a list of installed themes.
    $themes = $this->themeHandler->listInfo();
    $links = array();
    foreach ($themes as $machine_name => $theme) {
      $links['theme_' . $machine_name] = array(
        'title' => $this->themeHandler->getName($machine_name),
        'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'theme', 'name' => $machine_name)),
      );
    }
    uasort($links, array($this, 'sortLinks'));

    $build['links']['#rows'][] = array(
      $this->t('Theme'),
      array('data' => array(
        '#type' => 'operations',
        '#links' => $links,
      )),
    );

    // Profile is just one option.
    $links = array();
    $links['profile_' . $profile] = array(
      'title' => $this->moduleHandler->getName($profile),
      'url' => Url::fromRoute('config_update_ui.report', array('report_type' => 'profile')),
    );
    $build['links']['#rows'][] = array(
      $this->t('Installation profile'),
      array('data' => array(
        '#type' => 'operations',
        '#links' => $links,
      )),
    );

    return $build;
  }

  /**
   * Generates a report about config updates.
   *
   * @param string $report_type
   *   Type of report to generate: 'type', 'module', 'theme', or 'profile'.
   * @param string $value
   *   Machine name of a configuration type, module, or theme to generate the
   *   report for. Ignored for profile, since that uses the active profile.
   *
   * @return array
   *   Render array for the updates report. Empty if invalid or missing
   *   report type or value.
   */
  protected function generateReport($report_type, $value) {
    // Figure out what to name the report, and incidentally, validate that
    // $value exists for this type of report.
    switch($report_type) {
      case 'type':
        if ($value == 'system.all') {
          $label = $this->t('All configuration');
        }
        elseif ($value == 'system.simple') {
          $label = $this->t('Simple configuration');
        }
        else {
          $definition = $this->configList->getType($value);
          if (!$definition) {
            return NULL;
          }

          $label = $this->t('@name configuration', array('@name' => $definition->getLabel()));
        }

        break;

      case 'module':
        $list = $this->moduleHandler->getModuleList();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name module', array('@name' => $this->moduleHandler->getName($value)));
        break;

      case 'theme':
        $list = $this->themeHandler->listInfo();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name theme', array('@name' => $this->themeHandler->getName($value)));
        break;

      case 'profile':
        $profile = Settings::get('install_profile');
        $label = $this->t('@name profile', array('@name' => $this->moduleHandler->getName($profile)));
        break;

      default:
        return NULL;
    }

    // List the active and extension-provided config.
    list($active_list, $install_list, $optional_list) = $this->configList->listConfig($report_type, $value);

    // Build the report.
    $build = array();

    $build['#title'] = $this->t('Configuration updates report for @label', array('@label' => $label));
    $build['report_header'] = array('#markup' => '<h3>' . $this->t('Updates report') . '</h3>');

    // List items missing from site.
    $removed = array_diff($install_list, $active_list);
    $build['removed'] = array(
      '#caption' => $this->t('Missing configuration items'),
      '#empty' => $this->t('None: all provided configuration items are in your active configuration.'),
    ) + $this->makeReportTable($removed, 'extension', array('import'));

    // List optional items that are not installed.
    $inactive = array_diff($optional_list, $active_list);
    $build['inactive'] = array(
      '#caption' => $this->t('Inactive optional items'),
      '#empty' => $this->t('None: all optional configuration items are in your active configuration.'),
    ) + $this->makeReportTable($inactive, 'extension', array('import'));

    // List items added to site, which only makes sense in the report for a
    // config type.
    $added = array_diff($active_list, $install_list, $optional_list);
    if ($report_type == 'type') {
      $build['added'] = array(
        '#caption' => $this->t('Added configuration items'),
        '#empty' => $this->t('None: all active configuration items of this type were provided by modules, themes, or install profile.'),
      ) + $this->makeReportTable($added, 'active', array('export'));
    }

    // For differences, we need to go through the array of config in both
    // and see if each config item is the same or not.
    $both = array_diff($active_list, $added);
    $different = array();
    foreach ($both as $name) {
      if (!$this->configDiff->same(
        $this->configRevert->getFromExtension('', $name),
        $this->configRevert->getFromActive('', $name)
      )) {
        $different[] = $name;
      }
    }
    $build['different'] = array(
      '#caption' => $this->t('Changed configuration items'),
      '#empty' => $this->t('None: no active configuration items differ from their current provided versions.'),
    ) + $this->makeReportTable($different, 'active', array('diff', 'export', 'revert'));

    return $build;
  }

  /**
   * Builds a table for the report.
   *
   * @param string[] $names
   *   List of machine names of config items for the table.
   * @param string $storage
   *   Config storage the items can be loaded from, either 'active' or
   *   'extension'.
   * @param string[] $actions
   *   Action links to include, one or more of:
   *   - diff
   *   - revert
   *   - export
   *   - import
   *
   * @return array
   *   Render array for the table, not including the #empty and #prefix
   *   properties.
   */
  protected function makeReportTable($names, $storage, $actions) {
    $build = array();

    $build['#type'] = 'table';

    $build['#attributes'] = array('class' => array('config-update-report'));

    $build['#header'] = array(
      'name' => array(
        'data' => $this->t('Machine name'),
      ),
      'label' => array(
        'data' => $this->t('Label (if any)'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'type' => array(
        'data' => $this->t('Type'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'operations' => array(
        'data' => $this->t('Operations'),
      ),
    );

    $build['#rows'] = array();

    foreach ($names as $name) {
      $row = array();
      if ($storage == 'active') {
        $config = $this->configRevert->getFromActive('', $name);
      }
      else {
        $config = $this->configRevert->getFromExtension('', $name);
      }

      // Figure out what type of config it is, and get the ID.
      $entity_type = $this->configList->getTypeNameByConfigName($name);

      if (!$entity_type) {
        // This is simple config.
        $id = $name;
        $type_label = $this->t('Simple configuration');
        $entity_type = 'system.simple';
      }
      else {
        $definition = $this->configList->getType($entity_type);
        $id_key = $definition->getKey('id');
        $id = $config[$id_key];
        $type_label = $definition->getLabel();
      }

      $label = (isset($config['label'])) ? $config['label'] : '';
      $row[] = $name;
      $row[] = $label;
      $row[] = $type_label;

      $links = array();
      $routes = array(
        'export' => 'config.export_single',
        'import' => 'config_update_ui.import',
        'diff' => 'config_update_ui.diff',
        'revert' => 'config_update_ui.revert',
      );
      $titles = array(
        'export' => $this->t('Export'),
        'import' => $this->t('Import from source'),
        'diff' => $this->t('Show differences'),
        'revert' => $this->t('Revert to source'),
      );

      foreach ($actions as $action) {
        $links[$action] = array(
          'url' => Url::fromRoute($routes[$action], array('config_type' => $entity_type, 'config_name' => $id)),
          'title' => $titles[$action],
        );
      }

      $row[] = array('data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ));

      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * Compares links for uasort(), to sort by displayed link title.
   */
  protected static function sortLinks($link1, $link2) {
    $title1 = $link1['title'];
    $title2 = $link2['title'];
    if ($title1 == $title2) {
      return 0;
    }
    return ($title1 < $title2) ? -1 : 1;
  }

}
