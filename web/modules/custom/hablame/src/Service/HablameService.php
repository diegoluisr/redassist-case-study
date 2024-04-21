<?php

namespace Drupal\hablame\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ffmpeg\Service\FfmpegService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a repository for Block config entities.
 */
class HablameService {

  const AUTH_PATH_PROD = 'https://api103.hablame.co/api/';
  const AUTH_PATH_STAGE = 'http://localhost:10001/api/url-shortener/v1';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

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
   * Variable that store the HTTP client.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $client;

  /**
   * Var that store the logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Var that store the ffmpeg service.
   *
   * @var \Drupal\ffmpeg\Service\FfmpegService
   */
  protected $ffmpeg;

  /**
   * B2cHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Http\ClientFactory $client
   *   Var that stores the HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param \Drupal\ffmpeg\Service\FfmpegService $ffmpeg
   *   The ffmpeg service.
   */
  public function __construct(
    ConfigFactory $config,
    FileSystemInterface $fileSystem,
    ClientFactory $client,
    LoggerChannelFactoryInterface $logger,
    FfmpegService $ffmpeg
  ) {
    $this->config = $config;
    $this->fileSystem = $fileSystem;
    $this->settings = $config->get('hablame.settings');
    $this->client = $client;
    $this->logger = $logger;
    $this->ffmpeg = $ffmpeg;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('http_client_factory'),
      $container->get('logger.factory'),
      $container->get('ffmpeg.service')
    );
  }

  /**
   * Function to get service strings.
   */
  private function getString(string $key) {
    $test = boolval($this->settings->get('test'));
    $string = '';
    switch ($key) {
      case 'auth_path':
        $string = ($test === TRUE) ? self::AUTH_PATH_STAGE : self::AUTH_PATH_PROD;
        break;
    }
    return $string;
  }

  /**
   * Function to authenticate.
   */
  public function getShortUrl($url) {

    /** @var \GuzzleHttp\Client $client */
    $client = $this->client->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    $data = [
      'url' => $url,
    ];

    try {
      $response = $client->post('url-shortener/v1/token', [
        'headers' => [
          'Accept' => 'application/json',
          'account' => $this->settings->get('account'),
          'apikey' => $this->settings->get('api_key'),
          'token' => $this->settings->get('token'),
        ],
        'json' => $data,
      ]);

      $result = Json::decode($response->getBody());
      $this->logger->get('hablame')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('hablame')->error($exception->getMessage());
    }
    return FALSE;

  }

  /**
   * Function to authenticate.
   */
  public function sendMessage($phone, $message, $flash = FALSE) {

    /** @var \GuzzleHttp\Client $client */
    $client = $this->client->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    $data = [
      'toNumber' => $phone,
      'sms' => $message,
      'flash' => intval($flash),
    ];

    try {
      $response = $client->post('sms/v3/send/priority', [
        'headers' => [
          'Accept' => 'application/json',
          'account' => $this->settings->get('account'),
          'apikey' => $this->settings->get('api_key'),
          'token' => $this->settings->get('token'),
        ],
        'json' => $data,
      ]);

      $result = Json::decode($response->getBody());
      $this->logger->get('hablame')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('hablame')->error($exception->getMessage());
    }
    return FALSE;

  }

  /**
   * Function to load an audio file.
   */
  public function uploadFile($absoluteFilePath) {
    /** @var \GuzzleHttp\Client $client */
    $client = $this->client->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    try {
      $response = $client->post('callblasting/v1/callblasting/audio_load', [
        'headers' => [
          'Accept' => 'application/json',
          'account' => $this->settings->get('account'),
          'apikey' => $this->settings->get('api_key'),
          'token' => $this->settings->get('token'),
        ],
        'multipart' => [
          [
            'name' => 'audio',
            'contents' => Utils::tryFopen($absoluteFilePath, 'r'),
          ],
        ],
      ]);

      $result = Json::decode($response->getBody());
      $this->logger->get('hablame')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('hablame')->error($exception->getMessage());
    }
    return FALSE;
  }

  /**
   * Function to send audio OTP from audio_id.
   */
  public function audioOtp($code, $phone) {

    $filename = $this->ffmpeg->otp($code);
    $upload = $this->uploadFile($filename);
    if (!isset($upload['audio_id'])) {
      return FALSE;
    }

    /** @var \GuzzleHttp\Client $client */
    $client = $this->client->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    try {
      $this->logger->get('hablame')->info(print_r($upload['audio_id'], TRUE));
      $response = $client->post('callblasting/v1/callblasting/audio_id', [
        'headers' => [
          'Accept' => 'application/json',
          'account' => $this->settings->get('account'),
          'apikey' => $this->settings->get('api_key'),
          'token' => $this->settings->get('token'),
        ],
        'json' => [
          "toNumber" => $phone,
          "audio_id" => $upload['audio_id'],
          "sendDate" => '',
          "attempts" => '2',
          "attempts_delay" => '30',
        ],
      ]);

      $result = Json::decode($response->getBody());
      $this->logger->get('hablame')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('hablame')->error($exception->getMessage());
    }
    return FALSE;

  }

  /**
   * Function to get a message status.
   */
  public function getStatus($smsId) {
    /** @var \GuzzleHttp\Client $client */
    $client = $this->client->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    try {
      $response = $client->get('sms/v3/report/${$smsId}', [
        'headers' => [
          'Accept' => 'application/json',
          'account' => $this->settings->get('account'),
          'apikey' => $this->settings->get('api_key'),
          'token' => $this->settings->get('token'),
        ],
      ]);

      $result = Json::decode($response->getBody());
      $this->logger->get('hablame')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('hablame')->error($exception->getMessage());
    }
    return FALSE;
  }

}
