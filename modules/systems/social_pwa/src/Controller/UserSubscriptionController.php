<?php

namespace Drupal\social_pwa\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\social_pwa\BrowserDetector;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserSubscriptionController.
 *
 * @package Drupal\social_pwa\Controller
 */
class UserSubscriptionController extends ControllerBase {

  /**
   * Save or update the subscription data for the user.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return response.
   */
  public function saveSubscription() {
    // The user id.
    $uid = \Drupal::currentUser()->id();
    // The user agent.
    $ua = $_SERVER['HTTP_USER_AGENT'];
    // Get the data related to the user agent.
    $bd = new BrowserDetector($ua);

    // Decode the content.
    $subscription_data = json_decode(\Drupal::request()->getContent(), TRUE);

    // Prepare an array with the browser name and put in the subscription.
    $subscription_data['browser'] = $bd->getFormattedDescription();

    // Get the user data.
    $user_subscriptions = \Drupal::service('user.data')->get('social_pwa', $uid, 'subscription');

    // Make sure that is array to prevent any incorrect records into DB.
    if (!is_array($user_subscriptions)) {
      $user_subscriptions = [];
    }

    // If we have a key we can save the subscription.
    if (isset($subscription_data['key'])) {
      $user_subscriptions[$subscription_data['key']] = $subscription_data;

      // Save the subscription.
      \Drupal::service('user.data')->set('social_pwa', $uid, 'subscription', $user_subscriptions);
    }

    return new Response();
  }

  /**
   * Remove the subscription from the database.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns an ajax response with redirect.
   */
  public function removeSubscription() {
    // The user id.
    $uid = \Drupal::currentUser()->id();

    // Decode the content.
    $subscription_data = json_decode(\Drupal::request()->getContent(), TRUE);

    // Get the user data.
    $user_subscriptions = \Drupal::service('user.data')->get('social_pwa', $uid, 'subscription');

    // Remove a subscription if we have a key.
    if (is_array($user_subscriptions) && isset($subscription_data['key'])) {
      unset($user_subscriptions[$subscription_data['key']]);

      // Delete the subscription.
      \Drupal::service('user.data')->set('social_pwa', $uid, 'subscription', $user_subscriptions);
    }

    return new AjaxResponse(NULL, 200);
  }

}
