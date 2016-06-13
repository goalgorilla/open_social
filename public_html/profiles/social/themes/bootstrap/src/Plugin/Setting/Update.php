<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Update.
 */

namespace Drupal\bootstrap\Plugin\Setting;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\Form\SystemThemeSettings;
use Drupal\bootstrap\Plugin\UpdateManager;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "schema" theme setting.
 *
 * @BootstrapSetting(
 *   id = "schema",
 *   type = "hidden",
 *   weight = -20,
 *   groups = false,
 * )
 */
class Update extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);

    $update_manager = new UpdateManager($this->theme);
    $pending = $update_manager->getPendingUpdates();
    if ($pending) {
      $form['update'] = [
        '#type' => 'details',
        '#title' => \Drupal::translation()->formatPlural(count($pending), 'Pending Update', 'Pending Updates'),
        '#panel_type' => 'primary',
        '#weight' => -20,
      ];
      $rows = [];
      foreach ($pending as $version => $update) {
        $row = [];
        $row[] = $version;
        $row[] = new FormattableMarkup('<strong>@title</strong><p class="help-block">@description</p>', [
          '@title' => $update->getTitle(),
          '@description' => $update->getDescription(),
        ]);
        $rows[] = [
          'class' => [$update->getLevel() ?: 'default'],
          'data' => $row,
        ];
      }
      $form['update']['table'] = [
        '#type' => 'table',
        '#header' => [t('Update'), t('Description')],
        '#rows' => $rows,
      ];
      $form['update']['update'] = [
        '#type' => 'submit',
        '#value' => t('Update @theme', [
          '@theme' => $this->theme->getTitle(),
        ]),
        '#icon' => Bootstrap::glyphicon('open'),
        // @todo Setting a class like this is unnecessary, create a suggestion.
        '#attributes' => [
          'class' => ['btn-primary'],
        ],
        '#submit' => [[get_class($this), 'updateTheme']],
      ];
    }
  }

  /**
   * Callback for updating a theme.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function updateTheme(array $form, FormStateInterface $form_state) {
    if ($theme = SystemThemeSettings::getTheme($form, $form_state)) {
      $update_manager = new UpdateManager($theme);
      $installed = [];

      // Iterate over all pending updates.
      $pending = $update_manager->getPendingUpdates();
      foreach ($pending as $version => $update) {
        // Run the update.
        $result = $update->update($theme);

        // Update failed. Show a message and stop the process.
        if ($result === FALSE) {
          drupal_set_message(t('The update @title (@version) failed. No further updates can be processed at this time.', [
            '@title' => $update->getTitle(),
            '@version' => $version,
          ]), 'error');
          break;
        }

        // Update succeeded.
        $installed[] = $version;
      }

      // Save the last installed schema version.
      if ($installed) {
        $installed = array_flip($installed);
        foreach (array_keys($installed) as $version) {
          $installed[$version] = new FormattableMarkup('@version - @title', [
            '@version' => $version,
            '@title' => $pending[$version]->getTitle(),
          ]);
        }
        $build = ['#theme' => 'item_list', '#items' => $installed];
        drupal_set_message(t('Successfully installed the following update(s) for %theme: @installed', [
          '%theme' => $theme->getTitle(),
          '@installed' => \Drupal::service('renderer')->render($build),
        ]));

        // Save the latest installed version.
        $theme->setSetting('schema', max(array_keys($installed)));
      }
    }
  }

}
