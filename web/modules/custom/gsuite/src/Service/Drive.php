<?php

namespace Drupal\gsuite\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Google\Service\Drive as ServiceDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;

/**
 * Gmail service class.
 */
class Drive extends AbstractGsuiteService {

  use StringTranslationTrait;

  const SPREEDSHEET_MIMETYPE = 'application/vnd.google-apps.spreadsheet';

  /**
   * {@inheritdoc}
   */
  protected function getDefaultScopes() {
    return [
      ServiceDrive::DRIVE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getService($userId = NULL) {
    if ($this->service instanceof ServiceDrive) {
      return $this->service;
    }

    $this->service = new ServiceDrive($this->getClient($userId));

    return $this->service;
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $folderName
   *   The name of the folder to create.
   * @param string $parentFolderId
   *   The ID of the parent folder.
   *
   * @return string
   *   The created folder's ID.
   */
  public function createFolder(string $folderName, string $parentFolderId = NULL): string {
    try {
      $folder = new DriveFile();
      $folder->setName($folderName);
      $folder->setMimeType('application/vnd.google-apps.folder');

      if (!is_null($parentFolderId)) {
        $folder->setParents([$parentFolderId]);
      }

      // $owner = new Permission();
      // $owner->setEmailAddress('jabonillac@gmail.com');
      // $owner->setRole('writer');
      // $owner->setType('user');
      // $writer = new \Google_Service_Drive_Permission();
      // $writer->setRole('writer');
      // $writer->setType('domain');
      // $writer->setDomain('syllableit.com');
      $service = $this->getService(NULL);

      $folder = $service->files->create($folder);

      // $service->permissions->create($folder->getId(), $owner);
      return $folder->getId();
    }
    catch (\Throwable $e) {
      $message = new FormattableMarkup("Could not create the \"@folderName\" folder. Here\'s the technical reason why: @reason", [
        '@folderName' => $folderName,
        '@reason' => $e->getMessage(),
      ]);
      throw new \Exception((string) $message);
    }
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $fileId
   *   The ID of the file.
   *
   * @return string
   *   The created folder's ID.
   */
  public function getPermissions(string $fileId = NULL) {
    $service = $this->getService(NULL);
    try {
      $permissions = $service->permissions->listPermissions($fileId);
      if ($permissions != NULL) {
        return $permissions->getPermissions();
      }
    }
    catch (\Exception $e) {
      print "An error occurred: " . $e->getMessage();
    }
    return NULL;
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $folderId
   *   The ID of the parent folder.
   * @param string $folderName
   *   The name of the folder to create.
   *
   * @return mixed[]
   *   The created folder's ID.
   */
  public function listFolders(string $folderId = NULL, string $folderName = NULL): array {
    // https://developers.google.com/drive/api/v3/search-files
    $folders = [];
    $query = "trashed = false AND mimeType = 'application/vnd.google-apps.folder'";

    if (!empty($folderName)) {
      $words = mb_split(' ', $folderName);
      foreach ($words as $word) {
        $query .= " AND name CONTAINS '{$word}'";
      }
    }

    if (!is_null($folderId)) {
      $query .= " AND '{$folderId}' IN parents";
    }
    else {
      $query .= " AND 'root' IN parents";
    }

    $service = $this->getService(NULL);
    $results = $service->files->listFiles([
      'q' => $query,
      'fields' => "files(id,name,mimeType,fileExtension)",
    ]);
    foreach ($results as $folder) {
      $folders[] = [
        'id' => $folder->getId(),
        'name' => $folder->getName(),
        'mimeType' => $folder->getMimeType(),
        'fileExtension' => $folder->getFileExtension(),
      ];
    }
    return $folders;
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $folderId
   *   The ID of the parent folder.
   * @param string $name
   *   The name of the file to create.
   * @param string $type
   *   The type.
   *
   * @return string
   *   The created file's ID.
   */
  public function createFile(string $folderId = NULL, string $name = 'undefined', string $type = NULL): ?string {

    $service = $this->getService(NULL);
    try {
      $googleServiceDriveDriveFile = new DriveFile();
      if (!in_array($type, [self::SPREEDSHEET_MIMETYPE])) {
        return NULL;
      }
      $googleServiceDriveDriveFile->setMimeType($type);
      $googleServiceDriveDriveFile->setName($name);
      if (!is_null($folderId)) {
        $googleServiceDriveDriveFile->setParents([$folderId]);
      }
      $spreadsheet = $service->files->create($googleServiceDriveDriveFile);

      return $spreadsheet->getId();
    }
    catch (\Throwable $e) {
      $message = new FormattableMarkup("Could not create the \"@name\" file. Here\'s the technical reason why: @reason", [
        '@name' => $name,
        '@reason' => $e->getMessage(),
      ]);
      throw new \Exception((string) $message);
    }

    return NULL;
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $fileId
   *   The ID of the file to be readed.
   * @param string $range
   *   The range to be gotten from file.
   *
   * @return mixed[]
   *   The created folder's ID.
   */
  public function read(string $fileId = '', string $range = 'A1'): array {
    $result = $this->googleSheetsService->spreadsheets_values->get($fileId, $range);
    return $result->getValues() ?? [];
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $fileId
   *   The ID of the file to be readed.
   * @param string $range
   *   The range to be gotten from file.
   * @param mixed[] $data
   *   The range to be gotten from file.
   */
  public function write(string $fileId = '', string $range = 'A1', array $data = []): void {
    $service = $this->getService(NULL);

    $sheet = new Sheets($service->getClient());

    $body = new ValueRange();
    $body->setValues($data);

    $params = [
      'valueInputOption' => 'RAW',
    ];
    $sheet->spreadsheets_values->update(
      $fileId,
      $range,
      $body,
      $params
    );
  }

  /**
   * Creates a new folder in Google Drive.
   *
   * @param string $fileId
   *   The ID of the file to be readed.
   * @param string $range
   *   The range to be gotten from file.
   * @param mixed[] $data
   *   The range to be gotten from file.
   */
  public function batch(string $fileId = '', string $range = 'A1', array $data = []): void {
    $service = $this->getService(NULL);

    $sheet = new Sheets($service->getClient());

    $body = new ValueRange();
    $body->setValues($data);

    $batchUpdate = new BatchUpdateValuesRequest();

    $params = [
      'valueInputOption' => 'RAW',
    ];
    $sheet->spreadsheets_values->batchUpdate(
      $fileId,
      $batchUpdate,
      $params
    );
  }

}
