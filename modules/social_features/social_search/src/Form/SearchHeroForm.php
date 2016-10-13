<?php

namespace Drupal\social_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class SearchHeroForm.
 *
 * @package Drupal\social_search\Form
 */
class SearchHeroForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_hero_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search_input'] = array(
      '#type' => 'textfield',
    );

    // Prefill search input on the search group page.
    $form['search_input']['#default_value'] = \Drupal::routeMatch()
      ->getParameter('keys');

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_route = \Drupal::routeMatch()->getRouteName();
    $route_parts = explode('.', $current_route);
    if (empty($form_state->getValue('search_input'))) {
      // Redirect to the search page with empty search values.
      $new_route = "view.{$route_parts[1]}.page_no_value";
      $search_group_page = Url::fromRoute($new_route);
    }
    else {
      // Redirect to the search page with filters in the GET parameters.
      $search_input = $form_state->getValue('search_input');
      $new_route = "view.{$route_parts[1]}.page";
      $search_group_page = Url::fromRoute($new_route, array('keys' => $search_input));
    }
    $redirect_path = $search_group_page->toString();

    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());

    $redirect = Url::fromUserInput($redirect_path, array('query' => $query));

    $form_state->setRedirectUrl($redirect);
  }

}
