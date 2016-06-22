<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents the form state of a sub-form.
 */
class SubFormState implements FormStateInterface {

  /**
   * The keys which should be inherited as-is from the main form state.
   *
   * @var bool[]
   */
  protected static $inheritedKeys = array(
    'build_info' => TRUE,
    'rebuild_info' => TRUE,
    'rebuild' => TRUE,
    'response' => TRUE,
    'redirect' => TRUE,
    'redirect_route' => TRUE,
    'no_redirect' => TRUE,
    'method' => TRUE,
    'cache' => TRUE,
    'no_cache' => TRUE,
    'triggering_element' => TRUE,
  );

  /**
   * The form state of the main form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $mainFormState;

  /**
   * The keys that lead to the desired sub-form in the main form.
   *
   * @var string[]
   */
  protected $subKeys;

  /**
   * Internal storage for the sub-state, writing into the main form state.
   *
   * @var array
   */
  protected $internalStorage;

  /**
   * The values of the sub-form.
   *
   * @var array
   */
  protected $values;

  /**
   * The input of the sub-form.
   *
   * @var array
   */
  protected $input;

  /**
   * Constructs a SubFormState object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $main_form_state
   *   The state of the main form.
   * @param string[] $sub_keys
   *   The keys that lead to the desired sub-form in the main form.
   */
  public function __construct(FormStateInterface $main_form_state, array $sub_keys) {
    $this->mainFormState = $main_form_state;
    $this->subKeys = $sub_keys;
    $sub_state = &$main_form_state->get('sub_states');
    if (!isset($sub_state)) {
      $sub_state = array();
    }
    $this->internalStorage = &$this->applySubKeys($sub_state);
    if (!isset($this->internalStorage)) {
      $this->internalStorage = array();
    }
    $this->values = &$this->applySubKeys($main_form_state->getValues());
    if (!is_array($this->values)) {
      $this->values = array();
    }
    $this->input = &$this->applySubKeys($main_form_state->getUserInput());
    if (!is_array($this->input)) {
      $this->input = array();
    }
  }

  /**
   * Applies the sub-form's array keys to the given original array.
   *
   * @param array $original
   *   The original array, belonging to the main form.
   *
   * @return array
   *   The corresponding array for the sub form, as a reference.
   */
  protected function &applySubKeys(array &$original) {
    return NestedArray::getValue($original, $this->subKeys);
  }

