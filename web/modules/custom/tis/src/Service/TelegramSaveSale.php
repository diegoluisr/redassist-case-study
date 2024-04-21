<?php

namespace Drupal\tis\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sales\Form\SaleForm;
use Drupal\tis\Form\TisSettingsForm;
use TgBotApi\BotApiBase\Method\SendGameMethod;
use TgBotApi\BotApiBase\Type\CallbackGameType;
use TgBotApi\BotApiBase\Type\InlineKeyboardButtonType;
use TgBotApi\BotApiBase\Type\InlineKeyboardMarkupType;
use TgBotApi\BotApiBase\Type\KeyboardButtonType;
use TgBotApi\BotApiBase\Type\ReplyKeyboardMarkupType;

/**
 * Class ExportService.
 */
class TelegramSaveSale {

  use StringTranslationTrait;

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
   * @var \Drupal\tis\Service\TelegramBridgeWithSales
   */
  protected $telegramBridgeWithSales;

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
   * @param \Drupal\tis\Service\TelegramBridgeWithSales $telegramBridgeWithSales
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
    TelegramBridgeWithSales $telegramBridgeWithSales
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
    $this->telegramBridgeWithSales = $telegramBridgeWithSales;
  }

  /**
   * Function to know all the flow.
   */
  public static function getFlowStepsStructure() {
    // ['name' => 'confirm.name', 'storeInManifest' => FALSE,
    // 'completed' => FALSE, 'current' => FALSE],
    return [
      [
        'name' => 'show.terms',
        'storeInManifest' => FALSE,
        'completed' => FALSE,
        'current' => TRUE,
      ],
      [
        'name' => 'request.names',
        'storeInManifest' => FALSE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'request.lastnames',
        'storeInManifest' => FALSE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'plan.departamento',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'plan.ciudad',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'plan.asistencia',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.autorizacion.autorización_empresa',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.autorizacion.politica_de_datos',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.autorizacion.contrato_prestacion',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.autorizacion.contrato_vinculacion',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.autorizacion.autenticacion_firma',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'plan.nip1',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'plan.ciclo',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.tipo_de_vivienda',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.estrato',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.direccion.direccion_completa',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.via_complemento.complemento',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.via_complemento.commune',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'direccion.via_complemento.barrio',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.primer_nombre',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.segundo_nombre',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.primer_apellido',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.segundo_apellido',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.identificacion.tipo_documento',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.identificacion.numero_identificacion',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.identificacion.lugar_expedicion',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.identificacion.genero',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.contact.correo_electronico',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.contact.telefono_celular',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.contact.telefono_fijo',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'informacion_adicional.contact.correo_electronico_vendedor',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.adjuntos.selfie',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.adjuntos.anexo_documento_identidad',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'legal.adjuntos.anexo_factura',
        'storeInManifest' => TRUE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      // [
      // 'name' => 'legal.signature.signature_field',
      // 'storeInManifest' => TRUE,
      // 'completed' => FALSE,
      // 'current' => FALSE,
      // ],
      [
        'name' => 'confirm.data',
        'storeInManifest' => FALSE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
      [
        'name' => 'save.sale',
        'storeInManifest' => FALSE,
        'completed' => FALSE,
        'current' => FALSE,
      ],
    ];
  }

  /**
   * Function to confirm if the current steps was compelte.
   */
  public function launchFlowStepAction($userFlowSteps, $currentStep, $update, $chatId) {
    $step = $this->getCurrentFlowStepName($userFlowSteps, $currentStep);
    switch ($step) {

      case 'show.terms':
        $this->showTermsAndConditions($update, $chatId);
        break;

      case 'confirm.name':
        $this->confirmUserFullName($update, $chatId);
        break;

      case 'request.names':
        $this->requestDataFromInput($update, $chatId, '¿Cuál es tu nombre? escribe tu primer y segundo nombre Ej: Sandra Milena');
        break;

      case 'request.lastnames':
        $this->requestDataFromInput($update, $chatId, '¿Cuáles son tus apellidos?');
        break;

      case 'plan.departamento':
        $this->requestDataFromList($update, $chatId, $step, '¿En que departamento necesitas el servicio?');
        break;

      case 'plan.ciudad':
        $this->requestDataFromList($update, $chatId, $step, '¿En que ciudad?');
        break;

      case 'plan.asistencia':
        $this->requestDataFromList($update, $chatId, $step, '¿Cuál asistencia deseas contratar?');
        break;

      case 'plan.nip1':
        $this->requestDataFromInput($update, $chatId, '¿Cuál es el número de contrato de tu factura?');
        break;

      case 'plan.ciclo':
        $cicleInfo = $this->getFieldAllowedInputData($update, $step);
        $this->requestDataFromInput($update, $chatId, '¿Cuál es ciclo de facturación del contrato? ' . $cicleInfo);
        break;

      case 'direccion.tipo_de_vivienda':
        $this->requestDataFromList($update, $chatId, $step, '¿Qué tipo de vivienda o negocio es?');
        break;

      case 'direccion.estrato':
        $this->requestDataFromList($update, $chatId, $step, '¿Cuál es el estrato?');
        break;

      case 'direccion.direccion.direccion_completa':
        $this->requestDataFromInput($update, $chatId, '¿Cuál esa la dirección? Eje: Calle 1norte # 3a-101');
        break;

      case 'direccion.via_complemento.complemento':
        $this->requestDataFromInput($update, $chatId, '¿Hay algun otro dato que debamos conocer sobre tu dirección? Eje: bloque 1 apto 101 o piso 2. Escribe NA si no aplica.');
        break;

      case 'direccion.via_complemento.commune':
        $this->requestDataFromList($update, $chatId, $step, '¿Ha que comuna o localidad pertenece el predio?');
        break;

      case 'direccion.via_complemento.barrio':
        $this->requestDataFromInput($update, $chatId, '¿Cuál es el nombre del barrio o zona donde vives?');
        break;

      case 'informacion_adicional.primer_nombre':
        $update = $this->getFullNameSectionFromStoredData($userFlowSteps, $update, 'primer_nombre');
        $isValidData = $this->telegramBridgeWithSales->verifyIfIsValidFieldData($step, $update['message']);
        if (is_string($isValidData)) {
          $this->requestDataFromInput($update, $chatId, '¿Cuál es tu primer nombre? Eje: Jose');
        }
        elseif ($isValidData) {
          $this->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        }
        break;

      case 'informacion_adicional.segundo_nombre':
        $update = $this->getFullNameSectionFromStoredData($userFlowSteps, $update, 'segundo_nombre');
        $isValidData = $this->telegramBridgeWithSales->verifyIfIsValidFieldData($step, $update['message']);
        if (is_string($isValidData)) {
          $this->requestDataFromInput($update, $chatId, '¿Cuál es tu segundo nombre? Eje: Andrés');
        }
        elseif ($isValidData) {
          $this->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        }
        break;

      case 'informacion_adicional.primer_apellido':
        $update = $this->getFullNameSectionFromStoredData($userFlowSteps, $update, 'primer_apellido');
        $isValidData = $this->telegramBridgeWithSales->verifyIfIsValidFieldData($step, $update['message']);
        if (is_string($isValidData)) {
          $this->requestDataFromInput($update, $chatId, '¿Cuál es tu primer apellido? Eje: Perez');
        }
        elseif ($isValidData) {
          $this->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        }
        break;

      case 'informacion_adicional.segundo_apellido':
        $update = $this->getFullNameSectionFromStoredData($userFlowSteps, $update, 'segundo_apellido');
        $isValidData = $this->telegramBridgeWithSales->verifyIfIsValidFieldData($step, $update['message']);
        if (is_string($isValidData)) {
          $this->requestDataFromInput($update, $chatId, '¿Cuál es tu segundo apellido? Eje: Clavijo');
        }
        elseif ($isValidData) {
          $this->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        }
        break;

      case 'informacion_adicional.identificacion.tipo_documento':
        $this->requestDataFromList($update, $chatId, $step, '¿Cuál es tu tipo de documento de identificación?');
        break;

      case 'informacion_adicional.identificacion.numero_identificacion':
        $this->requestDataFromInput($update, $chatId, '¿Cuál es el número de tu documento de identficación?');
        break;

      case 'informacion_adicional.identificacion.lugar_expedicion':
        $this->requestDataFromInput($update, $chatId, '¿Dónde fue expedido tu documento de identificación?');
        break;

      case 'informacion_adicional.identificacion.genero':
        $this->requestDataFromList($update, $chatId, $step, '¿Cuál es tu género?');
        break;

      case 'informacion_adicional.contact.correo_electronico':
        $this->requestDataFromInput($update, $chatId, '¿Cúal es tu direccion de correo electronico? Eje: tunombre@gmail.com');
        break;

      case 'informacion_adicional.contact.telefono_celular':
        $this->requestDataFromInput($update, $chatId, '¿Cúal es el número de tu teléfono celular? Ej: 310 555 4466');
        break;

      case 'informacion_adicional.contact.telefono_fijo':
        $this->requestDataFromInput($update, $chatId, '¿Cúal es el número de tu teléfono fijo?. Escribe NA si no tienes uno.');
        break;

      case 'informacion_adicional.contact.correo_electronico_vendedor':
        $update['message'] = SaleForm::RA_B2C_VIRTUAL_MAIL;
        $this->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        break;

      case 'legal.adjuntos.selfie':
        $this->requestDataFromInput($update, $chatId, 'por favor envianos una fotografia de quien adquiere la asistencia, debe verse claramente tu rostro y debe ser legible.');
        break;

      case 'legal.adjuntos.anexo_documento_identidad':
        $this->requestDataFromInput($update, $chatId, 'por favor envianos tu documento de identidad. Puede ser una imagen o un PDF.');
        break;

      case 'legal.adjuntos.anexo_factura':
        $this->requestDataFromInput($update, $chatId, 'por favor envianos una factura. Puede ser una imagen o un PDF.');
        break;

      case 'legal.autorizacion.autorización_empresa':
        $this->informAboutTermsAndConditions($update, $chatId);
        $this->requestDataFromCheck($update, $chatId, $step);
        break;

      case 'legal.autorizacion.politica_de_datos':
        $this->requestDataFromCheck($update, $chatId, $step);
        break;

      case 'legal.autorizacion.contrato_prestacion':
        $this->requestDataFromCheck($update, $chatId, $step);
        break;

      case 'legal.autorizacion.contrato_vinculacion':
        $this->requestDataFromCheck($update, $chatId, $step);
        break;

      case 'legal.autorizacion.autenticacion_firma':
        $this->requestDataFromCheck($update, $chatId, $step);
        break;

      case 'legal.signature.signature_field':
        $this->launchSignatureApp($update, $chatId);
        break;

      case 'confirm.data':
        $this->confirmManifestData($update, $chatId);
        break;

      case 'save.sale':
        $this->registerSale($update, $chatId);
        break;
    }
  }

  /**
   * Function to confirm if the current steps was compelte.
   */
  public function getFieldAllowedInputData($update, $field) {
    $token = $this->telegramBridgeWithSales->getUserToken($update);
    $data = $this->telegramBridgeWithSales->getFieldData($token, $field);
    if (isset($data['#message_for_bot']) && !empty($data['#message_for_bot'])) {
      return $data['#message_for_bot'];
    }
    return '';
  }

  /**
   * Function to confirm if the current step was compelete.
   */
  public function getFullNameSectionFromStoredData($userFlowSteps, $update, $sectionName) {
    // Check if names are stored in user interaction data object.
    $userStoredNames = $this->telegramBridgeWithSales->getUserInteractionData($update, $sectionName);
    if (!empty($userStoredNames)) {
      // Store first name and go to next flow step.
      $update['message'] = $userStoredNames;
    }
    else {
      $requestFirst = TRUE;
      $stepName = '';
      switch ($sectionName) {
        case 'primer_nombre':
          $stepName = 'request.names';
          break;

        case 'segundo_nombre':
          $stepName = 'request.names';
          $requestFirst = FALSE;
          break;

        case 'primer_apellido':
          $stepName = 'request.lastnames';
          break;

        case 'segundo_apellido':
          $stepName = 'request.lastnames';
          $requestFirst = FALSE;
          break;
      }
      $userStoredNames = explode(' ', $this->getDataFromStepName($userFlowSteps, $stepName));
      if ($requestFirst) {
        $update['message'] = reset($userStoredNames);
      }
      else {
        if (count($userStoredNames) > 1) {

          array_shift($userStoredNames);
          $userStoredNames = implode(' ', $userStoredNames);

          if (!empty($userStoredNames)) {
            $update['message'] = $userStoredNames;
          }
          else {
            $update['message'] = '';
          }
        }
        else {
          $update['message'] = '';
        }
      }
    }
    // If (!empty($update['message'])) {
    // $update['message'] = $this->cleanString($update['message']);
    // $this->telegramBridgeWithSales
    // ->verifyIfIsValidFieldData(, $update['message']);
    // }.
    return $update;
  }

  /**
   * Function to confirm if the current steps.
   */
  public function cleanString($string) {
    return preg_replace('/[^a-zA-Z0-9ÑñÁáÉéÍíÓóÚú ]/s', '', $string);
  }

  /**
   * Function to confirm if the current step was compelte.
   */
  public function getDataFromStepName($userFlowSteps, $stepName) {
    foreach ($userFlowSteps as $userFlowStep) {
      if ($userFlowStep['name'] == $stepName) {
        return $userFlowStep['data'];
      }
    }
  }

  /**
   * Function to confirm if the current step was compelte.
   */
  public function getNextFlowStep($userFlowSteps, $update, $chatId) {
    $currentStep = $this->getCurrentFlowStep($userFlowSteps);
    $field = $this->getCurrentFlowStepName($userFlowSteps, $currentStep);

    // Verify if the field shuld be requested.
    if ($this->telegramBridgeWithSales->shouldRequestField($field, $update)) {
      $this->launchFlowStepAction($userFlowSteps, $currentStep, $update, $chatId);
    }
    else {
      $this->skipCurrentStep($userFlowSteps, $update, $chatId);
    }
  }

  /**
   * Function to confirm if the current steps was complete.
   */
  public function getCurrentFlowStep($userFlowSteps):int {
    $step = 0;
    foreach ($userFlowSteps as $userFlowStep) {
      if ($userFlowStep['current'] === TRUE && $userFlowStep['completed'] === FALSE) {
        return (int) $step;
      }
      $step++;
    }
    return (int) $step;
  }

  /**
   * Function to confirm if the current steps was complete.
   */
  public static function getCurrentFlowStepName($userFlowSteps, $step) {
    return $userFlowSteps[$step]['name'];
  }

  /**
   * Function to confirm if the current steps was complete.
   */
  public function isSalesFlow($update) {
    $isSaleFlowConversation = $this->telegramBridgeWithSales->getUserInteractionData($update, 'is_sale_flow_conversation');
    if (empty($isSaleFlowConversation)) {
      $this->telegramBridgeWithSales->getUserInteractionData($update, 'is_sale_flow_conversation', FALSE);
      return FALSE;
    }
    return $isSaleFlowConversation;
  }

  /**
   * Function to confirm if the current steps was complete.
   */
  public function startSalesFlow($update) {
    $this->telegramBridgeWithSales->setUserInteractionData($update, 'is_sale_flow_conversation', TRUE);
  }

  /**
   * Function to confirm if the steps was complete.
   */
  public function endSalesFlow($update) {
    $this->telegramBridgeWithSales->setUserInteractionData($update, 'is_sale_flow_conversation', FALSE);
  }

  /**
   * Function to confirm if the current steps was complete.
   */
  public function completeCurrentStep($userFlowSteps, $update, $chatId, $goToNextStep = TRUE, $isInputData = FALSE) {
    if (!$this->isSalesFlow($update)) {
      $this->showWelcomeMessage($update, $chatId);
      return FALSE;
    }

    $currentStep = $this->getCurrentFlowStep($userFlowSteps);

    if ($isInputData) {
      if ($userFlowSteps[$currentStep]['name'] !== 'request.names'
        && $userFlowSteps[$currentStep]['name'] !== 'request.lastnames'
      ) {
        if (!$userFlowSteps[$currentStep]['storeInManifest']) {
          $this->requestFieldDataAgain('No reconocemos la opción que elegiste.', $chatId, $update);
          return FALSE;
        }
      }
    }

    // Store input data register in user flow.
    $userFlowSteps[$currentStep]['data'] = $update['message'];

    // Store input data in manifest.
    if ($userFlowSteps[$currentStep]['storeInManifest']) {
      if ($this->storeFieldInManifest($userFlowSteps, $currentStep, $update, $chatId) === FALSE) {
        return FALSE;
      }
    }

    $userFlowSteps[$currentStep]['completed'] = TRUE;
    $userFlowSteps[$currentStep]['current'] = FALSE;
    $userFlowSteps[($currentStep + 1)]['current'] = TRUE;
    // Update flow steps in tempstore.
    $this->telegramBridgeWithSales->setUserFlowSteps($userFlowSteps, $update);

    if ($goToNextStep) {
      $this->getNextFlowStep($userFlowSteps, $update, $chatId);
    }
  }

  /**
   * Function to confirm if the current steps was compelete.
   */
  public function skipCurrentStep($userFlowSteps, $update, $chatId) {
    $currentStep = $this->getCurrentFlowStep($userFlowSteps);

    $userFlowSteps[$currentStep]['completed'] = TRUE;
    $userFlowSteps[$currentStep]['current'] = FALSE;
    $userFlowSteps[($currentStep + 1)]['current'] = TRUE;
    // Update flow steps in tempstore.
    $this->telegramBridgeWithSales->setUserFlowSteps($userFlowSteps, $update);
    // Go to next step.
    $this->getNextFlowStep($userFlowSteps, $update, $chatId);
  }

  /**
   * Function to confirm if the current steps was compelte.
   */
  public function goToFlowStep($userFlowSteps, $update, $chatId, int $targetStep, $launchStep = TRUE) {
    $userFlowSteps[$targetStep]['current'] = TRUE;

    for ($i = ($targetStep - 1); $i >= 0; $i--) {
      $userFlowSteps[$i]['current'] = FALSE;
      $userFlowSteps[$i]['completed'] = TRUE;
    }

    // Update flow steps in tempstore.
    $this->telegramBridgeWithSales->setUserFlowSteps($userFlowSteps, $update);

    if ($launchStep) {
      $this->launchFlowStepAction($userFlowSteps, $targetStep, $update, $chatId);
    }
  }

  /**
   * Function to confirm/request user full name.
   */
  public function showToken($update, $token, $chatId) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    $this->tisTelegram->send(
      $userNickName . ' hemos registrado tu solicitud en nuestro sistema con el siguiente código:',
      $chatId
    );
    $this->tisTelegram->send($token, $chatId);
  }

  /**
   * Function to confirm user full name 1st step.
   */
  public function confirmUserFullName($update, $chatId) {
    $userFullName = $this->telegramBridgeWithSales->getUserFullName($update, $chatId);
    $this->telegramBridgeWithSales->setUserInteractionData($update, 'fullname', $userFullName);
    // Store user nickname in state.
    $userNickName = explode(' ', $userFullName);
    $this->telegramBridgeWithSales->setUserNickName(reset($userNickName), $update);
    // Request confirmation.
    $replyMarkup = ReplyKeyboardMarkupType::create([
      [KeyboardButtonType::create('✅ Si, es mi nombre')],
      [KeyboardButtonType::create('No, debemos actualizarlo')],
    ], ['oneTimeKeyboard' => TRUE]);

    $this->tisTelegram->send('"' . $userFullName . '" es tu nombre completo?', $chatId, $replyMarkup);
  }

  /**
   * Function to confirm user full name 1st step.
   */
  public function informAboutTermsAndConditions($update, $chatId) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    $this->tisTelegram->send(
      $userNickName . ' a la totalidad de las siguientes preguntas debes contestar "Si, acepto." para poder radicar tu solicitud de servicio.',
      $chatId
    );
  }

  /**
   * Function to confirm user full name 1st step.
   */
  public function launchSignatureApp($update, $chatId) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    $gameUrl = $this->settings->get('signature_webapp_url');

    if (empty($gameUrl)) {
      $this->logger->get('tis')->error('No se puede procesar una solcitud de servicio a traves de telegram porque no esta configurado debidamente la apliación de firma. Por favor verifique las variables de configuración y la conexion con la api de Telegram.');
      $this->tisTelegram->send(
        $userNickName . ' en este momento no podemos procesar tu solicitud de servicio. Por favor contactanos o intenta iniciar nuevamente el proceso de solictud en unos minutos.',
        $chatId
      );
      return FALSE;
    }

    $replyMarkup = InlineKeyboardMarkupType::create([
      [
        InlineKeyboardButtonType::create(
          'Ir a firmar',
          ['callbackGame' => CallbackGameType::create()]
        ),
      ],
    ]);
    $method = SendGameMethod::create($chatId, 'B2CSignature', ['replyMarkup' => $replyMarkup]);
    $this->tisTelegram->sendGame($method);
    $this->tisTelegram->send('Este es el boton que buscas ☝️', $chatId, NULL, TRUE);
  }

  /**
   * Function to confirm terms and conditions 2nd step.
   */
  public function showTermsAndConditions($update, $chatId) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    // $termsUrl = $this->settings->get('terms_url');
    // $replyMarkup = InlineKeyboardMarkupType::create([
    // [InlineKeyboardButtonType::create('Leer terminos y
    // condiciones.', ['url' => $termsUrl])],
    // ]);
    // $this->tisTelegram->send($userNickName . '
    // estás a punto de iniciar un proceso de venta de asistencia para tu hogar
    // o negocio por medio e B2C te invitamos a leer la siguiente información:',
    // $chatId,
    // $replyMarkup
    // );
    $replyMarkup = ReplyKeyboardMarkupType::create([
      [KeyboardButtonType::create('✅ Si, estoy listo')],
      [KeyboardButtonType::create('No, por favor cancelar esta solicitud')],
    ], ['oneTimeKeyboard' => TRUE]);

    $msg = 'estás a punto de iniciar un proceso de venta de
      asistencia para tu hogar o negocio por medio e
      B2C ¿Estas listo para continuar?';
    $this->tisTelegram->send(
      $userNickName . ' ' . $msg,
      $chatId,
      $replyMarkup
    );
  }

  /**
   * Function to confirm/request country state 3rd step.
   */
  public function showWelcomeMessage($update, $chatId) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    // Clean user interaction data.
    $this->telegramBridgeWithSales->deleteUserInteractionData($update);
    // Welcome user message.
    $replyMarkup = ReplyKeyboardMarkupType::create([
      [KeyboardButtonType::create(TelegramBridgeWithSales::NEW_SALE_MENU_ITEM)],
      [KeyboardButtonType::create(TelegramBridgeWithSales::SUPPORT_MENU_ITEM)],
      [KeyboardButtonType::create(TelegramBridgeWithSales::VISIT_WEBSITE_MENU_ITEM)],
    ], ['oneTimeKeyboard' => TRUE]);

    $this->tisTelegram->send($userNickName . ' ¿Qué quieres hacer?', $chatId, $replyMarkup);
  }

  /**
   * Function to confirm/request country state 3rd step.
   */
  public function requestDataFromList($update, $chatId, $field, $msg) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    $token = $this->telegramBridgeWithSales->getUserToken($update);

    $list = $this->telegramBridgeWithSales->getFieldOptions($token, $field);
    // \Drupal::logger('tis_states')->info(print_r($states, TRUE));
    if (count($list)) {
      $keyboardButtons = $this->telegramBridgeWithSales->optionsArrayToKeyboardButtons($list);
      $replyMarkup = ReplyKeyboardMarkupType::create(
        $keyboardButtons,
        ['oneTimeKeyboard' => TRUE],
      );

      $this->tisTelegram->send(
        $userNickName . ' ' . $msg,
        $chatId,
        $replyMarkup
      );
    }
  }

  /**
   * Function to confirm/request country state 3rd step.
   */
  public function requestDataFromCheck($update, $chatId, $field) {
    $token = $this->telegramBridgeWithSales->getUserToken($update);
    $data = $this->telegramBridgeWithSales->getFieldData($token, $field);
    // \Drupal::logger('tis_states')->info(print_r($states, TRUE));
    if (is_array($data) && count($data)) {
      $replyMarkup = ReplyKeyboardMarkupType::create([
        [KeyboardButtonType::create(TelegramBridgeWithSales::YES_I_ACCEPT)],
        [KeyboardButtonType::create(TelegramBridgeWithSales::NO_I_DONT_ACCEPT)],
      ],
        ['oneTimeKeyboard' => TRUE]
      );

      $message = $data['#title'];

      if (count($data['#links'])) {
        $message .= $this->fieldLinksToLink($data['#links']);
      }

      $this->tisTelegram->send(
        $message,
        $chatId,
        $replyMarkup
      );
    }
  }

  /**
   * Function to confirm/request country state 3rd step.
   */
  public function fieldLinksToLink($links) {
    $addNumers = FALSE;
    $message = 'Visita el siguiente enalce para conocer más al respecto:';
    if (count($links) > 1) {
      $message = 'Visita los siguientes enalces para conocer más al respecto:';
      $addNumers = TRUE;
    }

    $number = 1;
    foreach ($links as $link) {
      $message .= ' <a href="' . $link['file'] . '">';
      if ($addNumers) {
        $message .= $number . '. ';
      }
      $message .= $link['title'] . '</a>';
      $number++;
    }
    return $message;
  }

  /**
   * Function to confirm/request country state 3rd Step.
   */
  public function requestDataFromInput($update, $chatId, $msg) {
    $userNickName = $this->telegramBridgeWithSales->getUserNickName($update);
    $this->tisTelegram->send($userNickName . ' ' . $msg, $chatId);
  }

  /**
   * Function to confirm if the current steps was compelte.
   */
  public function requestFieldDataAgain($errorMsg, $chatId, $update) {
    $errorMsg = 'Por favor ingresa nuevamente esta información. ' . $errorMsg;

    if (isset($update['downloads'])) {
      $errorMsg = 'Por favor envia nuevamante el archivo. ' . $errorMsg;
    }

    $this->tisTelegram->send($errorMsg, $chatId, NULL, FALSE);
  }

  /**
   * Function to confirm if the current steps was compelte.
   */
  public function storeFieldInManifest($userFlowSteps, $currentStep, $update, $chatId) {

    $token = $this->telegramBridgeWithSales->getUserToken($update);
    $manifestField = $this->getCurrentFlowStepName($userFlowSteps, $currentStep);
    $inputData = $update['message'];
    $isCheckBox = FALSE;

    if (strtolower($inputData) === "na") {
      $inputData = '';
    }
    // $this->logger->get('tis_ipnut_na')->
    // info(print_r($inputData, TRUE));
    switch ($manifestField) {
      case 'plan.departamento':
      case 'plan.ciudad':
      case 'plan.asistencia':
      case 'informacion_adicional.identificacion.tipo_documento':
      case 'informacion_adicional.identificacion.genero':
      case 'direccion.via_complemento.commune':
        $inputData = $this->telegramBridgeWithSales
          ->getDataIdFromListName($inputData, $manifestField);
        break;

      case 'direccion.tipo_de_vivienda':
        $inputData = $this->telegramBridgeWithSales
          ->getDataIdFromFieldOptions($inputData, $manifestField, $token);
        break;

      case 'legal.adjuntos.selfie':
      case 'legal.adjuntos.anexo_documento_identidad':
      case 'legal.adjuntos.anexo_factura':
        $inputData = $this->telegramBridgeWithSales
          ->getFileIdFromLocalFileDownloadFromTelegram($update);
        break;

      case 'legal.autorizacion.autorización_empresa':
      case 'legal.autorizacion.politica_de_datos':
      case 'legal.autorizacion.contrato_prestacion':
      case 'legal.autorizacion.contrato_vinculacion':
      case 'legal.autorizacion.autenticacion_firma':
        $inputData = $this->telegramBridgeWithSales
          ->getCheckBoxValueFromInputData($inputData);
        $isCheckBox = TRUE;
        break;
    }

    // $this->logger->get('tis_setManifestField_tosave')
    // ->info(print_r($inputData . ' - ' . $manifestField, TRUE));
    $save = $this->telegramBridgeWithSales->setManifestField($token, $manifestField, $inputData);
    // $this->logger->get('tis_setManifestField_saved')
    // ->info(print_r($save, TRUE));
    if (is_string($save)) {
      if (!$isCheckBox) {
        $this->requestFieldDataAgain($save, $chatId, $update);
      }
      else {
        if ($manifestField !== 'legal.autorizacion.autorización_empresa') {
          $this->tisTelegram->send('Para continuar con la vinculación a nuestro servicio es
            necesario que revise, conozca y acepte los siguientes terminos y condiciones.', $chatId);
        }
        $this->launchFlowStepAction($userFlowSteps, $currentStep, $update, $chatId);
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Function to confirm user full name 1st Step.
   */
  public function confirmManifestData($update, $chatId) {
    $token = $this->telegramBridgeWithSales->getUserToken($update);
    $manifest = $this->telegramBridgeWithSales->getFieldsToFill($token);

    $replyMarkup = ReplyKeyboardMarkupType::create([
      [KeyboardButtonType::create('✅ Si, esta todo en orden')],
      [KeyboardButtonType::create('No, empezar nuevamente')],
    ], ['oneTimeKeyboard' => TRUE]);

    $this->tisTelegram->send(
      'Esta es la información que suministraste:',
      $chatId
    );
    $this->tisTelegram->send($this->telegramBridgeWithSales
      ->manifestToHtml($manifest), $chatId);
    $this->tisTelegram->send(
      'Deseas solicitar el servicio con esta información?',
      $chatId,
      $replyMarkup
    );
  }

  /**
   * Function to confirm user full name 1st step.
   */
  public function showValidationErrors($errors, $chatId) {
    $this->tisTelegram->send(
      'Tu solicitud de servicio no pudo ser recibida por los siguientes motivos:',
      $chatId
    );
    $this->logger->get('tis_showValidationErrors')
      ->error(print_r($errors, TRUE));
    $this->tisTelegram->send(
      $this->tisTelegram->arrayToHtml($errors),
      $chatId
    );
  }

  /**
   * Function to confirm user full name 1st Step.
   */
  public function finishFlowAndDeleteTempData($update) {
    // Finish sale flow.
    $this->endSalesFlow($update);
    // Delete interation data.
    $this->telegramBridgeWithSales->deleteUserInteractionData($update);
    // Delete sale data.
    $this->telegramBridgeWithSales->deleteSaleData($update);
  }

  /**
   * Function to confirm user full name. 1st Step.
   */
  public function registerSale($update, $chatId) {
    $errors = [];

    // Send temporal data to logger.
    $token = $this->telegramBridgeWithSales->getUserToken($update);
    if (!empty($token)) {
      $manifest = $this->telegramBridgeWithSales->getFieldsToFill($token);
      $this->logger->get('tis_manifestTosale')->info(print_r($manifest, TRUE));
    }

    $errors = $this->telegramBridgeWithSales->checkIfIsValidSale($update);
    if (!empty($errors)) {
      $this->showValidationErrors($errors, $chatId);
      $this->tisTelegram->send('Por favor intenta realizar la solicitud nuevamente o comunicate con nosotros.', $chatId);
    }
    else {
      $saveSale = $this->telegramBridgeWithSales->sendSaleToRevisionAndSave($update);
      if (is_array($saveSale)) {
        $this->showValidationErrors($saveSale, $chatId);
        $this->tisTelegram->send('Por favor intenta realizar la solicitud nuevamente o comunicate con nosotros.', $chatId);
      }
      elseif ($saveSale === TRUE) {
        // Is valid sale.
        $this->tisTelegram->send(
          'Excelente, hemos enviado tu solicitud para ser revisada y aprobada por nuestro equipo. Por favor esta atento a tu email y teléfono celuar, a traves de ellos te informaremos sobre el estado de tu solicitud y te indicaremos que otros pasos debes reallizar.',
          $chatId
        );
      }
      else {
        $this->tisTelegram->send(
          'No fue posible radicar tu solicitud de servicio, por favor intentalo nuevamente o comunicate con nosotros.',
          $chatId
        );
      }
    }

    // Finish flow and delete temporal data.
    $this->finishFlowAndDeleteTempData($update);
    // Show welcome messsage.
    $this->showWelcomeMessage($update, $chatId);
  }

  /**
   * Function to confirm user full name 1st Step.
   */
  public function cancelSaleRequest($update, $chatId) {
    $this->tisTelegram->send(
      'Hemos cancelado tu solicitud. Si lo deseas puedes iniciar nuevamente.',
      $chatId
    );
    // Finish flow and delete temporal data.
    $this->finishFlowAndDeleteTempData($update);
    // Show welcome messsage.
    $this->showWelcomeMessage($update, $chatId);
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function shouldProcessSignature($userFlowSteps) {
    $currentStep = $this->getCurrentFlowStep($userFlowSteps);
    if ($userFlowSteps[$currentStep]['name'] === 'legal.signature.signature_field') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function shouldProcessFile($userFlowSteps) {
    $currentStep = $this->getCurrentFlowStep($userFlowSteps);
    if ($userFlowSteps[$currentStep]['name'] === 'legal.adjuntos.selfie'
      || $userFlowSteps[$currentStep]['name'] === 'legal.adjuntos.anexo_documento_identidad'
      || $userFlowSteps[$currentStep]['name'] === 'legal.adjuntos.anexo_factura'
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function showSupportLink($chatId) {
    $url = $this->settings->get('support_url');
    if (empty($url)) {
      $this->logger->get('tis')
        ->error('No se ha ingresado una url para el soporte en la configuración.');
      return FALSE;
    }

    $replyMarkup = InlineKeyboardMarkupType::create([
      [
        InlineKeyboardButtonType::create(
          'Hablar con un asesor',
          ['url' => $url]
        ),
      ],
    ]);
    $this->tisTelegram->send(
      'En el siguiente enlace podras obtener soporte',
      $chatId,
      $replyMarkup
    );
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function showWebLink($chatId) {
    $url = $this->settings->get('website_url');
    if (empty($url)) {
      $this->logger->get('tis')->error('No se ha ingresado una url para el sitio web en la configuración.');
      return FALSE;
    }

    $replyMarkup = InlineKeyboardMarkupType::create([
      [
        InlineKeyboardButtonType::create(
          'Ir al sitio web de B2C',
          ['url' => $url]
        ),
      ],
    ]);
    $this->tisTelegram->send('En el siguiente link podras encontrar nuestro sitio web', $chatId, $replyMarkup);
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function showTermsLink($chatId) {
    $url = $this->settings->get('terms_url');
    if (empty($url)) {
      $this->logger->get('tis')->error(
        'No se ha ingresado una url para los terminos y condiciones en la configuración.'
      );
      return FALSE;
    }

    $replyMarkup = InlineKeyboardMarkupType::create([
      [
        InlineKeyboardButtonType::create(
          'Leer terminos y condiciones.',
          ['url' => $url]
        ),
      ],
    ]);
    $this->tisTelegram->send(
      'En el siguiente link podras encontrar nuestro terminos y condiciones del servicio',
      $chatId,
      $replyMarkup
    );
  }

}
