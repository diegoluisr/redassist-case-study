<?php

namespace Drupal\contract\Command;

use Drupal\autentic\Service\AutenticService;
use Drupal\b2c\Service\PdfGeneratorService;
use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use Drupal\Component\Serialization\Yaml;
use Drupal\digitalsign\Service\DigitalSign;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush command file.
 *
 * @package Drupal\contract\Command
 */
class ContractCommand extends DrushCommands {

  /**
   * Variable that store the autentic service.
   *
   * @var \Drupal\autentic\Service\AutenticService
   */
  protected $autentic;

  /**
   * Variable that store the contract service.
   *
   * @var \Drupal\digitalsign\Service\DigitalSign
   */
  protected $digitalSignService;

  /**
   * Variable that store the filesystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Variable that store the pdfgenerator service.
   *
   * @var \Drupal\b2c\Service\PdfGeneratorService
   */
  protected $pdfgen;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AutenticService $autentic,
    DigitalSign $digitalSignService,
    FileSystemInterface $fileSystem,
    PdfGeneratorService $pdfgen
  ) {
    $this->autentic = $autentic;
    $this->digitalSignService = $digitalSignService;
    $this->fileSystem = $fileSystem;
    $this->pdfgen = $pdfgen;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
      $container->get('autentic.service'),
      $container->get('digitalsign.service'),
      $container->get('file_system'),
      $container->get('b2c.pdf_generator')
    );
  }

  /**
   * Drush command to send message via telgram.
   *
   * @command contract:lauchqualityworker
   * @aliases csigndoc
   * @usage contract:lauchqualityworker
   */
  public function lauchQualityWorker() {
    /** @var \Drupal\digitalsign\Plugin\QueueWorker\QualityApprovedContractQueue $qaWorker */
    $this->logger->notice('launch worker');
  }

  /**
   * Drush command to generate a pdf document.
   *
   * @param string $uuid
   *   The contract id to get the multimedia document.
   *
   * @command contract:generatepdf
   * @aliases cgeneratepdf
   * @usage contract:generatepdf uuid
   */
  public function generatePdf($uuid = '427') {

    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalSignService->loadContractByUuid($uuid);
    if (!$contract->hasField('manifest')) {
      return;
    }
    $manifest = $contract->get('manifest')->getValue()[0]['value'];

    if (is_null($manifest)) {
      $this->logger->notice($manifest);
      return;
    }

    $manifest = Yaml::decode($manifest);

    /** @var \Drupal\b2c\Service\PdfGeneratorService $pdfgen */
    // $pdfgen = \Drupal::service('b2c.pdf_generator');
    $pdfgen = $this->pdfgen;
    $isGenerated = $pdfgen->contractGen($uuid, 'contract-draft', $manifest);
    if ($isGenerated) {
      $this->logger->notice('El pdf fue generado: ' . print_r($isGenerated, TRUE));
    }
  }

  /**
   * Drush command to update contract pdf with singed ones.
   *
   * @param string $uuid
   *   The contract id to get the multimedia document.
   *
   * @command contract:updatesignedpdfs
   * @aliases cupdatesignedpdfs
   * @usage contract:updatesignedpdfs --uuid
   */
  public function updateSignedPdfs($uuid = '2eb2c225-14fc-4362-a7ec-847ba9d0d4f3') {

    /** @var \Drupal\digitalsign\Service\DigitalSign $digitalSignService */
    // $digitalSignService = \Drupal::service('digitalsign.service');
    $digitalSignService = $this->digitalSignService;
    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $digitalSignService->loadContractByUuid($uuid);

    if (!$contract->hasField('manifest')) {
      return;
    }

    $manifest = $contract->get('manifest')->getValue()[0]['value'];

    if (is_null($manifest)) {
      $this->logger->notice($manifest);
      return;
    }

    $manifest = Yaml::decode($manifest);

    /** @var \Drupal\autentic\Service\AutenticService $autentic */
    // $autentic = \Drupal::service('autentic.service');
    $autentic = $this->autentic;

    $files = [];
    // If ($contract->hasField('docs')) {
    // $documents = $contract->get('docs');.
    // foreach ($documents as $document) {
    // $docTargetID = $document->getValue()[0]['target_id'];
    // // @todo La linea anterior esta retornando null.
    // $files[] = $autentic->getFileBitesArray($docTargetID);
    // }
    // }
    // -> use un id de su sistema.
    $docTargetID = '4452';
    $files[] = $autentic->getFileBitesArray($docTargetID);

    if (empty($files)) {
      $this->logger->notice('No hay archivos en la seccion de documentos del contrato.');
    }

    $signed = $autentic->documentSignature(
      [
        'names' => implode(' ', [
          $manifest['signers'][0]['basic_info']['firstname'],
          $manifest['signers'][0]['basic_info']['secondname'],
        ]),
        'lastNames' => implode(' ', [
          $manifest['signers'][0]['basic_info']['lastname'],
          $manifest['signers'][0]['basic_info']['second_lastname'],
        ]),
        'docId' => $manifest['signers'][0]['basic_info']['docid']['num'],
      ],
      $files
    );

    if (empty($signed)) {
      $this->logger->notice('Los documentos no fueron firmados digitalmente.');
    }

    // /** @var \Drupal\autentic\Service\AutenticService $autentic */
    // $autentic = \Drupal::service('autentic.service');
    // $files = [];
    // $files[] = $autentic->getFileBitesArray('4443');
    // ->Debe usar un id de su sistema de archivos.
    // $signed = $autentic->documentSignature(
    // [
    // 'names' => 'Diego Luis',
    // 'lastNames' => 'Restrepo Urrea',
    // 'docId' => '9733464',
    // ],
    // $files
    // );
    // If (empty($signed)) {
    // $this->logger->notice('Los documentos no fueron firmados digitalmente.');
    // return;
    // }.
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    // $fileSystem = \Drupal::service('file_system');
    $fileSystem = $this->fileSystem;

    // Get file from autentic service.
    $fileDirectory = 'private://contract-signed/';
    $fileSystem->prepareDirectory(
      $fileDirectory,
      $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS
    );
    $tmpZipFile = $uuid . '_signed_pdfs.zip';
    $downloadSignedZip = file_put_contents($fileDirectory . $tmpZipFile, fopen($signed['urlDocuments'], 'r'));

    if (!$downloadSignedZip) {
      $this->logger->notice('No se pudo descargar el zip.');
      return;
    }

    // Unzip file.
    $zip = new \ZipArchive();
    $res = $zip->open($tmpZipFile);
    $tmpZipFileFolder = $uuid . '_signed_pdfs';
    if ($res === TRUE) {
      $zip->extractTo($fileDirectory . '/' . $tmpZipFileFolder);
      $zip->close();
      $this->logger->notice('Los pdf firmados fueron descomprimidos.');
    }

    /*
     * @TODO: Agregar al contrato el los pdfs que fueron enviando por autentic
     * y estan en el folder private/contract-signed/[contract_id]_signed_pdfs/
     */
    // $this->logger->notice(print_r($manifest, TRUE));
  }

  /**
   * Drush command to send message via telgram.
   *
   * @command contract:savenewmanifest
   * @aliases csavenm
   * @usage contract:savenewmanifest --uuid
   */
  public function saveNewManifest($options = [
    'uuid' => '53efc9ba-8369-4293-8817-9054620f14df',
  ]) {
    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalSignService->loadContractByUuid($options['uuid']);

    if (!$contract->hasField('manifest')) {
      return;
    }

    $signer = [
      'basic_info' => [
        'name' => [
          'first' => 'DIEGO',
          'second' => 'LUIS',
        ],
        'lastname' => [
          'first' => 'RESTREPO',
          'second' => 'URREA',
        ],
        'docid' => [
          'type' => '1',
          'name' => 'Cédula de ciudadanía',
          'num' => '9735036',
          'expedition_city' => 'bogota',
        ],
        'genre' => '1264',
      ],
      'contact_info' => [
        'email' => 'the_maurogo@hotmail.com',
        'phone' => '7315671',
        'mobile' => '3167588466',
        'state' => [
          'id' => '2510',
          'name' => 'Tolima',
          'novassist' => '',
        ],
        'city' => [
          'id' => '2532',
          'name' => 'Ibagué',
          'novassist' => '73001',
        ],
        'address' => [
          'full' => 'calle 20 # 20-20',
          'extra' => 'bloque 20',
        ],
        'comune' => [
          'id' => '12345',
          'name' => 'Fontibón',
        ],
        'neighborhood' => 'Hayuelos',
      ],
      'legal' => [
        'terms' => [
          'autorización_empresa'  => '1',
          'contrato_prestacion'  => '1',
          'politica_de_datos'  => '1',
          'contrato_vinculacion'  => '1',
          'autenticacion_firma'  => '1',
        ],
        'signature' => 'data:image/png;base64,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',
      ],
    ];

    $manifest = [
      'signers' => [$signer],
      'order' => [
        'id' => '45',
        'contract' => [
          'id' => '330077',
          'uuid' => $options['uuid'],
          'serial' => 'B2C-45',
          'nips' => [
            'nip1' => '1000000K',
            'nip2' => '',
            'nip3' => '55',
            'nip4' => '',
            'nip5' => '',
          ],
          'assistance' => [
            'id' => '1303',
            'name' => 'Acueducto Plan 1',
            'cicle' => '55',
            'novassist' => 'ACUP1',
            'mandate' => [
              'id' => '1291',
              'name' => 'Acueducto de Bogota',
              'novassist' => '24',
            ],
            'stratus' => '3',
            'ownership' => [
              'type' => 'owned',
              'name' => 'Propia',
            ],
          ],
        ],
      ],
      'date' => [
        'datetime' => '1631750985',
        'day' => '01',
        'month' => '02',
        'year' => '2021',
      ],
      'seller' => [
        'uid' => '427',
        'email' => 'vendedor_digital@b2c.net.co',
        'name' => 'Vendedor Digital',
        'docid' => '111111111111',
      ],
      'txs' => [],
    ];

    $contract->set('manifest', Yaml::encode($manifest));
    $contract->save();
  }

  /**
   * Drush command to send message via telgram.
   *
   * @command contract:seenewmanifest
   * @aliases csavenm
   * @usage contract:seenewmanifest --uuid
   */
  public function seeNewManifest($options = [
    'uuid' => '53efc9ba-8369-4293-8817-9054620f14df',
  ]) {
    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalSignService->loadContractByUuid($options['uuid']);

    if (!$contract->hasField('manifest')) {
      return;
    }

    $manifest = $contract->get('manifest')->getValue()[0]['value'];

    if (is_null($manifest)) {
      $this->logger->notice($manifest);
      return;
    }

    $manifest = Yaml::decode($manifest);

    $this->logger->notice('new manifest: ' . print_r($manifest, TRUE));

  }

  /**
   * Drush command to update phone in a contract.
   *
   * @command contract:updatemanifesttotest
   * @aliases csavenm
   * @usage contract:updatemanifesttotest --uuid --docid --mobile
   */
  public function updateManifestToTest($options = [
    'uuid' => '5a934cd3-6c84-4677-9f07-6718b86fbe82',
    'docid' => '9735036',
    'mobile' => '2333445564',
    'contract' => '9735036',
  ]) {
    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalSignService->loadContractByUuid($options['uuid']);

    if (!$contract->hasField('manifest')) {
      return;
    }

    $manifest = $contract->get('manifest')->getValue()[0]['value'];

    if (is_null($manifest)) {
      $this->logger->notice($manifest);
      return;
    }

    $manifest = Yaml::decode($manifest);
    $manifest['signers'][0]['basic_info']['docid']['num'] = $options['docid'];
    $manifest['signers'][0]['contact_info']['mobile'] = $options['mobile'];
    $manifest['signers'][0]['contact_info']['email'] = 'the_maurogo@hotmail.com';
    $manifest['order']['contract']['number'] = $options['contract'];
    $manifest['order']['contract']['nips']['nip1'] = $options['contract'];

    $contract->set('manifest', Yaml::encode($manifest));
    $contract->save();

    $this->logger->notice('new manifest: ' . print_r($manifest, TRUE));
  }

  /**
   * Drush command to update contract status.
   *
   * @command contract:updatecontractstatus
   * @aliases cucs
   * @usage contract:updatecontractstatus --uuid --status
   */
  public function updateContractStatus($options = [
    'uuid' => 'd2e18f10-b1cd-4b9d-a049-fdc38acdba5b',
    'status' => DigitalSign::STATUS_CONTRACT_APPROVED,
  ]) {

    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalSignService->loadContractByUuid($options['uuid']);
    $contract->set('status', $options['status']);
    $updated = $contract->save();
    $this->logger->notice('contract status updated: ' . print_r($updated, TRUE));
  }

}
