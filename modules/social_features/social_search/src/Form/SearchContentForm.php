<?php

namespace Drupal\social_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SearchContentForm.
 *
 * @package Drupal\social_search\Form
 */
class SearchContentForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SearchHeroForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

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

    $form['search_input_content'] = [
      '#type' => 'textfield',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search Content'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_all_view = 'search_all';
    if (empty($form_state->getValue('search_input_content'))) {
      // Redirect to the search content page with empty search values.
      $search_content_page = Url::fromRoute("view.$search_all_view.page_no_value");
    }
    else {
      // Redirect to the search content page with filters in the GET parameters.
      $search_input = Html::escape($form_state->getValue('search_input_content'));
      $search_input = preg_replace('/[\/]+/', ' ', $search_input);
      $search_content_page = Url::fromRoute("view.$search_all_view.page", ['keys' => $search_input]);
    }
    $redirect_path = $search_content_page->toString();

    $query = UrlHelper::filterQueryParameters($this->requestStack->getCurrentRequest()->query->all());

    $redirect = Url::fromUserInput($redirect_path, ['query' => $query]);

    $form_state->setRedirectUrl($redirect);
  }

}
