<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\SettingInterface.
 */

namespace Drupal\bootstrap\Plugin\Setting;

use Drupal\bootstrap\Plugin\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for an object oriented theme setting plugin.
 */
interface SettingInterface extends FormInterface {

  /**
   * Determines whether a theme setting should added to drupalSettings.
   *
   * By default, this value will be FALSE unless the method is overridden. This
   * is to ensure that no sensitive information can be potientially leaked.
   *
   * @see \Drupal\bootstrap\Plugin\Setting\SettingBase::drupalSettings()
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function drupalSettings();

  /**
   * The cache tags associated with this object.
   *
   * When this object is modified, these cache tags will be invalidated.
   *
   * @return string[]
   *   A set of cache tags.
   */
  public function getCacheTags();

  /**
   * Retrieves the setting's default value.
   *
   * @return string
   *   The setting's default value.
   */
  public function getDefaultValue();

  /**
   * Retrieves the group form element the setting belongs to.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\bootstrap\Utility\Element
   *   The group element object.
   */
  public function getGroup(array &$form, FormStateInterface $form_state);

  /**
   * Retrieves the setting's groups.
   *
   * @return array
   *   The setting's group.
   */
  public function getGroups();

  /**
   * Retrieves the form element for the setting.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\bootstrap\Utility\Element
   *   The setting element object.
   */
  public function getElement(array &$form, FormStateInterface $form_state);

  /**
   * Retrieves the setting's human-readable title.
   *
   * @return string
   *   The setting's type.
   */
  public function getTitle();

}
