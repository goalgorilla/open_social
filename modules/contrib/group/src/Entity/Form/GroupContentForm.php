<?php
/**
 * @file
 * Contains Drupal\group\Entity\Form\GroupContentForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group content edit forms.
 *
 * @ingroup group
 */
class GroupContentForm extends ContentEntityForm {

  /**
   * Returns the plugin responsible for this piece of group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The responsible group content enabler plugin.
   */
  protected function getContentPlugin() {
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    $group_content = $this->getEntity();
    return $group_content->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $config = $this->getContentPlugin()->getConfiguration();
    if (!empty($config['data']['info_text']['value'])) {
      $form['info_text'] = [
        '#markup' => $config['data']['info_text']['value'],
        '#weight' => -99,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    // The below redirect ensures the user will be redirected to the entity this
    // form was for. But only if there was no destination set in the URL.
    $route_name = $this->getContentPlugin()->getRouteName('canonical');
    $route_params = [
      'group' => $this->getEntity()->getGroup()->id(),
      'group_content' => $this->getEntity()->id(),
    ];
    $form_state->setRedirect($route_name, $route_params);

    return $return;
  }

}
