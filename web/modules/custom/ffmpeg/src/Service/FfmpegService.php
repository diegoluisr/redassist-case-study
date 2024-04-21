<?php

namespace Drupal\ffmpeg\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a repository for Block config entities.
 */
class FfmpegService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The module hanbdler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * The lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * B2cHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   */
  public function __construct(
    ConfigFactory $config,
    ModuleHandlerInterface $moduleHandler,
    FileSystemInterface $fileSystem,
    LockBackendInterface $lock,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->config = $config;
    $this->moduleHandler = $moduleHandler;
    $this->fileSystem = $fileSystem;
    $this->settings = $config->get('ffmpeg.settings');
    $this->lock = $lock;
    $this->logger = $logger;
  }

  /**
   * Function to create an OTP as mp3 file.
   */
  public function otp($code) {

    $rawNumbers = str_split($code);
    $numbers = [];
    foreach ($rawNumbers as $number) {
      if (strpos('0123456789', $number) !== FALSE) {
        $numbers[] = $number;
      }
    }

    $code = 's' . implode($numbers) . 'r' . implode($numbers);
    $chars = str_split($code);
    $filename = '';

    $sources = [
      '0' => '0.mp3',
      '1' => '1.mp3',
      '2' => '2.mp3',
      '3' => '3.mp3',
      '4' => '4.mp3',
      '5' => '5.mp3',
      '6' => '6.mp3',
      '7' => '7.mp3',
      '8' => '8.mp3',
      '9' => '9.mp3',
      's' => 'saludo.mp3',
      'r' => 'te-lo-repito.mp3',
    ];

    $files = [];
    $basePath = $this->moduleHandler->getModule('ffmpeg')->getPath() . '/assets/audio/';
    foreach ($chars as $char) {
      if (isset($sources[$char])) {
        $files[] = $basePath . $sources[$char];
        $filename .= $char;
      }
    }

    $target = rtrim($this->settings->get('target'), '/');
    $rawTargetParts = explode('/', $target);
    $targetParts = [];

    foreach ($rawTargetParts as $part) {
      if ($part !== '/') {
        $targetParts[] = $part;
        $folder = $this->fileSystem->realpath('public://' . implode('/', $targetParts));
        $this->fileSystem->prepareDirectory(
          $folder,
          $this->fileSystem::CREATE_DIRECTORY | $this->fileSystem::MODIFY_PERMISSIONS
        );
      }
    }

    $filename = $this->fileSystem->realpath('public://' . implode('/', $targetParts)) . '/' . $filename . '.mp3';

    if (!file_exists($filename)) {
      $this->concat($files, $filename);
    }

    return $filename;
  }

  /**
   * Function to create contract.
   */
  public function concat($files = [], $target = NULL) {
    $binary = $this->settings->get('binary');

    $resources = '';
    // -i /app/web/modules/custom/ffmpeg/assets/audio/1.mp3
    $filters = " -filter_complex '";
    // -filter_complex '[0:0][1:0][2:0]concat=n=3:v=0:a=1[out]' -map '[out]'
    $i = 0;
    foreach ($files as $file) {
      $resources .= ' -i ' . $file;
      $filters .= "[{$i}:0]";
      $i++;
    }
    $filters .= "concat=n={$i}:v=0:a=1[out]' -map '[out]' ";

    $command = $binary . $resources . $filters . $target;

    $lock = $this->lock;
    if ($lock->acquire(__FILE__) !== FALSE) {
      shell_exec($command);
      $lock->release(__FILE__);
    }
    else {
      while ($lock->acquire(__FILE__) === FALSE) {
        $lock->wait(__FILE__, 3);
      }
      if ($lock->acquire(__FILE__) !== FALSE) {
        shell_exec($command);
        $lock->release(__FILE__);
      }
    }
  }

}
