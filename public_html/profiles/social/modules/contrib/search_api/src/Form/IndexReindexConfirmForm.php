<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\SearchApiException;

/**
 * Defines a confirm form for reindexing an index.
 */
class IndexReindexConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reindex the search index %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Indexed data will remain on the search server until all items have been reindexed. Searches on this index will continue to yield results. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_api_index.canonical', array('search_api_index' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $entity */
    $entity = $this->getEntity();

    try {
      $entity->reindex();
      drupal_set_message($this->t('The search index %name was successfully reindexed.', array('%name' => $entity->label())));
    }
    catch (SearchApiException $e) {
      drupal_set_message($this->t('Failed to reindex items for the search index %name.', array('%name' => $entity->label())), 'error');
      watchdog_exception('search_api', $e, '%type while trying to reindex items on index %name: @message in %function (line %line of %file)', array('%name' => $entity->label()));
    }

    $form_state->setRedirect('entity.search_api_index.canonical', array('search_api_index' => $entity->id()));
  }

}
