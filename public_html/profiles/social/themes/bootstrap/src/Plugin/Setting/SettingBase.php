<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\SettingBase.
 */

namespace Drupal\bootstrap\Plugin\Setting;

use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Base class for a setting.
 */
class SettingBase extends PluginBase implements SettingInterface {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    $this->getElement($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['rendered'];
  }

  /**
   * Retrieves all the form properties from the setting definition.
   *
   * @return array
   *   The form properties.
   */
  public function getElementProperties() {
    $properties = $this->getPluginDefinition();
    foreach ($properties as $name => $value) {
      if (in_array($name, ['class', 'defaultValue', 'definition', 'groups', 'id', 'provider', 'see'])) {
        unset($properties[$name]);
      }
    }
    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return isset($this->pluginDefinition['defaultValue']) ? $this->pluginDefinition['defaultValue'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElement(array &$form, FormStateInterface $form_state) {
    // Construct the group elements.
    $group = $this->getGroup($form, $form_state);
    $plugin_id = $this->getPluginId();
    if (!isset($group->$plugin_id)) {
      // Set properties from the plugin definition.
      foreach ($this->getElementProperties() as $name => $value) {
        $group->$plugin_id->setProperty($name, $value);
      }

      // Set default value from the stored form state value or theme setting.
      $default_value = $form_state->getValue($plugin_id, $this->theme->getSetting($plugin_id));
      $group->$plugin_id->setProperty('default_value', $default_value);

      // Append additional "see" link references to the description.
      $description = (string) $group->$plugin_id->getProperty('description') ?: '';
      /** @var \Drupal\Core\Render\Renderer $renderer */
      $renderer = \Drupal::service('renderer');
      $links = [];
      foreach ($this->pluginDefinition['see'] as $url => $title) {
        $link = [
          '#type' => 'link',
          '#url' => Url::fromUri($url),
          '#title' => $title,
          '#attributes' => [
            'target' => '_blank',
          ],
        ];
        $links[] = (string) $renderer->render($link);
      }
      if (!empty($links)) {
        $description .= '<br>';
        $description .= t('See also:');
        $description .= ' ' . implode(', ', $links);
        $group->$plugin_id->setProperty('description', $description);
      }
    }
    return $group->$plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(array &$form, FormStateInterface $form_state) {
    $groups = $this->getGroups();
    $group = Element::create($form);
    $first = TRUE;
    foreach ($groups as $key => $title) {
      if (!isset($group->$key)) {
        if ($title) {
          $group->$key = ['#type' => 'details', '#title' => $title];
        }
        else {
          $group->$key = ['#type' => 'container'];
        }
        $group = Element::create($group->$key->getArray());
        if ($first) {
          $group->setProperty('group', 'bootstrap');
        }
        else {
          $group->setProperty('open', FALSE);
        }
      }
      else {
        $group = Element::create($group->$key->getArray());
      }
      $first = FALSE;
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return !empty($this->pluginDefinition['groups']) ? $this->pluginDefinition['groups'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return !empty($this->pluginDefinition['title']) ? $this->pluginDefinition['title'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function submitForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {}

  /**
   * {@inheritdoc}
   */
  public static function validateForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {}

}
