<?php

namespace Drupal\tis\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sales\Service\DigitalSaleService;
use Drupal\tis\Form\TisSettingsForm;
use TgBotApi\BotApiBase\Type\InlineKeyboardButtonType;
use TgBotApi\BotApiBase\Type\InlineKeyboardMarkupType;
use TgBotApi\BotApiBase\Type\KeyboardButtonType;

/**
 * Class ExportService.
 */
class TelegramBridgeWithSales {

  use StringTranslationTrait;

  /**
   * Key form menu item.
   *
   * @var string
   */
  const NEW_SALE_MENU_ITEM = '📄 Iniciar una venta';

  /**
   * Key form menu item.
   *
   * @var string
   */
  const SUPPORT_MENU_ITEM = '🆘 Solicitar soporte';

  /**
   * Key form menu item.
   *
   * @var string
   */
  const VISIT_WEBSITE_MENU_ITEM = '🔗 Visitar nuestro sitio web';

  /**
   * Key form menu item.
   *
   * @var string
   */
  const YES_I_ACCEPT = '✅ Si, acepto.';

  /**
   * Key form menu item.
   *
   * @var string
   */
  const NO_I_DONT_ACCEPT = 'No estoy de acuerdo.';
  /**
   * The entity type .
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityField;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Var that stores config Factory Services.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The unwanted fields array.
   *
   * @var array
   */
  protected $unwantedFields;

  /**
   * The Messenger service.
   *
   * @var array
   */
  protected $inlineEntities;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $tisTelegram;

