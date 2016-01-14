<?php

/**
 * @file
 * Contains \Drupal\devel\Form\SettingsForm.
 */

namespace Drupal\devel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $current_url = Url::createFromRequest($request);
    $devel_config = $this->config('devel.settings');

    $form['api_url'] = array('#type' => 'textfield',
      '#title' => t('API Site'),
      '#default_value' => $devel_config->get('api_url'),
      '#description' => t('The base URL for your developer documentation links. You might change this if you run <a href=":url">api.module</a> locally.', array(':url' => Url::fromUri('http://drupal.org/project/api')->toString())));
    $form['page_alter'] = array('#type' => 'checkbox',
      '#title' => t('Display $page array'),
      '#default_value' => $devel_config->get('page_alter'),
      '#description' => t('Display $page array from <a href="http://api.drupal.org/api/function/hook_page_alter/7">hook_page_alter()</a> in the messages area of each page.'),
    );
    $form['raw_names'] = array('#type' => 'checkbox',
      '#title' => t('Display machine names of permissions and modules'),
      '#default_value' => $devel_config->get('raw_names'),
      '#description' => t('Display the language-independent machine names of the permissions in mouse-over hints on the <a href=":permissions_url">Permissions</a> page and the module base file names on the Permissions and <a href=":modules_url">Modules</a> pages.', array(':permissions_url' => Url::fromRoute('user.admin_permissions')->toString(), ':modules_url' => Url::fromRoute('system.modules_list')->toString())),
    );

    $error_handlers = devel_get_handlers();
    $form['error_handlers'] = array(
      '#type' => 'select',
      '#title' => t('Error handlers'),
      '#options' => array(
        DEVEL_ERROR_HANDLER_NONE => t('None'),
        DEVEL_ERROR_HANDLER_STANDARD => t('Standard Drupal'),
        DEVEL_ERROR_HANDLER_BACKTRACE_DPM => t('Kint backtrace in the message area'),
        DEVEL_ERROR_HANDLER_BACKTRACE_KINT => t('Kint backtrace above the rendered page'),
      ),
      '#multiple' => TRUE,
      '#default_value' => empty($error_handlers) ? DEVEL_ERROR_HANDLER_NONE : $error_handlers,
      '#description' => [
        [
          '#markup' => $this->t('Select the error handler(s) to use, in case you <a href=":choose">choose to show errors on screen</a>.', [':choose' => $this->url('system.logging_settings')])
        ],
        [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('<em>None</em> is a good option when stepping through the site in your debugger.'),
            $this->t('<em>Standard Drupal</em> does not display all the information that is often needed to resolve an issue.'),
            $this->t('<em>Kint backtrace</em> displays nice debug information when any type of error is noticed, but only to users with the %perm permission.', ['%perm' => t('Access developer information')]),
          ],
        ],
        [
          '#markup' => $this->t('Depending on the situation, the theme, the size of the call stack and the arguments, etc., some handlers may not display their messages, or display them on the subsequent page. Select <em>Standard Drupal</em> <strong>and</strong> <em>Kint backtrace above the rendered page</em> to maximize your chances of not missing any messages.') . '<br />' .
            $this->t('Demonstrate the current error handler(s):') . ' ' .
            $this->l('notice', $current_url->setOption('query', ['demo' => 'notice'])) . ', ' .
            $this->l('notice+warning', $current_url->setOption('query', ['demo' => 'warning'])). ', ' .
            $this->l('notice+warning+error', $current_url->setOption('query', ['demo' => 'error'])) . ' (' .
            $this->t('The presentation of the @error is determined by PHP.', ['@error' => 'error']) . ')'
        ],
      ],
    );
    $form['error_handlers']['#size'] = count($form['error_handlers']['#options']);
    if ($request->query->has('demo')) {
      if ($request->getMethod() == 'GET') {
        $this->demonstrateErrorHandlers($request->query->get('demo'));
      }
      $request->query->remove('demo');
    }

    $form['rebuild_theme'] = array(
     '#type' => 'checkbox',
     '#title' => t('Rebuild the theme information like the registry'),
     '#description' => t('While creating new templates, change the $theme.info.yml and theme_ overrides the theme information needs to be rebuilt.'),
     '#default_value' => $devel_config->get('rebuild_theme'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('devel.settings')
      ->set('api_url', $values['api_url'])
      ->set('page_alter', $values['page_alter'])
      ->set('raw_names', $values['raw_names'])
      ->set('error_handlers', $values['error_handlers'])
      ->set('rebuild_theme', $values['rebuild_theme'])
      ->save();
  }

  /**
   * @param string $severity
   */
  protected function demonstrateErrorHandlers($severity) {
    switch ($severity) {
      case 'notice':
        $undefined = $undefined;
        break;
      case 'warning':
        $undefined = $undefined;
        1/0;
        break;
      case 'error':
        $undefined = $undefined;
        1/0;
        devel_undefined_function();
        break;
    }
  }

}
