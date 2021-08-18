[![Build Status](https://travis-ci.com/goalgorilla/open_social.svg?branch=10.1.x)](https://travis-ci.com/goalgorilla/open_social)
[![Packagist Version](https://img.shields.io/packagist/v/goalgorilla/open_social.svg)](https://packagist.org/packages/goalgorilla/open_social)
[![Twitter Follow](https://img.shields.io/twitter/follow/OpenSocialHQ.svg)](https://twitter.com/OpenSocialHQ)

# Open Social
The install profile for the
<a target="_blank" href="http://www.drupal.org/project/social">Open Social
distribution</a>.

# Quick installation?
We have a template available at
<a target="_blank" href="https://github.com/goalgorilla/social_template/">
goalgorilla/social_template</a>

# Want to help contribute?
Be sure to check out our repository with development tools on
<a target="_blank" href="https://github.com/goalgorilla/drupal_social/">
Github</a>

For any other information be sure to checkout our
<a target="_blank" href="https://www.drupal.org/docs/8/distributions/open-social">
Documentation</a>.

# PR flow in Open Social
![Open Social - Distro flow - New](https://user-images.githubusercontent.com/16667281/117429026-e75a3980-af26-11eb-8cd5-668b9fa61f4b.jpg)

## Automated
Not only will we have manual PR's created, we use [dependabot](https://dependabot.com/) to automatically create dependency updates of the Drupal modules we use.

## Webhook/Mirror [WIP]
We want to see if we can mirror this repository with Drupal's GitLab. To ensure the work we are doing here is also automatically visible on GitLab and if there is any way to do it vice versa, so whenever a merge requests is created on GitLab we also have it in here.

## Tugboat [WIP]
We are testing [Tugboat](https://www.tugboat.qa/) to achieve the following:

1. Create a live preview of every PR with the changes reflected
2. Use this live preview to run Behat tests on
3. Use this live preview to run Cypress tests on

## GitHub actions
We use github actions to trigger
1. PHPStan for static analysis of the code being contributed
2. PHPCS for coding standards of the code being contributed
3. PHPUnit for our unit tests instead of running it in Travis CI 
