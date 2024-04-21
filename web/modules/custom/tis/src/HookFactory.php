<?php

namespace Drupal\tis;

use Drupal\tis\Hook\Hook;

/**
 * The hook factory.
 */
class HookFactory {

  /**
   * Function comment.
   */
  public function __construct() {

  }

  /**
   * Function comment.
   */
  public static function build(string $hookName) {
    $className = self::getClassName($hookName);
    $class = 'Drupal\\tis\\Hook\\' . $className;

    if (class_exists($class)) {
      $instance = new $class();
      if ($instance instanceof Hook) {
        return $instance;
      }
    }

    return NULL;
  }

  /**
   * Function to conver hook name to class name candidate.
   */
  private static function getClassName(string $hookName) {
    return str_replace('_', '', ucwords($hookName, '_'));
  }

}
