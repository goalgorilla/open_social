<?php

namespace Drupal\social_geolocation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class GeolocationSearchContentForm.
 *
 * @package Drupal\social_geolocation\Form
 */
class GeolocationSearchContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geolocation_search_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search_input_content'] = array(
      '#type' => 'textfield',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search Content'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_content_view =  'search_content_proximity';
    if (empty($form_state->getValue('search_input_content'))) {
      // Redirect to the search content page with empty search values.
      $search_content_page = Url::fromRoute("view.$search_content_view.page_no_value");
    }
    else {
      // Redirect to the search content page with filters in the GET parameters.
      $search_input = $form_state->getValue('search_input_content');
      $search_content_page = Url::fromRoute("view.$search_content_view.page", array('keys' => $search_input));
    }
    $redirect_path = $search_content_page->toString();

    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());

    $redirect = Url::fromUserInput($redirect_path, array('query' => $query));

    $form_state->setRedirectUrl($redirect);
  }

}
