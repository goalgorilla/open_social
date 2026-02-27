<?php

namespace Drupal\social_graphql\Exception;

/**
 * This exception indicates that there is a bug in the application.
 *
 * This indicates that something has gone wrong during development and it's not
 * safe to continue.
 */
class ShouldNotHappenException extends \Exception {}
