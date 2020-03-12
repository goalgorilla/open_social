<?php

namespace Drupal\social_event\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Class RequestEventEnrollment.
 */
class RequestEventEnrollment extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }
  /**
   * Provides the form for requesting a group membership.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function requestEnrollment(NodeInterface $node) {
  }

  /**
   * Provides the form for approving a requested group membership.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function approveEnrollmentRequest(NodeInterface $node) {
  }

  /**
   * Helper method so we can have consistent dialog options.
   *
   * @return string[]
   *   An array of jQuery UI elements to pass on to our dialog form.
   */
  protected static function getDataDialogOptions() {
    return [
      'dialogClass' => 'form--default',
      'closeOnEscape' => TRUE,
      'width' => '400',
    ];
  }

  /**
   * Enroll dialog callback.
   */
  public function enrollDialog() {
    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    $form = $this->formBuilder->getForm('Drupal\social_event\Form\EnrollRequestModalForm');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->t('Request enrollment'), $form, static::getDataDialogOptions()));

    return $response;
  }

  /**
   * The _title_callback for the event enroll dialog route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @return string
   *   The page title.
   */
  public function enrollTitle(NodeInterface $node) {
    return $this->t('Request enrollment in @label Event', ['@label' => $node->label()]);
  }

  /**
   * Determines if user has access to enroll form.
   */
  public function enrollAccess(NodeInterface $node) {
    return AccessResult::allowed();
  }
}
