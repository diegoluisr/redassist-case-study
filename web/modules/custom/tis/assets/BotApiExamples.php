<?php

/**
 * @file
 * The url you wish to send the POST request to.
 */

/**
 * The register for the commands.
 */
// @codingStandardsIgnoreStart
function registerCommands() {
  $url = "https://api.telegram.org/bot5155701758:AAE26mJ34-C-dmzzfX0nxF9uc_sT6y50eF3/setMyCommands";

  $commands = [
        ['command' => 'start', 'description' => 'cerrar una nueva venta.'],
        ['command' => 'options', 'description' => 'Crear una nueva venta.'],
        ['command' => 'inline', 'description' => 'cerrar una nueva venta.'],
        ['command' => 'weather', 'description' => 'cerrar una nueva venta.'],
        ['command' => 'other', 'description' => 'cerrar una nueva venta.'],

  ];

  $fields = [];
  $fields['commands'] = json_encode($commands);

  sendToTelegramBot($url, $fields);
}

/**
 * To get the commands.
 */
function getCommands() {
  $url = "https://api.telegram.org/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/getMyCommands";
  return json_decode(sendToTelegramBot($url, ''));
}

/**
 *
 */
function debug($log) {
  file_put_contents('debug.log', print_r($log, TRUE) . "\n", FILE_APPEND);
}

/**
 * To send to telegram bot.
 */
function sendToTelegramBot($url, $fields) {

  // url-ify the data for the POST.
  $fields_string = http_build_query($fields);

  // Open connection.
  $ch = curl_init();

  // Set the url, number of POST vars, POST data.
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

  // So that curl_exec returns the contents of the cURL; rather than echoing it.
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  // Execute post.
  $result = curl_exec($ch);
  // debug($result);
  return $result;
}

/**
 *
 */
function sendResponse($msg, $keyboard = '') {
  $url = "https://api.telegram.org/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/sendmessage";
  $chatId = '1208796669';

  // The data you want to send via POST.
  $fields = [
    'chat_id' => $chatId,
    'parse_mode' => 'HTML',
    'text' => $msg,
  ];

  if (!empty($keyboard)) {
    $fields['reply_markup'] = json_encode($keyboard);
  }
  else {
    $replyKeyboardRemove = [
      'remove_keyboard' => TRUE,
    ];

    $fields['reply_markup'] = json_encode($replyKeyboardRemove);
  }

  sendToTelegramBot($url, $fields);
}

/**
 *
 */
function sendAnswerInlineKeyboard($callbackId, $msg) {
  $url = "https://api.telegram.org/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/answerCallbackQuery";

  // The data you want to send via POST.
  $fields = [
    'callback_query_id' => $callbackId,
    'text' => $msg,
        // 'show_alert' => true,
  ];

  // debug($fields);
  sendToTelegramBot($url, $fields);
}

/**
 *
 */
function sendAnswerCallbackQuery($callbackId, $msg) {
  $url = "https://api.telegram.org/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/answerCallbackQuery";
  $gameUrl = "https://sharkhelpers.com/webhook/game.php?uuid=dmzzfX0nxF9uc_sT7y50eF4";

  // The data you want to send via POST.
  $fields = [
    'callback_query_id' => $callbackId,
    'text' => $msg,
    'url' => $gameUrl,
    'show_alert' => TRUE,
  ];

  // debug($fields);
  sendToTelegramBot($url, $fields);
}

/**
 *
 */
function getFileWithCurl($url, $fileName) {
  $ch = curl_init($url);
  $fp = fopen('/var/www/html/webhook/assets/' . $fileName, 'wb');
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
}

/**
 * To donwload a file.
 */
function downloadFile($fileID, $fileName = '') {
  $url = "https://api.telegram.org/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/getFile";

  $fields = [
    'file_id' => $fileID,
  ];

  $res = json_decode(sendToTelegramBot($url, $fields));

  $urlFile = 'https://api.telegram.org/file/bot5255801758:AAE26mJ34-C-dmzzfX0nxF9uc_sT7y50eF4/';
  $urlFile .= $res->result->file_path;

  debug($urlFile);

  if (empty($fileName)) {
    $fileName = basename($urlFile);
  }

  $fileName = str_replace(' ', '_', $fileName);

  try {
    getFileWithCurl($urlFile, $fileName);
    debug("File downloaded successfully");
  }
  catch (Exception $e) {
    debug('Caught exception: ', $e->getMessage(), "\n");
  }

  sendResponse('aqui quedo: ' . 'https://sharkhelpers.com/webhook/assets/' . $fileName);
}

$update = json_decode(file_get_contents("php://input"), TRUE);
debug($update);

