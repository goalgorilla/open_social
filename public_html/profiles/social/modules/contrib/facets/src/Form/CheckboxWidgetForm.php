<?php

namespace Drupal\facets\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The checkbox / radios widget form.
 */
class CheckboxWidgetForm implements BaseFormIdInterface {

  /**
   * The facet to build the checkbox form for.
   *
   * @var FacetInterface $facet
   */
  protected $facet;

  /**
   * Class constructor.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to build the form for.
   */
  public function __construct(FacetInterface $facet) {
    $this->facet = $facet;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'facets_checkbox_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '__' . $this->facet->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $facet = $this->facet;

    /** @var \Drupal\facets\Result\Result[] $results */
    $results = $facet->getResults();

    $configuration = $facet->getWidgetConfigs();
    $show_numbers = (bool) isset($configuration['show_numbers']) ? $configuration['show_numbers'] : FALSE;
    $form[$facet->getFieldAlias()] = [
      '#type' => 'checkboxes',
      '#title' => $facet->getName(),
    ];

    $options = [];
    foreach ($results as $result) {
      $text = $result->getDisplayValue();
      if ($show_numbers) {
        $text .= ' (' . $result->getCount() . ')';
      }

      $options[$result->getRawValue()] = $text;

      if ($result->isActive()) {
        $form[$facet->getFieldAlias()]['#default_value'][] = $result->getRawValue();
      }
    }

    $form[$facet->getFieldAlias()]['#options'] = $options;

    $form[$facet->id() . '_submit'] = [
      '#type' => 'submit',
      '#value' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $facet = $this->facet;

    $result_link = FALSE;
    $active_items = [];

    foreach ($values[$facet->getFieldAlias()] as $key => $value) {
      if ($value !== 0) {
        $active_items[] = $value;
      }
    }

    foreach ($facet->getResults() as $result) {
      if (in_array($result->getRawValue(), $active_items)) {
        $result_link = $result->getUrl();
      }
    }

    // We have an active item, so we redirect to the page that has that facet
    // selected. This should be an absolute link because RedirectResponse is a
    // symfony class that requires a full URL.
    if ($result_link instanceof Url) {
      $result_link->setAbsolute();
      $form_state->setResponse(new RedirectResponse($result_link->toString()));
      return;
    }

    // The form was submitted but nothing was active in the form, we should
    // still redirect, but the url for the new page can't come from a result.
    // So we're redirecting to the facet source's page.
    $path = $facet->getFacetSource()->getPath();
    if (substr($path, 0, 1) !== '/') {
      $path = '/' . $path;
    }
    $link = Url::fromUserInput($path);
    $link->setAbsolute();
    $form_state->setResponse(new RedirectResponse($link->toString()));
  }

}
