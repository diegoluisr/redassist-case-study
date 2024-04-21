<?php

namespace Drupal\gsuite\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Abstract GSuite service class.
 *
 * @package Drupal\gsuite\Service
 */
abstract class AbstractGsuiteService {

  /**
   * Client Factory.
   *
   * @var \Drupal\gsuite\ClientFactory
   */
  protected $clientFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Gmail Service.
   *
   * @var \Google_Service_Gmail
   */
  protected $service;

  /**
   * Array of available scopes.
   *
   * @var array
   */
  protected $availableScopes;

  /**
   * Service class constructor.
   *
   * @param \Drupal\gsuite\ClientFactory $clientFactory
   *   Google client factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger factory.
   */
  public function __construct(ClientFactory $clientFactory, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->clientFactory = $clientFactory;
    $this->logger = $loggerChannelFactory->get('gsuite');
  }

  /**
   * Get designated Gsuite service.
   *
   * @param string|null $userId
   *   The ID of tthe user to impersonate.
   *
   * @return mixed
   *   Service object.
   */
  abstract protected function getService($userId = NULL);

  /**
   * Return default scopes.
   *
   * @return array
   *   Array of default scopes.
   */
  abstract protected function getDefaultScopes();

  /**
   * Get Gsuite client.
   *
   * @param string|null $subject
   *   An email address account to impersonate.
   *
   * @return \Google_Client
   *   Google Client.
   *
   * @throws \Google_Exception
   *   Exception.
   */
  protected function getClient($subject = NULL) {
    $client = $this->clientFactory->getClient();

    $client->addScope($this->getScopes());

    if ($subject != NULL) {
      $client->setSubject($subject);
    }

    return $client;
  }

  /**
   * Get scopes.
   *
   * @return array
   *   Array of scopes.
   */
  protected function getScopes() {
    if (empty($this->availableScopes)) {
      return $this->getDefaultScopes();
    }

    return $this->availableScopes;
  }

  /**
   * Set available scopes.
   *
   * @param string $scope
   *   Scope to set.
   */
  protected function setScopes($scope) {
    $this->availableScopes[] = $scope;
  }

}