$chatId = $update['message']['chat']['id'];
$message = $update['message']['text'];
$callback_query = $update["callback_query"];
$photo = $update['message']['photo'];
$document = $update['message']['document'];

if (is_array($callback_query)) {
  // sendResponse('estamos procesando tu peticion...');.
  if (isset($callback_query['data'])) {
    switch ($callback_query['data']) {
      case "newsale":
        sendAnswerInlineKeyboard($callback_query['id'], 'quiere guardar una nueva venta');
        sendResponse('vamos a empezar, ingresa tu cedula');
        break;

      case "otras":
        sendAnswerInlineKeyboard($callback_query['id'], 'quiere guardar ver otras cosas');
        break;

      default:
        sendAnswerInlineKeyboard($callback_query['id'], 'que me dices? intentan de nuevo.');
        break;
    }
  }

  if (isset($callback_query['game_short_name'])) {
    switch ($callback_query['game_short_name']) {
      case "B2CSignature":
        sendAnswerCallbackQuery($callback_query['id'], 'Lito... empecemos!');
        break;

      default:
        sendResponse('ese jueguito no lo tengo.');
        break;
    }
  }
}

if (is_array($photo)) {
  $fileId = end($photo)['file_id'];
  downloadFile($fileId);
}

if (is_array($document)) {
  downloadFile($document['file_id'], $document['file_name']);
}

if (strpos($message, "/start") === 0) {
  // Register bot commands.
  registerCommands();

  // Create keyboard.
  $keyboard = [
    'inline_keyboard' =>
        [
            [
                ['text' => 'Nueva venta si pilla', 'callback_data' => 'newsale'],
            ],
            [
                ['text' => 'Otras opcones pa ver', 'callback_data' => 'otras'],
            ],
        ],
  ];

  // sendResponse('hola bitch', $keyboard);.
}

if (strpos($message, "/weather") === 0) {

  // Create keyboard.
  $keyboard = [
    "inline_keyboard" => [
            [
                [
                  "text" => "Yes",
                  "callback_data" => "yes",
                ],
                [
                  "text" => "No",
                  "callback_data" => "no",
                ],
                [
                  "text" => "Stop",
                  "callback_data" => "stop",
                ],
            ],
    ],
  ];

  sendResponse('te gusto?', $keyboard);
}

if ($message === "🏀 Quina") {
  sendResponse('le dio a Quina');
}

if ($message === "quina") {
  sendResponse('le dio a quina');
}

if (strpos($message, "/other") === 0) {

  // Create keyboard.
  $keyboard = [
    'keyboard' => [
            ['⚽️ Mega-Sena', '🏀 Quina', ' 🏈 Other'],
            ['⚾️ Mega-Sena2', '🥎 Quina2', '🎾 Other2'],
    ],
    'one_time_keyboard' => TRUE,
  ];

  sendResponse('cual quiere?', $keyboard);
}

if (strpos($message, "/inline") === 0) {

  // Create keyboard.
  $keyboard = [
    'inline_keyboard' =>
        [
            [
                ['text' => 'Lindo', 'callback_data' => 'lindo'],
                ['text' => 'Feo', 'callback_data' => 'feo'],
                ['text' => 'Quina', 'callback_data' => 'quina'],
            ],
        ],
  ];

  sendResponse('cual quiere?', $keyboard);
}

if ($message === "Yes, you rigth!") {
  sendResponse('asi es');
}

if (strpos($message, "/options") === 0) {

  // Create keyboard.
  $keyboard = [
    'keyboard' => [
            ['Yes, you rigth!'],
            ['Try again bitch'],
            ['No way.'],
    ],
    'one_time_keyboard' => TRUE,
  ];

  sendResponse('cual quiere?', $keyboard);
}

if (strpos($message, "/commands") === 0) {
  $commands = getCommands();
  // debug($commands);
  $html = '';
  foreach ($commands->result as $command) {
    $html .= '/' . $command->command . ': ' . $command->description . PHP_EOL;
  }
  sendResponse($html);
}

if (strpos($message, "/download") === 0) {
  $fileId = trim(str_replace("/download", '', $message));
  debug($fileId);

  if (!empty($fileId)) {
    downloadFile($fileId);
  }
}

if (strpos($message, "/signature") === 0) {
  $keyboard = [
    'inline_keyboard' =>
        [
            [
              [
                'text' => 'Si, vamos a firmar',
                'url' => 'http://stage-multib2c.calipso.com.co/sale/b2c/427',
              ],
            ],
        ],
  ];

  sendResponse('Listo para firmar?', $keyboard);
}
// @codingStandardsIgnoreEnd
