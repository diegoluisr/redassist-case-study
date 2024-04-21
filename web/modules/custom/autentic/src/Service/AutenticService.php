<?php

namespace Drupal\autentic\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a repository for Block config entities.
 */
class AutenticService {

  const AUTH_PATH_PROD = 'https://fabricacredito.auth0.com/';
  const AUTH_PATH_STAGE = 'https://stag-signingcore-fabricacredito.us.auth0.com/';
  const API_PATH_PROD = 'https://api.autenticsign.com/v1/';
  const API_PATH_STAGE = 'https://stag-api.autenticsign.com/v1/';
  const OTP_PATH_PROD = 'https://fd3su7s5q0.execute-api.us-east-1.amazonaws.com/prod/';
  const OTP_PATH_STAGE = 'https://j4dmk2yez8.execute-api.us-east-1.amazonaws.com/test/';
  const AUDIENCE_PROD = 'api.autenticsign.com';
  const AUDIENCE_STAGE = 'stag-api.autenticsign.com';
  const CREDENTIALS = 'autentic.credentials';
  // Responses.
  const OTP_DOESNT_EXISTS = 'otp_doesnt_exits';
  const OTP_VALIDATION_ERROR = 'otp_validation_error';
  const OTP_SERVER_FAILS = 'otp_server_fails';

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
   * The config.
   *
   * @var array
   */
  protected $credentials;

  /**
   * Var that store the entityTypemanager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Var that holds an HTTP client.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClient;

  /**
   * Var that store the logger for the channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The variable that obtains the credentials.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Var that holds the time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * B2cHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The var that store the entityManager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Http\ClientFactory $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The var for the logger.
   * @param \Drupal\Core\State\StateInterface $state
   *   The variable that obtains the credentials.
   * @param Drupal\Component\Datetime\TimeInterface $time
   *   The var for the time.
   */
  public function __construct(
    ConfigFactory $config,
    EntityTypeManagerInterface $entityTypeManager,
    FileSystemInterface $fileSystem,
    ClientFactory $httpClient,
    LoggerChannelFactoryInterface $logger,
    StateInterface $state,
    TimeInterface $time
    ) {
    $this->config = $config;
    $this->settings = $config->get('autentic.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->httpClient = $httpClient;
    $this->logger = $logger;
    $this->state = $state;
    $this->time = $time;

    $this->authenticate();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('http_client_factory'),
      $container->get('logger.factory'),
      $container->get('state'),
      $container->get('datetime.time')
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

      case 'api_path':
        $string = ($test === TRUE) ? self::API_PATH_STAGE : self::API_PATH_PROD;
        break;

      case 'otp_path':
        $string = ($test === TRUE) ? self::OTP_PATH_STAGE : self::OTP_PATH_PROD;
        break;

      case 'audience':
        $string = ($test === TRUE) ? self::AUDIENCE_STAGE : self::AUDIENCE_PROD;
        break;
    }
    return $string;
  }

  /**
   * Function to authenticate.
   */
  public function authenticate() {

    $this->credentials = $this->state->get(self::CREDENTIALS, NULL);

    // $currentTime = \Drupal::time()->getCurrentTime();
    $currentTime = $this->time->getCurrentTime();
    if (
      $this->credentials !== NULL &&
      isset($this->credentials['expires_at']) &&
      $currentTime < intval($this->credentials['expires_at'])
    ) {
      return;
    }

    // $client = \Drupal::service('http_client_factory')->fromOptions([
    // 'base_uri' => $this->getString('auth_path'),
    // ]);
    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient->fromOptions([
      'base_uri' => $this->getString('auth_path'),
    ]);

    $data = [
      'audience' => $this->getString('audience'),
      'grant_type' => 'client_credentials',
      'client_id' => $this->settings->get('client_id'),
      'client_secret' => $this->settings->get('client_secret'),
    ];

    try {
      $response = $client->post('oauth/token', [
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => $data,
      ]);

      $this->credentials = Json::decode($response->getBody());
      $this->credentials['expires_at'] = $currentTime + intval($this->credentials['expires_in']);
      // \Drupal::state()->set(self::CREDENTIALS, $this->credentials);
      $this->state->set(self::CREDENTIALS, $this->credentials);
    }
    catch (ClientException $exception) {
      // \Drupal::logger('autentic')->error($exception->getMessage());
      $this->logger->get('autentic')->error($exception->getMessage());
    }
  }

