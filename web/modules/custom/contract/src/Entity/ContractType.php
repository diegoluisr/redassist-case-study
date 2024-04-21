<?php

namespace Drupal\contract\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Contract Type.
 *
 * @ConfigEntityType(
 *   id = "contract_type",
 *   label = @Translation("Contract Type"),
 *   bundle_of = "contract",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   config_prefix = "contract_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "langcode"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contract\ContractTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contract\Form\ContractTypeEntityForm",
 *       "add" = "Drupal\contract\Form\ContractTypeEntityForm",
 *       "edit" = "Drupal\contract\Form\ContractTypeEntityForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "canonical" = "/admin/structure/contract_type/{contract_type}",
 *     "add-form" = "/admin/structure/contract_type/add",
 *     "edit-form" = "/admin/structure/contract_type/{contract_type}/edit",
 *     "delete-form" = "/admin/structure/contract_type/{contract_type}/delete",
 *     "collection" = "/admin/structure/contract_type",
 *   }
 * )
 */
class ContractType extends ConfigEntityBase implements ContractTypeInterface {

  /**
   * The Example ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Example label.
   *
   * @var string
   */
  protected $label;

}
