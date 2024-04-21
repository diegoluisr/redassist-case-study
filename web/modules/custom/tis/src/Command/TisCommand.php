<?php

namespace Drupal\tis\Command;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\tis\Service\TelegramCommands;
use Drupal\tis\Service\TelegramService;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush command file.
 *
 * @package Drupal\autentic\Command
 */
class TisCommand extends DrushCommands {

  /**
   * Variable that store the Telegram Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $telegram;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramCommands
   */
  protected $telegramCommands;

  /**
   * Var that store the logger for the channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @param \Drupal\tis\Service\TelegramService $telegram
   *   The Telegram Service.
   * @param \Drupal\tis\Service\TelegramCommands $telegramCommands
   *   The telegram commands service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The var for the logger.
   */
  public function __construct(
    TelegramService $telegram,
    TelegramCommands $telegramCommands,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->telegram = $telegram;
    $this->telegramCommands = $telegramCommands;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('telegram.service'),
      $container->get('telegram.commands'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Drush command to send message via telgram.
   *
   * @param string $message
   *   Argument with message to be displayed.
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:message
   * @aliases tism
   * @usage tis:message message channel
   */
  public function message($message, $channel = 'general') {
    $this->telegram->send($message, $channel);
  }

  /**
   * Drush command to responde messages via telgram.
   *
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:response
   * @aliases tisr
   * @usage tis:response channel
   */
  public function response($channel = 'general') {
    $latestMessage = $this->telegram->updates();
    $this->logger->get('tis_command')->info(print_r($latestMessage, TRUE));

    $message = 'con gusto!';
    $this->telegram->send($message, $channel);
  }

  /**
   * Drush command to responde messages via telgram.
   *
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:webhook
   * @aliases tisw
   * @usage tis:webhook channel
   */
  public function webhook($channel = 'general') {
    $res = $this->telegram->setWebhook();
    $this->logger->get('tis_command')->info(print_r($res, TRUE));

    $message = 'web hook enviado';
    $this->telegram->send($message, $channel);
  }

  /**
   * Drush command to register commands in telegram bot.
   *
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:registercommands
   * @aliases tisrc
   * @usage tis:registercommands channel
   */
  public function registerCommands($channel = 'general') {
    $res = $this->telegram->registerCommands($this->telegramCommands->commandsToRegister());
    $this->logger->get('tis_command')->info(print_r($res, TRUE));

    $message = 'listo los comandos!';
    $this->telegram->send($message, $channel);
  }

  /**
   * Drush command to register commands in telegram bot.
   *
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:getcommands
   * @aliases tisgc
   * @usage tis:getcommands channel
   */
  public function getCommands($channel = 'general') {
    $res = $this->telegram->getBotCommands();
    $this->logger->get('tis_command')->info(print_r($res, TRUE));

    $message = 'ya lei los comandos!';
    $this->telegram->send($message, $channel);
  }

  /**
   * Drush command to register commands in telegram bot.
   *
   * @param string $channel
   *   Argument with email to be sended.
   *
   * @command tis:getfile
   * @aliases tisgf
   * @usage tis:getfile channel
   */
  public function getFile($channel = 'general') {
    $res = $this->telegram->donwloadFile('AAMCAQADGQEAAgJpYi_rvW37DsHTQk-BwXV60YdEtCQAAv8CAAKXIYFFjugfIY6ey-oBAAdtAAMjBA');
    $this->logger->get('tis_command')->info(print_r($res, TRUE));

    $message = 'ya lei el archivo!';
    $this->telegram->send($message, $channel);
  }

}
