<?php

namespace Drupal\contract;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for contract_contract entity.
 *
 * @ingroup contract
 */
class ContractListBuilder extends EntityListBuilder {

  /**
   * Var that store the url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlgen;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlgen
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $urlgen) {
    parent::__construct($entity_type, $storage);
    $this->urlgen = $urlgen;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contract list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Id');
    $header['name'] = $this->t('Name');
    $header['bundle'] = $this->t('Bundle');
    $header['uid'] = $this->t('User');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\contract\Entity\Contract $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->name->value;
    $row['bundle'] = $entity->bundle();
    $row['uid'] = $entity->getOwner()->label();
    $row['status'] = $entity->status->value;

    return $row + parent::buildRow($entity);
  }

}
