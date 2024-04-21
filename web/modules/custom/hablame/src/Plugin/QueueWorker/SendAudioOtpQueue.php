<?php

namespace Drupal\hablame\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\hablame\Service\HablameService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Tasks for Learning.
 *
 * @QueueWorker(
 *   id = "hablame_send_audio_otp_queue",
 *   title = @Translation("Hablame worker: Audio OTP callblasting"),
 *   cron = {"time" = 30}
 * )
 */
class SendAudioOtpQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Var that store the hablame service.
   *
   * @var \Drupal\hablame\Service\HablameService
   */
  protected $hablame;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HablameService $hablame, LoggerChannelFactoryInterface $loggerInterface) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->hablame = $hablame;
    $this->logger = $loggerInterface->get('hablame');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates this class.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hablame.service'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    if (isset($data['phone']) && isset($data['otp'])) {
      $this->hablame->audioOtp($data['otp'], $data['phone']);
    }
    else {
      $this->logger->get('hablame')->notice('Hablame - callblasting no enviado: ' . print_r($data, TRUE));
    }

  }

}
