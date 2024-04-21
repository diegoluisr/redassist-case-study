<?php

namespace Drupal\contract\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contract entity.
 *
 * @ingroup contract
 */
interface ContractInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Sets the name.
   *
   * @param string $name
   *   The name.
   */
  public function setName($name);

  /**
   * Gets name.
   *
   * @return string
   *   The name.
   */
  public function getName();

  /**
   * Gets Owner.
   *
   * @return object
   *   Creation Owner uid.
   */
  public function getOwner();

  /**
   * Sets the Owner.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user.
   *
   * @return object
   *   The called Owner Id.
   */
  public function setOwner(UserInterface $account);

  /**
   * Gets the Owner Id.
   *
   * @return int
   *   Creation Owner Id.
   */
  public function getOwnerId();

  /**
   * Sets the Owner id.
   *
   * @param int $uid
   *   The user uid.
   *
   * @return int
   *   The called Owner Id.
   */
  public function setOwnerId($uid);

  /**
   * Set the bundle that should be used.
   *
   * @param string $bundle
   *   The language to load from the message template when fetching the text.
   */
  public function setBundle($bundle);

  /**
   * Gets bundle.
   *
   * @return string
   *   The bundle entity.
   */
  public function getBundle();

  /**
   * Set the language that should be used.
   *
   * @param string $language
   *   The language to load from the message template when fetching the text.
   */
  public function setLanguage($language);

}