  /**
   * The digital sale service.
   *
   * @var \Drupal\sales\Service\DigitalSaleService
   */
  protected $digitalSaleService;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityField
   *   The file storage backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The bundle type info.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\tis\Service\TelegramService $tisTelegram
   *   The tis Telegram service.
   * @param \Drupal\tis\Service\DigitalSaleService $digitalSaleService
   *   The tis Telegram service.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityFieldManagerInterface $entityField,
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    FileSystemInterface $fileSystem = NULL,
    LoggerChannelFactoryInterface $logger,
    MessengerInterface $messenger,
    StateInterface $state,
    TelegramService $tisTelegram,
    DigitalSaleService $digitalSaleService
  ) {
    $this->config = $config;
    $this->settings = $config->getEditable(TisSettingsForm::SETTINGS);
    $this->entityField = $entityField;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->fileSystem = $fileSystem;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->state = $state;
    $this->tisTelegram = $tisTelegram;
    $this->digitalSaleService = $digitalSaleService;
  }

  /**
   * Function to get the callback data froma a text message.
   */
  public function startNewSale($update): string {
    $token = $this->digitalSaleService->getToken();
    // Store user token and start the conversation log.
    $seed = $this->getUserInteractionSeed($update);
    $this->digitalSaleService->setUserInteractionData($seed, 'token', $token);
    return $token;
  }

  /**
   * Function to set a manifest field.
   */
  public function setUserInteractionData($update, string $key, $value) {
    $seed = $this->getUserInteractionSeed($update);
    return $this->digitalSaleService->setUserInteractionData($seed, $key, $value);
  }

  /**
   * Function to get a manifest field.
   */
  public function getUserInteractionData($update, string $key = '') {
    $seed = $this->getUserInteractionSeed($update);
    return $this->digitalSaleService->getUserInteractionData($seed, $key);
  }

  /**
   * Function to delete all data related to interaction with user.
   */
  public function deleteUserInteractionData($update) {
    $seed = $this->getUserInteractionSeed($update);
    return $this->digitalSaleService->deleteUserInteractionData($seed);
  }

  /**
   * Function to delete all data related to interaction with user.
   */
  public function deleteSaleData($update) {
    $token = $this->getUserToken($update);
    return $this->digitalSaleService->deleteSaleTemporalData($token);
  }

  /**
   * Function to set a manifest field.
   */
  public function setManifestField(string $token, string $field, string $value) {
    return $this->digitalSaleService->setField($token, $field, $value);
  }

  /**
   * Function to get a manifest field.
   */
  public function getManifestField(string $token, string $field) {
    return $this->digitalSaleService->getField($token, $field);
  }

  /**
   * Function to get the callback data froma a text message.
   */
  public function getFieldsToFill(string $token): array {
    return $this->digitalSaleService->showFields($token);
  }

  /**
   * Function.
   */
  public function getUserFrienldyFieldName(string $fieldName) {
    $lastPartFieldName = explode('.', $fieldName);
    $nameField = end($lastPartFieldName);
    $nameField = str_replace('_', ' ', $nameField);
    return ucfirst($nameField);
  }

  /**
   * Function to convert an array in keyboard reply.
   */
  public function arrayToKeyboardReply(array $options) {
    $replyMarkup = NULL;

    $replyMarkupOptions = [];
    foreach ($options as $key => $value) {
      $replyMarkupOptions[] = InlineKeyboardButtonType::create(
        $this->getUserFrienldyFieldName($key) . ': ' . $value,
        ['callbackData' => $key]
      );
    }
    if (count($replyMarkupOptions)) {
      $replyMarkup = InlineKeyboardMarkupType::create($replyMarkupOptions);
    }
    return $replyMarkup;
  }

  /**
   * Function to get and store user nick name.
   */
  public function getUserNickName($update) {
    $seed = $this->getUserInteractionSeed($update);
    $nickname = '';

    if (!empty($seed)) {
      $storedUserNickname = $this->digitalSaleService->getUserInteractionData($seed, 'nickname');
      if (!empty($storedUserNickname)) {
        $nickname = $storedUserNickname;
      }
    }

    if (empty($nickname)) {
      if (is_array($update)) {
        $user = $this->tisTelegram->getFromUser($update);
        if (!is_null($user)) {
          $nickname = $user['first_name'];
        }
      }
    }

    return $nickname;
  }

  /**
   * Function to store temporal value for interaction with the user.
   */
  public function getUserInteractionSeed($update) {
    $telegramUser = $this->tisTelegram->getFromUser($update);
    $chat = $this->tisTelegram->getChat($update);
    $seed = $telegramUser['id'] . '.' . $chat['id'] . '.' . date('Y-m-d');
    return $seed;
  }

  /**
   * Function to get user token.
   */
  public function getUserToken($update) {
    $seed = $this->getUserInteractionSeed($update);
    $token = $this->digitalSaleService->getUserInteractionData($seed, 'token');
    if (empty($token)) {
      return '';
    }
    return $token;
  }

  /**
   * Function to set user nick name.
   */
  public function setUserNickName($nickname, $update) {
    $seed = $this->getUserInteractionSeed($update);
    $this->digitalSaleService->setUserInteractionData($seed, 'nickname', $nickname);
  }

  /**
   * Function to get and store user nick name.
   */
  public function getUserFullName($update, $chatId) {
    $token = $this->getUserToken($update);

    $userFullName = '';

    if (!is_null($token)) {
      $storedUserFullName = '';
      if (!empty($this->getUserInteractionData($update, 'primer_nombre'))) {
        $storedUserFullName .= ' ' . $this->getUserInteractionData($update, 'primer_nombre');
      }

      if (!empty($this->getUserInteractionData($update, 'segundo_nombre'))) {
        $storedUserFullName .= ' ' . $this->getUserInteractionData($update, 'segundo_nombre');
      }

      if (!empty($this->getUserInteractionData($update, 'primer_apellido'))) {
        $storedUserFullName .= ' ' . $this->getUserInteractionData($update, 'primer_apellido');
      }

      if (!empty($this->getUserInteractionData($update, 'segundo_apellido'))) {
        $storedUserFullName .= ' ' . $this->getUserInteractionData($update, 'segundo_apellido');
      }

      if (!empty(trim($storedUserFullName))) {
        $userFullName = trim($storedUserFullName);
      }
    }

    if (empty($userFullName)) {
      $user = $this->tisTelegram->getFromUser($update);
      $userInfo = $this->tisTelegram->getUserInfo($user['id'], $chatId);
      if (!is_null($userInfo)) {
        $this->storeUserFullName($userInfo, $update);
        $userFullName = $userInfo->user->firstName . ' ' . $userInfo->user->lastName;
      }
    }

    if (empty($userFullName)) {
      $userFullName = $this->getUserNickName($update);
    }

    return trim($userFullName);
  }

  /**
   * Function to get and store user nick name.
   */
  public function storeUserFullName($userInfo, $update) {
    $seed = $this->getUserInteractionSeed($update);
    $userNames = explode(' ', $userInfo->user->firstName);
    $userLastnames = explode(' ', $userInfo->user->lastName);

    if (isset($userNames[0])) {
      $this->digitalSaleService->setUserInteractionData($seed, 'primer_nombre', reset($userNames));
    }

    if (isset($userNames[1])) {
      $this->digitalSaleService->setUserInteractionData($seed, 'segundo_nombre', end($userNames));
    }
    else {
      $this->digitalSaleService->setUserInteractionData($seed, 'segundo_nombre', '');
    }

    if (isset($userLastnames[0])) {
      $this->digitalSaleService->setUserInteractionData($seed, 'primer_apellido', reset($userLastnames));
    }

    if (isset($userLastnames[1])) {
      $this->digitalSaleService->setUserInteractionData($seed, 'segundo_apellido', end($userLastnames));
    }
    else {
      $this->digitalSaleService->setUserInteractionData($seed, 'segundo_apellido', '');
    }
  }

  /**
   * Function to get and store user nick name.
   */
  public function cleanUserFullName($update) {
    $seed = $this->getUserInteractionSeed($update);
    $this->digitalSaleService->setUserInteractionData($seed, 'primer_nombre', '');
    $this->digitalSaleService->setUserInteractionData($seed, 'segundo_nombre', '');
    $this->digitalSaleService->setUserInteractionData($seed, 'primer_apellido', '');
    $this->digitalSaleService->setUserInteractionData($seed, 'segundo_apellido', '');
    $this->digitalSaleService->setUserInteractionData($seed, 'fullname', '');
  }

  /**
   * Function to get and store user nick name.
   */
  public function getFieldOptions($token, $fieldName) {
    $field = Json::decode($this->digitalSaleService->jsonField($token, $fieldName));
    if (isset($field['#options'])) {
      return $field['#options'];
    }
    return [];
  }

  /**
   * Function to get and store user nick name.
   */
  public function getFieldData($token, $fieldName) {
    return Json::decode($this->digitalSaleService->jsonField($token, $fieldName));
  }

  /**
   * Function to transform option array to replymarkup.
   */
  public function optionsArrayToKeyboardButtons(array $options, $columnsNumber = 1) {
    $keyboardButtons = [];
    foreach ($options as $option) {
      $keyboardButtons[] = [KeyboardButtonType::create($option)];
    }
    return $keyboardButtons;
  }

  /**
   * Function to build the input log to be stored.
   */
  public function getInputLog($userFlowSteps, $update, $currentStep) {
    if (is_array($update)) {
      return [
        'from' => $update['data']['message']['from']['id'],
        'date' => $update['data']['message']['date'],
        'field' => TelegramSaveSale::getCurrentFlowStepName($userFlowSteps, $currentStep),
        'data' => $update['message'],
      ];
    }
    return NULL;
  }

  /**
   * Function to store user input data log in the server.
   */
  public function storeInputData($userFlowSteps, $update, $currentStep) {
    $seed = $this->getUserInteractionSeed($update);
    $userInputLog = $this->digitalSaleService->getUserInteractionData($seed, 'inputlog');
    $userInputLog[] = $this->getInputLog($userFlowSteps, $update, $currentStep);
    $this->digitalSaleService->setUserInteractionData($seed, 'inputlog', $userInputLog);
  }

  /**
   * Function to store user input data log in the server.
   */
  public function getUserFlowSteps($update) {
    // Store telegram data flow in temp store.
    $storeFlowStructure = $this->getUserInteractionData($update, 'saleflow');
    if (empty($storeFlowStructure)) {
      $storeFlowStructure = TelegramSaveSale::getFlowStepsStructure();
      $this->setUserInteractionData($update, 'saleflow', $storeFlowStructure);
    }
    return $storeFlowStructure;
  }

  /**
   * Function to store user input data log in the server.
   */
  public function setUserFlowSteps($userStepsFlow, $update) {
    $this->setUserInteractionData($update, 'saleflow', $userStepsFlow);
  }

  /**
   * Function to store user input data log in the server.
   */
  public function getDataIdFromListName($inputData, $fieldName) {
    $termMachineName = $this->digitalSaleService->getTermMachineName($fieldName);
    if (!empty($termMachineName)) {
      $inputData = $this->digitalSaleService->getTermIdByName($inputData, $termMachineName);
    }
    return $inputData;
  }

  /**
   * Function to store user input data log in the server.
   */
  public function getDataIdFromFieldOptions($inputData, $fieldName, $token) {
    $list = $this->getFieldOptions($token, $fieldName);
    foreach ($list as $key => $value) {
      if ($value === $inputData) {
        return $key;
      }
    }
    return $inputData;
  }

  /**
   * Function to store user input data log in the server.
   */
  public function getFileIdFromLocalFileDownloadFromTelegram($update) {
    if (isset($update['downloads']) && !empty($update['downloads'])) {
      return $update['downloads']['local_file_id'];
    }
    return '';
  }

  /**
   * Function to store user input data log in the server.
   */
  public function getCheckBoxValueFromInputData($inputData) {
    if ($inputData === TelegramBridgeWithSales::YES_I_ACCEPT) {
      return 1;
    }
    return $inputData;
  }

  /**
   * Function to store user input data log in the server.
   */
  public function shouldRequestField($fieldName, $update) {
    $token = $this->getUserToken($update);
    return $this->digitalSaleService->isRequestField($token, $fieldName);
  }

  /**
   * Function to convert an array in html text with line breaks.
   */
  public function manifestToHtml($manifest) {
    $outputHtml = '';
    if (is_array($manifest)) {
      foreach ($manifest as $key => $value) {
        if ($key != 'legal.signature.signature_field'
          && $key != 'legal.adjuntos.selfie'
          && $key != 'legal.adjuntos.anexo_documento_identidad'
          && $key != 'legal.adjuntos.anexo_factura'
          && $key != 'informacion_adicional.contact.correo_electronico_vendedor'
        ) {
          switch ($key) {
            case 'plan.departamento':
            case 'plan.ciudad':
            case 'plan.asistencia':
            case 'informacion_adicional.identificacion.tipo_documento':
            case 'informacion_adicional.identificacion.genero':
            case 'direccion.via_complemento.commune':
              $value = $this->getTermNameById($value);
              break;

            case 'direccion.tipo_de_vivienda':
              if ($value == 'owned') {
                $value = 'Propia';
              }
              else {
                $value = 'Arriendo';
              }
              break;

            case 'legal.autorizacion.autorización_empresa':
            case 'legal.autorizacion.politica_de_datos':
            case 'legal.autorizacion.contrato_prestacion':
            case 'legal.autorizacion.contrato_vinculacion':
            case 'legal.autorizacion.autenticacion_firma':
              if ($value == '1') {
                $value = 'Si acepto.';
              }
              else {
                $value = 'No acepto.';
              }
              break;
          }
          $outputHtml .= $this->getUserFrienldyFieldName($key) . ': ' . $value . PHP_EOL;
        }
      }
    }
    return $outputHtml;
  }

  /**
   * Function to convert an array in html text with line breaks.
   */
  public function checkIfIsValidSale($update) {
    $token = $this->getUserToken($update);
    if (empty($token)) {
      return ['Syserror: No existe un token creado en el sistema para esta venta.'];
    }
    return $this->digitalSaleService->validSale($token);
  }

  /**
   * Function to convert an array in html text with line breaks.
   */
  public function sendSaleToRevisionAndSave($update) {
    $token = $this->getUserToken($update);
    return $this->digitalSaleService->saveSale($token);
  }

  /**
   * Function to convert an array in html text with line breaks.
   */
  public function getTermNameById($termId) {
    return $this->digitalSaleService->getTermNameById($termId);
  }

  /**
   * Function.
   */
  public function verifyIfIsValidFieldData($fieldName, $fieldValue) {
    return $this->digitalSaleService->isValidFieldData($fieldName, $fieldValue);
  }

}
