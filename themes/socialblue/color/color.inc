<?php

/**
 * @file
 * Lists available colors and color schemes for the Social Blue theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'brand-primary' => t('Primary color'),
    'brand-secondary' => t('Secondary color'),
    'brand-accent' => t('Accent color'),
    'brand-link' => t('Link color'),
    'navbar-bg' => t('Navigation background'),
    'navbar-text' => t('Navigation text'),
    'navbar-active-bg' => t('Navigation active item background'),
    'navbar-active-text' => t('Navigation active item text'),
    'navbar-sec-bg' => t('Section navbar background'),
    'navbar-sec-text' => t('Section navbar text'),
  ],
  // Pre-defined color schemes.
  // Do not use the same color twice as the color module does not
  // know which values to replace then. Make sure all colors match with
  // the colors in the css file below.
  'schemes' => [
    'default' => [
      'title' => t('Social Blue (default)'),
      'colors' => [
        'brand-primary' => '#29abe2',
        'brand-secondary' => '#1f80aa',
        'brand-accent' => '#ffc142',
        'brand-link' => '#33b5e5',
        'navbar-bg' => '#333333',
        'navbar-text' => '#ffffff',
        'navbar-active-bg' => '#1f1f1f',
        'navbar-active-text' => '#f3f3f3',
        'navbar-sec-bg' => '#1f7ea7',
        'navbar-sec-text' => '#f9f9f9',
      ],
    ],
  ],

  'css' => [
    'assets/css/brand.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],

  // Preview files.
  'preview_library' => 'socialblue/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', $theme),
      ],
    ],
  ],
];