  /**
   * {@inheritdoc}
   */
  public function &getCompleteForm() {
    return $this->applySubKeys($this->mainFormState->getCompleteForm());
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleteForm(array &$complete_form) {
    $sub_form = &$this->applySubKeys($this->mainFormState->getCompleteForm());
    $sub_form = $complete_form;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function loadInclude($module, $type, $name = NULL) {
    return $this->mainFormState->loadInclude($module, $type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableArray() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setFormState(array $form_state_additions) {
    foreach ($form_state_additions as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponse(Response $response) {
    $this->mainFormState->setResponse($response);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->mainFormState->getResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirect($route_name, array $route_parameters = array(), array $options = array()) {
    $this->mainFormState->setRedirect($route_name, $route_parameters, $options);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUrl(Url $url) {
    $this->mainFormState->setRedirectUrl($url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    return $this->mainFormState->getRedirect();
  }

  /**
   * {@inheritdoc}
   */
  public function setStorage(array $storage) {
    $this->internalStorage = $storage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getStorage() {
    return $this->internalStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function &get($property) {
    if (isset(self::$inheritedKeys[$property])) {
      return $this->mainFormState->get($property);
    }
    $value = &NestedArray::getValue($this->internalStorage, (array) $property);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    if (isset(self::$inheritedKeys[$property])) {
      $this->mainFormState->set($property, $value);
    }
    else {
      $this->internalStorage[$property] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function has($property) {
    return isset(self::$inheritedKeys[$property])
        || array_key_exists($property, $this->internalStorage);
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildInfo(array $build_info) {
    $this->mainFormState->setBuildInfo($build_info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildInfo() {
    return $this->mainFormState->getBuildInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function addBuildInfo($property, $value) {
    $this->mainFormState->addBuildInfo($property, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getUserInput() {
    $user_input = &$this->mainFormState->getUserInput();
    return $this->applySubKeys($user_input);
  }

  /**
   * {@inheritdoc}
   */
  public function setUserInput(array $user_input) {
    $old = &$this->getUserInput();
    $old = $user_input;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValue($key, $default = NULL) {
    $exists = NULL;
    $value = &NestedArray::getValue($this->getValues(), (array) $key, $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    $this->values = $values;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($key, $value) {
    $this->values[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetValue($key) {
    unset($this->values[$key]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue($key) {
    if (isset($this->values[$key])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isValueEmpty($key) {
    if (empty($this->values[$key])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setValueForElement(array $element, $value) {
    $this->mainFormState->setValueForElement($element, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function hasAnyErrors() {
    return FormState::hasAnyErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorByName($name, $message = '') {
    $this->mainFormState->setErrorByName(implode('][', $this->subKeys) . '][' . $name, $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setError(array &$element, $message = '') {
    $this->mainFormState->setError($element, $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrors() {
    $this->mainFormState->clearErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->mainFormState->getErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function getError(array $element) {
    return $this->mainFormState->getError($element);
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuild($rebuild = TRUE) {
    $this->mainFormState->setRebuild($rebuild);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRebuilding() {
    return $this->mainFormState->isRebuilding();
  }

  /**
   * {@inheritdoc}
   */
  public function setInvalidToken($invalid_token) {
    $this->mainFormState->setInvalidToken($invalid_token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInvalidToken() {
    return $this->mainFormState->hasInvalidToken();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCallback($callback) {
    return $this->mainFormState->prepareCallback($callback);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject() {
    return $this->mainFormState->getFormObject();
  }

  /**
   * {@inheritdoc}
   */
  public function setFormObject(FormInterface $form_object) {
    $this->mainFormState->setFormObject($form_object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlwaysProcess($always_process = TRUE) {
    $this->mainFormState->setAlwaysProcess($always_process);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlwaysProcess() {
    return $this->mainFormState->getAlwaysProcess();
  }

  /**
   * {@inheritdoc}
   */
  public function setButtons(array $buttons) {
    $this->mainFormState->setButtons($buttons);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return $this->mainFormState->getButtons();
  }

  /**
   * {@inheritdoc}
   */
  public function setCached($cache = TRUE) {
    $this->mainFormState->setCached($cache);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCached() {
    return $this->mainFormState->isCached();
  }

  /**
   * {@inheritdoc}
   */
  public function disableCache() {
    $this->mainFormState->disableCache();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecuted() {
    $this->mainFormState->setExecuted();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExecuted() {
    return $this->mainFormState->isExecuted();
  }

  /**
   * {@inheritdoc}
   */
  // @todo What are groups? Is this the way to handle them in a sub-form?
  public function setGroups(array $groups) {
    $this->mainFormState->setGroups($groups);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroups() {
    return $this->mainFormState->getGroups();
  }

  /**
   * {@inheritdoc}
   */
  public function setHasFileElement($has_file_element = TRUE) {
    $this->mainFormState->setHasFileElement($has_file_element);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFileElement() {
    return $this->mainFormState->hasFileElement();
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitValidationErrors($limit_validation_errors) {
    $add_subkeys = function(array $path) {
      return array_merge($this->subKeys, $path);
    };
    $limit_validation_errors = array_map($add_subkeys, $limit_validation_errors);
    $this->mainFormState->setLimitValidationErrors($limit_validation_errors);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitValidationErrors() {
    $limit_validation_errors = $this->mainFormState->getLimitValidationErrors();
    if ($limit_validation_errors === NULL) {
      return NULL;
    }
    $return = array();
    $sub_keys_count = count($this->subKeys);
    foreach ($limit_validation_errors as $path) {
      if (array_slice($path, 0, $sub_keys_count) == $sub_keys_count) {
        // If the whole sub-form is included, it is the same (for the sub-form)
        // as if there was no limitation at all.
        if (count($path) == $sub_keys_count) {
          return NULL;
        }
        $return[] = $path;
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->mainFormState->setMethod($method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestMethod($method) {
    $this->mainFormState->setRequestMethod($method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isMethodType($method_type) {
    return $this->mainFormState->isMethodType($method_type);
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationEnforced($must_validate = TRUE) {
    $this->mainFormState->setValidationEnforced($must_validate);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationEnforced() {
    return $this->mainFormState->isValidationEnforced();
  }

  /**
   * {@inheritdoc}
   */
  public function disableRedirect($no_redirect = TRUE) {
    $this->mainFormState->disableRedirect($no_redirect);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRedirectDisabled() {
    return $this->mainFormState->isRedirectDisabled();
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessInput($process_input = TRUE) {
    $this->mainFormState->setProcessInput($process_input);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProcessingInput() {
    return $this->mainFormState->isProcessingInput();
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammed($programmed = TRUE) {
    $this->mainFormState->setProgrammed($programmed);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProgrammed() {
    return $this->mainFormState->isProgrammed();
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammedBypassAccessCheck($programmed_bypass_access_check = TRUE) {
    $this->mainFormState->setProgrammedBypassAccessCheck($programmed_bypass_access_check);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBypassingProgrammedAccessChecks() {
    return $this->mainFormState->isBypassingProgrammedAccessChecks();
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildInfo(array $rebuild_info) {
    $this->mainFormState->setRebuildInfo($rebuild_info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRebuildInfo() {
    return $this->mainFormState->getRebuildInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function addRebuildInfo($property, $value) {
    $this->mainFormState->addRebuildInfo($property, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitHandlers(array $submit_handlers) {
    $this->mainFormState->setSubmitHandlers($submit_handlers);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandlers() {
    return $this->mainFormState->getSubmitHandlers();
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitted() {
    $this->mainFormState->setSubmitted();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmitted() {
    return $this->mainFormState->isSubmitted();
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporary(array $temporary) {
    $this->mainFormState->setTemporary($temporary);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemporary() {
    return $this->mainFormState->getTemporary();
  }

  /**
   * {@inheritdoc}
   */
  public function &getTemporaryValue($key) {
    return $this->mainFormState->getTemporaryValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporaryValue($key, $value) {
    $this->mainFormState->setTemporaryValue($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTemporaryValue($key) {
    return $this->mainFormState->getTemporaryValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggeringElement($triggering_element) {
    $this->mainFormState->setTriggeringElement($triggering_element);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getTriggeringElement() {
    return $this->mainFormState->getTriggeringElement();
  }

  /**
   * {@inheritdoc}
   */
  public function setValidateHandlers(array $validate_handlers) {
    $this->mainFormState->setValidateHandlers($validate_handlers);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidateHandlers() {
    return $this->mainFormState->getValidateHandlers();
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationComplete($validation_complete = TRUE) {
    $this->mainFormState->setValidationComplete($validation_complete);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationComplete() {
    return $this->mainFormState->isValidationComplete();
  }

  /**
   * {@inheritdoc}
   */
  public function getCleanValueKeys() {
    return $this->mainFormState->getCleanValueKeys();
  }

  /**
   * {@inheritdoc}
   */
  public function setCleanValueKeys(array $keys) {
    $this->mainFormState->setCleanValueKeys($keys);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCleanValueKey($key) {
    $this->mainFormState->addCleanValueKey($key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanValues() {
    $this->mainFormState->cleanValues();
    return $this;
  }

}
