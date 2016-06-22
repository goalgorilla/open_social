<?php

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
   * @param \Drupal\config_update\ConfigRevertInterface $config_update
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

    $build = [];
    $definition = $this->configList->getType($config_type);
    $config_type_label = ($definition) ? $definition->getLabel() : $this->t('Simple configuration');
    $build['#title'] = $this->t('Config difference for @type @name', ['@type' => $config_type_label, '@name' => $config_name]);
    $build['#attached']['library'][] = 'system/diff';

    $build['diff'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Source config'), 'colspan' => '2'],
        ['data' => $this->t('Site config'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
      '#attributes' => ['class' => ['diff']],
    ];

    $url = new Url('config_update_ui.report');

    $build['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel',
        ],
      ],
      '#title' => $this->t("Back to 'Updates report' page."),
      '#url' => $url,
    ];

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
    $build = [];
    $build['#title'] = $report['#title'];
    unset($report['#title']);

    $build['links_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Generate new report'),
      '#children' => $links,
    ];

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
    $build = [];

    $build['links'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Report type'),
        $this->t('Report on'),
      ],
      '#rows' => [],
    ];

    $definitions = $this->configList->listTypes();
    $links = [];
    foreach ($definitions as $entity_type => $definition) {
      $links['type_' . $entity_type] = [
        'title' => $definition->getLabel(),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => $entity_type]),
      ];
    }

    uasort($links, [$this, 'sortLinks']);

    $links = [
      'type_all' => [
        'title' => $this->t('All types'),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => 'system.all']),
      ],
      'type_system.simple' => [
        'title' => $this->t('Simple configuration'),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => 'system.simple']),
      ],
    ] + $links;

    $build['links']['#rows'][] = [
      $this->t('Configuration type'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Make a list of installed modules.
    $profile = Settings::get('install_profile');
    $modules = $this->moduleHandler->getModuleList();
    $links = [];
    foreach ($modules as $machine_name => $module) {
      if ($machine_name != $profile) {
        $links['module_' . $machine_name] = [
          'title' => $this->moduleHandler->getName($machine_name),
          'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'module', 'name' => $machine_name]),
        ];
      }
    }
    uasort($links, [$this, 'sortLinks']);

    $build['links']['#rows'][] = [
      $this->t('Module'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Make a list of installed themes.
    $themes = $this->themeHandler->listInfo();
    $links = [];
    foreach ($themes as $machine_name => $theme) {
      $links['theme_' . $machine_name] = [
        'title' => $this->themeHandler->getName($machine_name),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'theme', 'name' => $machine_name]),
      ];
    }
    uasort($links, [$this, 'sortLinks']);

    $build['links']['#rows'][] = [
      $this->t('Theme'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Profile is just one option.
    $links = [];
    $links['profile_' . $profile] = [
      'title' => $this->moduleHandler->getName($profile),
      'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'profile']),
    ];
    $build['links']['#rows'][] = [
      $this->t('Installation profile'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

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
    switch ($report_type) {
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

          $label = $this->t('@name configuration', ['@name' => $definition->getLabel()]);
        }

        break;

      case 'module':
        $list = $this->moduleHandler->getModuleList();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name module', ['@name' => $this->moduleHandler->getName($value)]);
        break;

      case 'theme':
        $list = $this->themeHandler->listInfo();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name theme', ['@name' => $this->themeHandler->getName($value)]);
        break;

      case 'profile':
        $profile = Settings::get('install_profile');
        $label = $this->t('@name profile', ['@name' => $this->moduleHandler->getName($profile)]);
        break;

      default:
        return NULL;
    }

    // List the active and extension-provided config.
    list($active_list, $install_list, $optional_list) = $this->configList->listConfig($report_type, $value);

    // Build the report.
    $build = [];

    $build['#title'] = $this->t('Configuration updates report for @label', ['@label' => $label]);
    $build['report_header'] = ['#markup' => '<h3>' . $this->t('Updates report') . '</h3>'];

    // List items missing from site.
    $removed = array_diff($install_list, $active_list);
    $build['removed'] = [
      '#caption' => $this->t('Missing configuration items'),
      '#empty' => $this->t('None: all provided configuration items are in your active configuration.'),
    ] + $this->makeReportTable($removed, 'extension', ['import']);

    // List optional items that are not installed.
    $inactive = array_diff($optional_list, $active_list);
    $build['inactive'] = [
      '#caption' => $this->t('Inactive optional items'),
      '#empty' => $this->t('None: all optional configuration items are in your active configuration.'),
    ] + $this->makeReportTable($inactive, 'extension', ['import']);

    // List items added to site, which only makes sense in the report for a
    // config type.
    $added = array_diff($active_list, $install_list, $optional_list);
    if ($report_type == 'type') {
      $build['added'] = [
        '#caption' => $this->t('Added configuration items'),
        '#empty' => $this->t('None: all active configuration items of this type were provided by modules, themes, or install profile.'),
      ] + $this->makeReportTable($added, 'active', ['export', 'delete']);
    }

    // For differences, we need to go through the array of config in both
    // and see if each config item is the same or not.
    $both = array_diff($active_list, $added);
    $different = [];
    foreach ($both as $name) {
      if (!$this->configDiff->same(
        $this->configRevert->getFromExtension('', $name),
        $this->configRevert->getFromActive('', $name)
      )) {
        $different[] = $name;
      }
    }
    $build['different'] = [
      '#caption' => $this->t('Changed configuration items'),
      '#empty' => $this->t('None: no active configuration items differ from their current provided versions.'),
    ] + $this->makeReportTable($different, 'active', ['diff', 'export', 'revert']);

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
   *   - delete
   *
   * @return array
   *   Render array for the table, not including the #empty and #prefix
   *   properties.
   */
  protected function makeReportTable($names, $storage, $actions) {
    $build = [];

    $build['#type'] = 'table';

    $build['#attributes'] = ['class' => ['config-update-report']];

    $build['#header'] = [
      'name' => [
        'data' => $this->t('Machine name'),
      ],
      'label' => [
        'data' => $this->t('Label (if any)'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'operations' => [
        'data' => $this->t('Operations'),
      ],
    ];

    $build['#rows'] = [];

    foreach ($names as $name) {
      $row = [];
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

      $links = [];
      $routes = [
        'export' => 'config.export_single',
        'import' => 'config_update_ui.import',
        'diff' => 'config_update_ui.diff',
        'revert' => 'config_update_ui.revert',
        'delete' => 'config_update_ui.delete',
      ];
      $titles = [
        'export' => $this->t('Export'),
        'import' => $this->t('Import from source'),
        'diff' => $this->t('Show differences'),
        'revert' => $this->t('Revert to source'),
        'delete' => $this->t('Delete'),
      ];

      foreach ($actions as $action) {
        $links[$action] = [
          'url' => Url::fromRoute($routes[$action], ['config_type' => $entity_type, 'config_name' => $id]),
          'title' => $titles[$action],
        ];
      }

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

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
