<?php

namespace Drupal\social_event_managers\Form;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\views_bulk_operations\Form\ConfigureAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action configuration form.
 */
class SocialEventManagementViewsBulkOperationsConfigureAction extends ConfigureAction {

  /**
   * The Drupal module handler service.
   */
  protected ModuleHandler $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'event_manage_enrollments', $display_id = 'page_manage_enrollments') {
    $data = $this->getTempstoreData($view_id, $display_id);
    // Filter enrollees that have disabled bulk mailing based on their profile
    // settings.
    // We need to remove the selected users from temporary data and update
    // form data passed through the further forms.
    if (!empty($data['list'])) {
      $origin = $data['list'];
      // The list contains selected enrollments.
      foreach ($data['list'] as $key => $item) {
        if (!in_array('event_enrollment', $item)) {
          continue;
        }
        // First element is the enrollment ID.
        $eid = $item[0];
        $enrollment = EventEnrollment::load($eid);
        if (!$enrollment) {
          continue;
        }

        $account = $enrollment->getAccountEntity();

        // Check user frequency settings for event bulk mailing.
        // If a user has disabled mailing, we remove enrollment from
        // the selected list.
        // Only authenticated users have frequency settings.
        if ($account && $account->isAuthenticated() && $this->moduleHandler->moduleExists('activity_send_email')) {
          $frequency = SendActivityDestinationBase::getSendUserSettings(destination: 'email', account: $account, type: 'bulk_mailing');
          if (!empty($frequency['event_enrollees']) && $frequency['event_enrollees'] === FREQUENCY_NONE) {
            unset($data['list'][$key]);
          }
        }
      }

      if ($removed = array_diff_key($origin, $data['list'])) {
        $this->messenger()->addWarning($this->t('Your email will be sent to members who have opted to receive community updates and announcements'));
        $this->messenger()->addWarning($this->formatPlural(count($removed),
          "1 member won't receive this email due to their communication preferences.",
          "@count members won't receive this email due to their communication preferences."
        ));
        $this->setTempstoreData($data, $view_id, $display_id);
        $this->addListData($data);
      }
    }

    return parent::buildForm($form, $form_state, $view_id, $display_id);
  }

}
