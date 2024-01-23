<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserData;
use Drupal\views\ViewExecutable;

/**
 * Class MembersPageHelperService.
 */
class MembersPageHelperService {

  /**
   * MembersPageHelperService constructor.
   *
   * @param \Drupal\user\UserData $userData
   *   The user data service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected UserData $userData,
    protected AccountProxyInterface $currentUser
  ) {}

  /**
   * Get items_per_page value in users_data table.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param string $module_name
   *   The module name.
   * @param string $users_data_key
   *   The key for 'users_data' table.
   *
   * @return void
   *   There are some view object changes, so return void.
   */
  public function getItemsPerPage(ViewExecutable $view, string $module_name, string $users_data_key): void {
    // Get default pager options.
    $default_pager_options = $view->display_handler->getOption('pager');

    // Check if user selected specific number of items on the page and override
    // default one.
    if ($items_per_page = $this->userData->get($module_name, $this->currentUser->id(), $users_data_key)) {
      $default_pager_options['options']['items_per_page'] = $items_per_page;
      $view->display_handler->setOption('pager', $default_pager_options);
    }
  }

  /**
   * Set '__items_per_page' value in users_data table.
   *
   * @param array $form
   *   The views exposed form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return void
   *   There are some form changes, so return void.
   */
  public static function setItemsPerPage(array &$form, FormStateInterface $form_state): void {
    // Currently, only user input includes actual items_per_page value.
    $values = $form_state->getUserInput();

    // Check if 'items_per_page' value has been changed.
    if (
      isset($form['module_name']) &&
      isset($form['items_per_value_key']) &&
      isset($values['items_per_page'])
    ) {
      // Save selected specific number of items on the page for current user.
      \Drupal::service('user.data')
        ->set(
          $form['module_name']['#value'],
          \Drupal::service('current_user')->id(),
          $form['items_per_value_key']['#value'],
          $values['items_per_page']
        );
    }
  }

}
