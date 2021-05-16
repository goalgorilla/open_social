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

# GitHub Open Social PR flow
![Open Social - Distro flow - Old](https://user-images.githubusercontent.com/16667281/117428390-508d7d00-af26-11eb-8340-115ab04b518e.jpg)

Whenever someone creates a PR within goalgorilla/open_social the following steps are triggered:

## GitHub actions
We use github actions to trigger
1. PHPStan for static analysis of the code being contributed
2. PHPCS for coding standards of the code being contributed

## TravisCI
We use TravisCI to run
3. PHPUnit tests
4. Behat tests

After all checks are completed, including a manual review we can merge our PR's.

**However - We are currently migrating to a new PR flow.**
