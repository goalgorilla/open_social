<?php

namespace Drupal\social_gdpr;

use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\data_policy\Entity\DataPolicy;
use Drupal\data_policy\Entity\UserConsentInterface;
use Drupal\user\UserInterface;

/**
 * Implements helper functions for Social GDPR module.
 */
class SocialGDPRHelper {

  /**
   * Submit callback for social_gdpr_form_user_form_alter().
   *
   * User can change non-required consent of policies.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function userChangeDataPoliciesSubmit(array $form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('data_policy');

    if (empty($values)) {
      return;
    }

    /** @var \Drupal\data_policy\DataPolicyConsentManagerInterface $data_policy_manager */
    $data_policy_manager = \Drupal::service('data_policy.manager');

    // Get user that made consents.
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface) {
      $user = $form_object->getEntity();
      if ($user instanceof UserInterface) {
        $uid = $user->id();
      }
    }

    // If user doesn't exist as form object than load current user.
    $uid = (int) ($uid ?? \Drupal::currentUser()->id());

    // Load user consent and make changes if it needs.
    $user_consents_ids = $data_policy_manager->getExistingUserConsents($uid);

    // We need only consents that can be changed.
    if (empty($user_consents_ids)) {
      // Usually, user should make policy agreement on register page, but in
      // case when it appears on settings page, let's add them.
      if (function_exists('_data_policy_user_register_form_submit')) {
        _data_policy_user_register_form_submit($form, $form_state);
        // Nothing to edit, consents were created from scratch.
        return;
      }
    }

    /** @var \Drupal\data_policy\Entity\UserConsent[] $user_consents */
    $user_consents = \Drupal::entityTypeManager()
      ->getStorage('user_consent')
      ->loadMultiple($user_consents_ids);

    foreach ($values as $name => $value) {
      // Load data policy last revision.
      $data_policy_id = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
      $data_policy = DataPolicy::load($data_policy_id);
      if (empty($data_policy)) {
        continue;
      }

      foreach ($user_consents as $consent) {
        // We don't need newly created consent.
        if ($consent->isNew()) {
          continue;
        }

        $data_policy_revision_id = (int) $consent->get('data_policy_revision_id')->getString();
        // Make sure the consent belongs to the current data policy revision.
        if ((int) $data_policy->getRevisionId() !== $data_policy_revision_id) {
          continue;
        }

        $previous_state = (int) $consent->get('state')->getString();
        $agreed = $previous_state === UserConsentInterface::STATE_AGREE;

        // Make sure user made changes otherwise nothing to update.
        if ($agreed === (bool) $value) {
          continue;
        }

        // Why we don't take into account UserConsentInterface::STATE_UNDECIDED?
        // Because here we just edit already existed consents, which mean
        // the user visited policies previously and this state can't
        // be applied anymore.
        $new_state = $value
          ? UserConsentInterface::STATE_AGREE
          : UserConsentInterface::STATE_NOT_AGREE;

        $consent->set('state', $new_state)
          ->save();
      }
    }
  }

}
