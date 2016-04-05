<?php

namespace Drupal\facets\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\FacetInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The dropdown widget form.
 */
class DropdownWidgetForm implements BaseFormIdInterface {

  use StringTranslationTrait;

  /**
   * The facet to build the form for.
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
    return 'facets_dropdown_widget';
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
    $results = $this->facet->getResults();

    $configuration = $this->facet->getWidgetConfigs();
    $form[$this->facet->getFieldAlias()] = [
      '#type' => 'select',
      '#title' => $this->facet->getName(),
      '#default_value' => '_none',
    ];

    $options = [];
    $active_result_url = '_none';
    foreach ($results as $result) {
      $result_url = $result->getUrl()->setAbsolute()->toString();

      $text = $result->getDisplayValue();
      if (!empty($configuration['show_numbers'])) {
        $text .= ' (' . $result->getCount() . ')';
      }

      if ($result->isActive()) {
        $options['_none'] = $text;
        $active_result_url = $result_url;
      }
      else {
        $options[$result_url] = $text;
      }
    }

    $options = [$active_result_url => $this->t('- All -')] + $options;

    $form[$this->facet->getFieldAlias()]['#options'] = $options;

    $form[$this->facet->getFieldAlias() . '_submit'] = [
      '#type' => 'submit',
      '#value' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field_alias = $this->facet->getFieldAlias();
    $form_value = $form_state->getValue($field_alias);
    if ($form_value != '_none') {
      $form_state->setResponse(new RedirectResponse($form_value));
    }
  }

}
