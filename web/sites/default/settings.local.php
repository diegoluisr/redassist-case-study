<?php

switch($_ENV['ENVIRONMENT']) {
  case 'lando':
    include $app_root . '/' . $site_path . '/lando.settings.php';
    break;

  case 'prod':
    include $app_root . '/' . $site_path . '/prod.settings.php';
    break;
}
