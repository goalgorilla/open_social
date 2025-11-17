<?php

namespace Drupal\social_group\Traits;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Trait for updating VBO tempstore data using trait methods.
 *
 * This trait provides a method to update tempstore data that replicates
 * the logic from the private updateTempstoreData() method in
 * ViewsBulkOperationsBulkForm, but uses trait methods instead.
 */
trait ViewsBulkOperationsTempstoreUpdateTrait {

  /**
   * Updates tempstore data using trait methods.
   *
   * This method replicates the logic from the private updateTempstoreData()
   * method in the parent class, but uses trait methods instead.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function updateTempstoreDataUsingTrait(FormStateInterface $form_state): void {
    // Initialize view data.
    $this->viewData->init($this->view, $this->displayHandler, $this->options['relationship']);

    // Get entity data if form is being displayed (not submitted).
    $view_entity_data = NULL;
    if (empty($form_state->getUserInput()['op'])) {
      $view_entity_data = $this->viewData->getViewEntityData();
    }

    // Get current tempstore data.
    $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);

    // Build variable array with parameters subject to change.
    // Get exposed input from view and process it
    // (replicating getExposedInput logic).
    $exposed_input = $this->view->getExposedInput();
    // Remove ajax_page_state that leaks to exposed input if AJAX is enabled.
    unset($exposed_input['ajax_page_state']);
    foreach ($this->view->exposed_raw_input as $key => $value) {
      if (!\array_key_exists($key, $exposed_input)) {
        $exposed_input[$key] = $value;
      }
    }
    // Sort values to avoid problems when comparing old and current exposed
    // input.
    \ksort($exposed_input);

    $variable = [
      'batch' => $this->options['batch'],
      'batch_size' => $this->options['batch'] ? $this->options['batch_size'] : 0,
      'total_results' => $this->viewData->getTotalResults($this->options['clear_on_exposed']),
      'relationship_id' => $this->options['relationship'],
      'arguments' => $this->view->args,
      'exposed_input' => $exposed_input,
    ];

    // Add bulk form keys when the form is displayed.
    if ($view_entity_data !== NULL) {
      $variable['bulk_form_keys'] = [];
      foreach ($view_entity_data as $row_index => $item) {
        $variable['bulk_form_keys'][$row_index] = $item[0];
      }
    }

    // Set redirect URL taking destination into account.
    $request = $this->requestStack->getCurrentRequest();
    if ($request !== NULL) {
      $destination = $request->query->get('destination');
      if ($destination && \is_string($destination)) {
        $request->query->remove('destination');
        unset($variable['exposed_input']['destination']);
        if (\strpos($destination, '/') !== 0) {
          $destination = '/' . $destination;
        }
        $variable['redirect_url'] = Url::fromUserInput($destination, []);
      }
      else {
        $variable['redirect_url'] = Url::createFromRequest(clone $request);
      }
    }
    else {
      // Fallback if no request is available.
      $variable['redirect_url'] = Url::fromRoute('<front>');
    }

    // Set exposed filters values to be kept after action execution.
    $query = $variable['redirect_url']->getOption('query');
    if ($query === NULL) {
      $query = [];
    }
    $query += $variable['exposed_input'];
    $variable['redirect_url']->setOption('query', $query);

    // Create tempstore data object if it doesn't exist.
    if (!\is_array($tempstoreData)) {
      $tempstoreData = [];

      // Add initial values.
      $tempstoreData += [
        'view_id' => $this->view->id(),
        'display_id' => $this->view->current_display,
        'list' => [],
        'exclude_mode' => FALSE,
      ];

      // Add variable parameters.
      $tempstoreData += $variable;

      $this->setTempstoreData($tempstoreData, $this->view->id(), $this->view->current_display);
    }
    // Update some of the tempstore data parameters if required.
    else {
      $update = FALSE;

      // Delete list if view arguments and optionally exposed filters changed.
      $clear_triggers = ['arguments'];
      if ($this->options['clear_on_exposed']) {
        $clear_triggers[] = 'exposed_input';
      }

      foreach ($clear_triggers as $trigger) {
        if (\array_key_exists($trigger, $variable) && \array_key_exists($trigger, $tempstoreData) && $variable[$trigger] !== $tempstoreData[$trigger]) {
          $tempstoreData[$trigger] = $variable[$trigger];
          $tempstoreData['list'] = [];
          $tempstoreData['exclude_mode'] = FALSE;
          $update = TRUE;
          continue;
        }
        if (\array_key_exists($trigger, $variable)) {
          unset($variable[$trigger]);
        }
      }

      foreach ($variable as $param => $value) {
        if (!\array_key_exists($param, $tempstoreData) || $tempstoreData[$param] !== $value) {
          $update = TRUE;
          $tempstoreData[$param] = $value;
        }
      }

      if ($update) {
        $this->setTempstoreData($tempstoreData, $this->view->id(), $this->view->current_display);
      }
    }
  }

}
