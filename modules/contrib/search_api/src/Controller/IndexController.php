<?php

namespace Drupal\search_api\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for search indexes.
 */
class IndexController extends ControllerBase {

  /**
   * Displays information about a search index.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(IndexInterface $search_api_index) {
    // Build the search index information.
    $render = array(
      'view' => array(
        '#theme' => 'search_api_index',
        '#index' => $search_api_index,
      ),
    );
    // Check if the index is enabled and can be written to.
    if ($search_api_index->status() && !$search_api_index->isReadOnly()) {
      // Attach the index status form.
      $render['form'] = $this->formBuilder()->getForm('Drupal\search_api\Form\IndexStatusForm', $search_api_index);
    }
    return $render;
  }

  /**
   * Returns the page title for an index's "View" tab.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(IndexInterface $search_api_index) {
    return new FormattableMarkup('@title', array('@title' => $search_api_index->label()));
  }

  /**
   * Enables a search index without a confirmation form.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index to be enabled.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to send to the browser.
   */
  public function indexBypassEnable(IndexInterface $search_api_index) {
    // Enable the index.
    $search_api_index->setStatus(TRUE)->save();

    // \Drupal\search_api\Entity\Index::preSave() doesn't allow an index to be
    // enabled if its server is not set or disabled.
    if ($search_api_index->status()) {
      // Notify the user about the status change.
      drupal_set_message($this->t('The search index %name has been enabled.', array('%name' => $search_api_index->label())));
    }
    else {
      // Notify the user that the status change did not succeed.
      drupal_set_message($this->t('The search index %name could not be enabled. Check if its server is set and enabled.', array('%name' => $search_api_index->label())));
    }

    // Redirect to the index's "View" page.
    $url = $search_api_index->toUrl('canonical');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Removes a field from a search index.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The search index.
   * @param string $field_id
   *   The ID of the field to remove.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to send to the browser.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the field was not found.
   */
  public function removeField(IndexInterface $search_api_index, $field_id) {
    $fields = $search_api_index->getFields();
    if (isset($fields[$field_id])) {
      try {
        $search_api_index->removeField($field_id);
        $search_api_index->save();
      }
      catch (SearchApiException $e) {
        $args['%field'] = $fields[$field_id]->getLabel();
        drupal_set_message($this->t('The field %field is locked and cannot be removed.', $args), 'error');
      }
    }
    else {
      throw new NotFoundHttpException();
    }

    // Redirect to the index's "View" page.
    $url = $search_api_index->toUrl('fields');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

}
