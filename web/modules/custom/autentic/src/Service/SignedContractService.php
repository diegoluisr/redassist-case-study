<?php

namespace Drupal\autentic\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a repository for Block config entities.
 */
class SignedContractService {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * B2cHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Function to create a signer.
   */
  public function createSigner(string $email, string $name, string $docid, string $mobile, string $txid) {
    // Create paragraph (autentic_signer).
    $signer = $this->entityTypeManager->getStorage('paragraph')->create([
      'type' => 'autentic_signer',
      'field_autentic_sign_email' => $email,
      'field_autentic_sign_name' => $name,
      'field_autentic_sign_docid' => $docid,
      'field_autentic_sign_phone' => $mobile,
      'field_autentic_sign_txid' => $txid,
    ]);

    $signer->save();

    return $signer;
  }

  /**
   * Function to create Signed Contract (Media).
   */
  public function createContract(File $originalFile, array $signers = []) {

    if (count($signers) === 0) {
      return FALSE;
    }

    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->entityTypeManager->getStorage('media')->create([
      'type' => 'signed_document',
    ]);

    $media->get('field_original_contract')->appendItem([
      'target_id' => $originalFile->id(),
      'display' => 1,
    ]);

    foreach ($signers as $signer) {
      $media->get('field_signers')->appendItem([
        'target_id' => $signer->id(),
        'target_revision_id' => $signer->getRevisionId(),
      ]);
    }

    $media->save();

    return $media;
  }

  /**
   * Function to request an OTP generation.
   */
  public function requestSignerOtp(Paragraph $signer) {
  }

  /**
   * Function to request a contract signature.
   */
  public function requestContratSignature(Media $media) {
  }

}
