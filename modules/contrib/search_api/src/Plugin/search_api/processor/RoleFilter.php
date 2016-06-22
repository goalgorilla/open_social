<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Filters out users based on their role.
 *
 * @SearchApiProcessor(
 *   id = "role_filter",
 *   label = @Translation("Role filter"),
 *   description = @Translation("Filters out users based on their role."),
 *   stages = {
 *     "preprocess_index" = -50
 *   }
 * )
 */
class RoleFilter extends ProcessorPluginBase {

  /**
   * Can only be enabled for an index that indexes the user entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'user') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'default' => TRUE,
      'roles' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $options = array_map(function (RoleInterface $role) {
      return Html::escape($role->label());
    }, user_roles());

    $form['default'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Which users should be indexed?'),
      '#default_value' => $this->configuration['default'],
      '#options' => array(
        1 => $this->t('All but those from one of the selected roles'),
        0 => $this->t('Only those from the selected roles'),
      ),
    );
    $form['roles'] = array(
      '#type' => 'select',
      '#title' => $this->t('Roles'),
      '#default_value' => array_combine($this->configuration['roles'], $this->configuration['roles']),
      '#options' => $options,
      '#size' => min(4, count($options)),
      '#multiple' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['default'] = (bool) $values['default'];
    $values['roles'] = array_values(array_filter($values['roles']));
    $form_state->set('values', $values);

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    $selected_roles = array_combine($this->configuration['roles'], $this->configuration['roles']);
    $default = (bool) $this->configuration['default'];

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $account = $item->getOriginalObject()->getValue();
      if (!($account instanceof UserInterface)) {
        continue;
      }

      $account_roles = $account->getRoles();
      $account_roles = array_flip($account_roles);
      $has_some_roles = (bool) array_intersect_key($account_roles, $selected_roles);

      // If $default is TRUE, we want to remove those users with at least one of
      // the selected roles. Otherwise, we want to remove those with none.
      if ($default == $has_some_roles) {
        unset($items[$item_id]);
      }
    }
  }

}
