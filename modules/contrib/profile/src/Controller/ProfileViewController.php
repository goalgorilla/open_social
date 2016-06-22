<?php

/**
 * @file
 * Contains \Drupal\profile\Controller\ProfileViewController.
 */

namespace Drupal\profile\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single profile entity.
 */
class ProfileViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $profile, $view_mode = 'full', $langcode = NULL) {
    $build = [
      'profiles' => \Drupal::entityTypeManager()
        ->getViewBuilder($profile->getEntityTypeId())
        ->view($profile, $view_mode, $langcode),
    ];
    $build['#title'] = $profile->label();

    foreach ($profile->uriRelationships() as $rel) {
      // Set the profile path as the canonical URL to prevent duplicate content.
      $build['#attached']['html_head_link'][] = [
        [
          'rel' => $rel,
          'href' => $profile->toUrl($rel)->toString(),
        ],
        TRUE,
      ];

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => 'shortlink',
            'href' => $profile->toUrl($rel, ['alias' => TRUE])->toString(),
          ],
          TRUE,
        ];
      }
    }
    return $build;
  }

  /**
   * The _title_callback for the page that renders a profile entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $profile
   *   The current profile.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $profile) {
    return $this->entityManager->getTranslationFromContext($profile)->label();
  }

}
