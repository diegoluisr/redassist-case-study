<?php

namespace Drupal\gsuite\Service;

/**
 * Gmail service class.
 */
class Gmail extends AbstractGsuiteService {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultScopes() {
    return [
      \Google_Service_Gmail::MAIL_GOOGLE_COM,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getService($userId = NULL) {
    if ($this->service instanceof \Google_Service_Gmail) {
      return $this->service;
    }

    $this->service = new \Google_Service_Gmail($this->getClient($userId));

    return $this->service;
  }

  /**
   * Get list of all available messages for a user.
   *
   * @param string $userId
   *   Email address of the user.
   * @param array $parameters
   *   Array of parameters.
   *
   * @return array|bool
   *   Array of messages or false.
   */
  public function listMessages($userId, array $parameters) {
    try {
      $gMailService = $this->getService($userId);

      $messageList = $gMailService
        ->users_messages
        ->listUsersMessages($userId, $parameters);

      $messages = [];
      foreach ($messageList->getMessages() as $message) {
        $messageObject = $this->getMessage($userId, $message->getId());
        $messages[] = [
          'id' => $message->getId(),
          'snippet' => $messageObject['snippet'],
        ];
      }

      return [
        'size_estimate' => $messageList['resultSizeEstimate'],
        'messages' => $messages,
      ];

    }
    catch (\Exception $exception) {
      $this->logger->error(
        'Failed to get messages - Error: {message}',
        ['message' => $exception->getMessage()]
          );
    }

    return FALSE;
  }

  /**
   * Get message with ID.
   *
   * @param string $userId
   *   User’s email address.
   *   The special value ‘me’ can be used to indicate the authenticated user.
   * @param string $messageId
   *   ID of Message to get.
   *
   * @return \Google_Service_Gmail_Message
   *   Gmail message object.
   */
  public function getMessage($userId, $messageId) {
    try {
      return $this->getService($userId)->users_messages->get($userId, $messageId);
    }
    catch (\Exception $exception) {
      $this->logger->error(
        'Failed to get message: {id} - Error: {message}',
        ['id' => $messageId, 'message' => $exception->getMessage()]
          );
    }
  }

}
