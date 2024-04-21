<?php

namespace Drupal\gsuite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Laminas\Diactoros\Response\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for handling the connection to Google APIs.
 */
class GSuiteController extends ControllerBase {

  /**
   * The `Auth Code` url parameter name.
   *
   * @var string
   */
  protected const AUTH_CODE_PARAMETER_NAME = 'code';

  /**
   * The @messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The @request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The @gsuite.google_drive_api service.
   *
   * @var \Drupal\gsuite\Service\GoogleDriveApi
   */
  protected $googleApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->googleApi = $container->get('gsuite.client.factory');

    return $instance;
  }

  /**
   * Connect.
   *
   * @return string
   *   Return Hello string.
   */
  public function connect() {
    if ($auth_code = $this->request->query->get(self::AUTH_CODE_PARAMETER_NAME)) {
      $this->googleApi->setToken($auth_code);

      $this->messenger->addMessage($this->t('You have successfully connected your Google Account.'));

      return new RedirectResponse(Url::fromRoute('gsuite.form.settings')->toString());
    }
  }

}