  /**
   * Function to sign documentos.
   */
  public function documentSignature(array $metadata, array $files) {
    if (
      array_key_exists('names', $metadata) &&
      array_key_exists('lastNames', $metadata) &&
      array_key_exists('docId', $metadata) &&
      count($files) > 0
    ) {
      $this->authenticate();
      // $client = \Drupal::service('http_client_factory')->fromOptions([
      // 'base_uri' => $this->getString('api_path'),
      // ]);
      $client = $this->httpClient->fromOptions([
        'base_uri' => $this->getString('api_path'),
      ]);

      $imploded = implode('_', $metadata);
      $metadata['secureKey'] = $imploded . '_' . Crypt::hashBase64($imploded);

      $data = [
        'metadata' => $metadata,
        'files' => $files,
      ];

      try {
        $response = $client->post('signature/', [
          'headers' => [
            'Accept' => 'application/json',
            'Authorization' => $this->credentials['token_type'] . ' ' . $this->credentials['access_token'],
          ],
          'json' => $data,
        ]);
        $result = Json::decode($response->getBody());
        return $result;
      }
      catch (ClientException $exception) {
        $this->logger->get('autentic')->error($exception->getMessage());
      }
      catch (ServerException $serverException) {
        $this->logger->get('autentic')->error($serverException->getMessage());
      }
    }
    return FALSE;
  }

  /**
   * Function to get a file as an bites array.
   */
  public function getFileBitesArray($fileId) {
    /** @var \Drupal\file\FileStorageInterface $fileStorage */
    $fileStorage = $this->entityTypeManager->getStorage('file');

    /** @var \Drupal\file\Entity\File $file */
    $file = $fileStorage->load($fileId);

    try {
      if ($file !== NULL && $file->getMimeType() == 'application/pdf') {
        $realpath = $this->fileSystem->realpath($file->getFileUri());
        $resource = fopen($realpath, "r");
        $bytes = fread($resource, filesize($realpath));
        fclose($resource);

        if ($bytes !== FALSE) {
          return [
            'fileName' => $file->getFilename(),
            'fileContent' => array_values(unpack("c*", $bytes)),
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->get('autentic')->error('Error opening PDF file' . $e);
    }

    return [];
  }

  /**
   * Function to request an OTP.
   */
  public function generateOtp() {
    // $client = \Drupal::service('http_client_factory')->fromOptions([
    // 'base_uri' => $this->getString('otp_path'),
    // ]);
    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient->fromOptions([
      'base_uri' => $this->getString('otp_path'),
    ]);
    try {
      $response = $client->get('v2/autentic/generateotp', [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => $this->settings->get('otp_token'),
        ],
      ]);
      $result = Json::decode($response->getBody());
      $this->logger->get('autentic')->info('generateOtp: ' . print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $clientException) {
      $this->logger->get('autentic')->error($clientException->getMessage());
    }
    catch (ServerException $serverException) {
      $this->logger->get('autentic')->error($serverException->getMessage());
    }
    return FALSE;
  }

  /**
   * Function to request an OTP by email.
   */
  public function generateEmailOtp($destinationMail) {
    // $client = \Drupal::service('http_client_factory')->fromOptions([
    // 'base_uri' => $this->getString('otp_path'),
    // ]);
    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient->fromOptions([
      'base_uri' => $this->getString('otp_path'),
    ]);

    $data = [
      'destinationMail' => $destinationMail,
    ];

    try {
      $response = $client->post('v2/autentic/mail/generateotp', [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => $this->settings->get('otp_token'),
        ],
        'json' => $data,
      ]);
      $result = Json::decode($response->getBody());
      // \Drupal::logger('autentic')->info(print_r($result, TRUE));
      $this->logger->get('autentic')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      // \Drupal::logger('autentic')->error($exception->getMessage());
      $this->logger->get('autentic')->error($exception->getMessage());
    }
    catch (ServerException $serverException) {
      // \Drupal::logger('autentic')->error($serverException->getMessage());
      $this->logger->get('autentic')->error($serverException->getMessage());
    }
    return FALSE;
  }

  /**
   * Function to validate an OTP.
   */
  public function validateOneTimePassword($code) {
    // $client = \Drupal::service('http_client_factory')->fromOptions([
    // 'base_uri' => $this->getString('otp_path'),
    // ]);
    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient->fromOptions([
      'base_uri' => $this->getString('otp_path'),
    ]);

    try {
      $response = $client->get('v2/autentic/validateotp/' . $code, [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => $this->settings->get('otp_token'),
        ],
      ]);
      $result = Json::decode($response->getBody());
      $this->logger->get('autentic')->info(print_r($result, TRUE));
      return $result;
    }
    catch (ClientException $exception) {
      $this->logger->get('autentic')->error($exception->getMessage());
      return $this::OTP_DOESNT_EXISTS;
    }
    catch (ServerException $serverException) {
      $this->logger->get('autentic')->error($serverException->getMessage());
      return $this::OTP_SERVER_FAILS;
    }
    return $this::OTP_VALIDATION_ERROR;
  }

}
