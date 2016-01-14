<?php

/**
 * @file
 * Contains \Drupal\devel_node_access\Form\DnaForm.
 */

namespace Drupal\devel_node_access\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\devel_node_access\Plugin\Block\DnaBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the form to change the DNA settings.
 */
class DnaForm extends FormBase {

  /**
   * Constructs a new DnaForm object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_dna_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached'] = array(
      'library' =>  array(
        'devel_node_access/devel_node_access'
      ),
    );

    $debug_mode = \Drupal::config('devel_node_access.settings')->get('debug_mode');
    $by_user_mode = \Drupal::config('devel_node_access.settings')->get('by_user_mode');

    $form['node_content'] = array(
      '#prefix' => '<div id="devel-node-access-node-content-div">',
      '#suffix' => '</div>',
    );
    $form['node_content'][0] = DnaBlock::buildNodeInfo($debug_mode);

    $form['user_content'] = array(
      '#prefix' => '<div id="devel-node-access-by-user-content-div">',
      '#suffix' => '</div>',
    );
    if ($by_user_mode) {
      $form['user_content'][0] =  DnaBlock::buildByUserInfo();
    }

    $form['setup'] = array(
      '#markup' => t('Enable:'),
      '#prefix' => '<div class="devel-node-access-inline">',
      '#suffix' => '</div>',
    );
    $form['setup']['debug_mode'] = array(
      '#type' => 'checkbox',
      '#value' => $debug_mode,
      '#prefix' => ' &nbsp; &nbsp; ',
      '#title' => t('Debug Mode'),
      '#ajax' => array(
        'callback' => '::toggleDebugMode',
        'wrapper' => 'devel-node-access-node-content-div',
      ),
      '#disabled' => TRUE,
    );
    $form['setup']['by_user_mode'] = array(
      '#type' => 'checkbox',
      '#value' => $by_user_mode,
      '#prefix' => ' &nbsp; &nbsp; ',
      '#title' => t('By-User Analysis (slow!)'),
      '#ajax' => array(
        'callback' => '::toggleByUserMode',
        'wrapper' => 'devel-node-access-by-user-content-div',
      ),
      '#disabled' => TRUE,
    );

    return $form;
  }

  /**
   * AJAX handler for form_state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function toggleDebugMode($form, FormStateInterface $form_state) {
    $debug_mode = $form_state->getUserInput();
    $debug_mode = !empty($debug_mode['debug_mode']);
    \Drupal::configFactory()->getEditable('devel_node_access.settings')->set('debug_mode', $debug_mode)->save(TRUE);
    $form['node_content'][0] = DnaBlock::buildNodeInfo($debug_mode);
    return $form['node_content'];
  }

  /**
   * AJAX handler for by_user_state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function toggleByUserMode($form, FormStateInterface $form_state) {
    $by_user_mode = $form_state->getUserInput();
    $by_user_mode = !empty($by_user_mode['by_user_mode']);
    \Drupal::configFactory()->getEditable('devel_node_access.settings')->set('by_user_mode', $by_user_mode)->save(TRUE);
    $form['user_content'][0] = ($by_user_mode ? DnaBlock::buildByUserInfo() : []);
    return $form['user_content'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
