<?php

/**
 * @file
 * Contains \Drupal\social_search\Form\SearchContentForm.
 */

namespace Drupal\social_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class SearchContentForm.
 *
 * @package Drupal\social_search\Form
 */
class SearchContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search_input'] = array(
      '#type' => 'textfield',
      '#title' => t('Search the entire website'),
    );
    // Prefill search input if we are on search content page.
    if (\Drupal::routeMatch()->getRouteName() == 'view.search_content.page') {
      // As function arg() is deprecated this is the only way to retrieve
      // an array which contains the path pieces.
      // See: https://www.drupal.org/node/2274705
      $current_path = \Drupal::service('path.current')->getPath();
      $path_args = explode('/', $current_path);
      if(!empty($path_args[3])){
        $form['search_input']['#default_value'] = $path_args[3];
      }
    }

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
    // Redirect to the search content page with filters in the GET parameters.
    $search_content_page = Url::fromRoute('view.search_content.page');
    $search_input = $form_state->getValue('search_input');
    $redirect_path = $search_content_page->toString() . '/' . $search_input;

    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());

    $redirect = Url::fromUserInput($redirect_path, array('query' => $query));

    $form_state->setRedirectUrl($redirect);
  }
}
