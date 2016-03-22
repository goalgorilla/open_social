<?php

/**
 * @file
 * Contains Drupal\template_mapper\Form\TemplateMappingForm.
 */

namespace Drupal\template_mapper\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TemplateMappingForm.
 *
 * @package Drupal\template_mapper\Form
 */
class TemplateMappingForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $template_mapping = $this->entity;

    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pre-existing theme hook suggestion'),
      '#default_value' => $template_mapping->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\template_mapper\Entity\TemplateMapping::load',
      ),
      '#required' => TRUE,
      '#description' => $this->t('This is the value that is suggested by Drupal core or another module. For instance, if you want to override the template for full view mode for article nodes you would enter node__article__full. See https://www.drupal.org/node/2358785 for more details on how to find existing theme hook suggestions.'),
    );

    // @todo, autocompleting to known existing templates would be nice.
    $form['mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Replacement suggestion'),
      '#default_value' => $template_mapping->getMapping(),
      '#required' => TRUE,
      '#description' => $this->t('Enter the name of the new template suggestion to which you are mapping. For instance, if you want a template named node--illustrated-list-item.html.twig to be used, enter node__illustrated_list_item.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $template_mapping = $this->entity;
    $status = $template_mapping->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Template mapping.', [
          '%label' => $template_mapping->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Template mapping.', [
          '%label' => $template_mapping->label(),
        ]));
    }
    $form_state->setRedirectUrl($template_mapping->urlInfo('collection'));
  }

}
