<?php

namespace Drupal\gsuite\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Google Client Factory class.
 */
class ClientFactory {

  use StringTranslationTrait;

  /**
   * The settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ClientFactory constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    Settings $settings,
    StateInterface $state,
    MessengerInterface $messenger
  ) {
    $this->settings = $settings;
    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('settings'),
      $container->get('state'),
      $container->get('messenger')
    );
  }

  /**
   * Get the Google client.
   *
   * @return \Google\Client
   *   Google client.
   *
   * @throws \Google\Exception
   */
  public function getClient() {
    $client = $this->getPreConfiguredClient();

    $token = $this->state->get('gsuite_token');

    if (!$token) {
      return NULL;
    }

    $accessToken = Json::decode($token);
    $client->setAccessToken($accessToken);

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
      // Refresh the token if possible.
      if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      }
    }

    return $client;
  }

  /**
   * Redirects to Google for the purpose of granting authorization to this app.
   *
   * @return string
   *   The redirect URL.
   */
  public function authorize(): string {
    $client = $this->getPreConfiguredClient();

    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();

    return $authUrl;
  }

  /**
   * Set the access token as an STATE.
   */
  public function setToken(string $token): void {
    try {
      $token = trim($token);
      $client = $this->getPreConfiguredClient();

      // Exchange authorization code for an access token.
      $accessToken = $client->fetchAccessTokenWithAuthCode($token);
      $client->setAccessToken($accessToken);

      // Check to see if there was an error.
      if (array_key_exists('error', $accessToken)) {
        throw new \Exception(implode(', ', $accessToken));
      }

      $this->state->set('gsuite_token', Json::encode($accessToken));
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($this->t("Could now write Google's access token file. Here are some technical details: @details", [
        '@details' => $e->getMessage(),
      ]), $this->messenger::TYPE_ERROR);
    }
  }

  /**
   * Get setting using key from settings variable.
   *
   * @param string $key
   *   The key of the setting defined under 'gsuite' parent array.
   *
   * @return bool|string
   *   Either the value of false.
   */
  protected function getSetting(string $key) {
    $settings = $this->settings->get('gsuite');
    if (is_string($settings)) {
      $settings = Json::decode($settings);
    }
    if (array_key_exists($key, $settings) == FALSE) {
      return FALSE;
    }

    return $settings[$key];
  }

  /**
   * Get setting using key from settings variable.
   *
   * @return bool|string
   *   Either the value of false.
   */
  protected function getSettings() {
    $settings = $this->settings->get('gsuite');
    if (is_string($settings)) {
      $settings = Json::decode($settings);
    }

    return $settings;
  }

  /**
   * Gets a basic, pre-configured Google Client object.
   *
   * @return \Google\Client
   *   The pre-configured Google Client object.
   */
  protected function getPreConfiguredClient(): Client {
    $client = new Client();
    $client->setApplicationName('GSuite - Drupal Module');
    $client->setScopes([
      Drive::DRIVE,
      Sheets::SPREADSHEETS,
    ]);

    $client->setAuthConfig($this->getSettings());
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    return $client;
  }

}
