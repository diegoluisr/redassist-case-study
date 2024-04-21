<?php

namespace Drupal\ffmpeg\Command;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;
use Drupal\ffmpeg\Service\FfmpegService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush command file.
 *
 * @package Drupal\ffmpeg\Command
 */
class FfmpegCommand extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Var that store the ffmpeg service.
   *
   * @var Drupal\ffmpeg\Service\FfmpegService
   */
  protected $ffmpeg;

  /**
   * {@inheritdoc}
   */
  public function __construct(FfmpegService $ffmpeg) {
    $this->ffmpeg = $ffmpeg;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
      $container->get('ffmpeg.service')
    );
  }

  /**
   * Drush command that displays the given text.
   *
   * @param string $code
   *   Argument with code to be created.
   *
   * @command ffmpeg:otp
   * @aliases ffmpegt
   * @usage ffmpeg:otp
   */
  public function otp($code) {
    print_r($this->ffmpeg->otp($code));
  }

}
