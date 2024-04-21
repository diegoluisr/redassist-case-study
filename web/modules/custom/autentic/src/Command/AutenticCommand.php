<?php

namespace Drupal\autentic\Command;

use Drupal\autentic\Service\AutenticService;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush command file.
 *
 * @package Drupal\autentic\Command
 */
class AutenticCommand extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Variable that holds the autentic service.
   *
   * @var \Drupal\autentic\Service\AutenticService
   */
  protected $autentic;

  /**
   * {@inheritdoc}
   */
  public function __construct(AutenticService $autentic) {
    $this->autentic = $autentic;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('autentic.service')
    );
  }

  /**
   * Drush command that displays the given text.
   *
   * @command autentic:otp
   * @aliases auo
   * @usage autentic:otp
   */
  public function otp() {
    /** @var \Drupal\autentic\Service\AutenticService $autentic */
    // $autentic = \Drupal::service('autentic.service');
    $otp = $this->autentic->generateOtp();

    $this->output()->writeln(print_r($otp, TRUE));
  }

  /**
   * Drush command that displays the given text.
   *
   * @param string $email
   *   Argument with email to be sended.
   *
   * @command autentic:otp-email
   * @aliases auoe
   * @usage autentic:otp-email email
   */
  public function otpEmail($email) {
    /** @var \Drupal\autentic\Service\AutenticService $autentic */
    // $autentic = \Drupal::service('autentic.service');
    $otp = $this->autentic->generateEmailOtp($email);

    $this->output()->writeln(print_r($otp, TRUE));
  }

}
