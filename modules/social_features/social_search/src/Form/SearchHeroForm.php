<?php

namespace Drupal\social_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SearchHeroForm.
 *
 * @package Drupal\social_search\Form
 */
class SearchHeroForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SearchHeroForm constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RouteMatchInterface $routeMatch, RequestStack $requestStack) {
    $this->routeMatch = $routeMatch;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

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

    $form['search_input'] = [
      '#type' => 'textfield',
    ];

    // Pre-fill search input on the search group page.
    $form['search_input']['#default_value'] = $this->routeMatch
      ->getParameter('keys');

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['#cache']['contexts'][] = 'url';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_route = $this->routeMatch->getRouteName();
    $route_parts = explode('.', $current_route);
    if (empty($form_state->getValue('search_input'))) {
      // Redirect to the search page with empty search values.
      $new_route = "view.{$route_parts[1]}.page_no_value";
      $search_group_page = Url::fromRoute($new_route);
    }
    else {
      // Redirect to the search page with filters in the GET parameters.
      $search_input = Html::escape($form_state->getValue('search_input'));
      $search_input = preg_replace('/[\/]+/', ' ', $search_input);
      $new_route = "view.{$route_parts[1]}.page";
      $search_group_page = Url::fromRoute($new_route, ['keys' => $search_input]);
    }
    $redirect_path = $search_group_page->toString();

    $query = UrlHelper::filterQueryParameters($this->requestStack->getCurrentRequest()->query->all());

    $redirect = Url::fromUserInput($redirect_path, ['query' => $query]);

    $form_state->setRedirectUrl($redirect);
  }

}
