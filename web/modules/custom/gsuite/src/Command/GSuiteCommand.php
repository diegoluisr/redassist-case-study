<?php

declare(strict_types = 1);

namespace Drupal\gsuite\Command;

use Drupal\gsuite\Service\Drive;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush command file to test google drive actions.
 *
 * @package Drupal\gsuite\Command
 */
class GSuiteCommand extends DrushCommands {

  /**
   * The @gsuite.drive service.
   *
   * @var \Drupal\gsuite\Service\Drive
   */
  protected Drive $drive;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Drive $drive
  ) {
    $this->drive = $drive;
  }

  /**
   * Drush command to create a folder on Google Drive.
   *
   * @command gsuite:create-folder
   * @aliases gscf
   * @usage gsuite:create-folder parentFolderId name
   */
  public function createFolder(string $name = 'undefined', string $parentFolderId = NULL): string {
    return $this->drive->createFolder($name, $parentFolderId);
  }

  /**
   * Drush command to create a folder on Google Drive.
   *
   * @command gsuite:get-permissions
   * @aliases gsgp
   * @usage gsuite:get-permissions fileId
   */
  public function getPermissions(string $fileId = NULL) {
    $permissions = $this->drive->getPermissions($fileId);
    return print_r($permissions, TRUE);
  }

  /**
   * Drush command to create a folder on Google Drive.
   *
   * @command gsuite:list-folders
   * @option name An option that takes multiple values.
   * @option id An option that takes multiple values.
   * @aliases gslf
   * @usage gsuite:list-folders folderName folderId
   */
  public function listFolders($options = ['name' => '', 'id' => '']) {
    $folders = $this->drive->listFolders(
      !empty($options['id']) ? $options['id'] : NULL,
      !empty($options['name']) ? $options['name'] : NULL,
    );
    return print_r($folders, TRUE);
  }

  /**
   * Drush command to create a folder on Google Drive.
   *
   * @command gsuite:create-spreadsheet
   * @option id An option that takes multiple values.
   * @option name An option that takes multiple values.
   * @aliases gscss
   * @usage gsuite:create-spreadsheet folderName folderId
   */
  public function createSpreadSheet($options = ['id' => '', 'name' => '']) {
    $folders = $this->drive->createFile(
      !empty($options['id']) ? $options['id'] : NULL,
      !empty($options['name']) ? $options['name'] : NULL,
      Drive::SPREEDSHEET_MIMETYPE,
    );
    return print_r($folders, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
      $container->get('gsuite.drive')
    );
  }

}
