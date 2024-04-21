<?php

namespace Drupal\contract\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Defines the Contract entity.
 *
 * @ingroup contract
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "contract",
 *   label = @Translation("Contract"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contract\ContractListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\contract\Form\ContractForm",
 *       "add" = "Drupal\contract\Form\ContractForm",
 *       "edit" = "Drupal\contract\Form\ContractForm",
 *       "delete" = "Drupal\contract\Form\ContractDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\contract\ContractAccessControlHandler",
 *   },
 *   base_table = "contract",
 *   admin_permission = "administer contract entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "bundle" = "bundle",
 *   },
 *   links = {
 *     "canonical" = "/contract/{contract}",
 *     "add-page" = "/contract/add",
 *     "add-form" = "/contract/add/{contract_type}",
 *     "edit-form" = "/contract/{contract}/edit",
 *     "delete-form" = "/contract/{contract}/delete",
 *     "collection" = "/admin/content/contract/list"
 *   },
 *   bundle_entity_type = "contract_type",
 *   field_ui_base_route = "entity.contract_type.edit_form",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.contract.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Contract' class defines methods and fields for the contract entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContractInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class Contract extends ContentEntityBase implements ContractInterface {

  // Implements methods defined by EntityChangedInterface.
  use EntityChangedTrait;

  /**
   * The language to use when fetching text from the message template.
   *
   * @var string
   */
  protected $language = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the uid entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    $this->set('bundle', $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->get('bundle')->getValue()[0]['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage($language) {
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setDescription(new TranslatableMarkup('The ID of the Contract entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setDescription(new TranslatableMarkup('The UUID of the Contract entity.'))
      ->setReadOnly(TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setSetting('target_type', 'contract_type')
      ->setTargetBundle('contract_type')
      ->setTargetEntityTypeId('contract_type')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // Name field for the contract.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The name of the Contract entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Gender field for the contract.
    // ListTextType with a drop down menu widget.
    // The values shown in the menu are 'male' and 'female'.
    // In the view the field content is shown as string.
    // In the form the choices are presented as options list.
    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The title of this node, always treated as non-markup plain text.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => 'new',
        'max_length' => 40,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setReadOnly(TRUE);

    $fields['manifest'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Manifest'))
      ->setDescription(new TranslatableMarkup('The title of this node, always treated as non-markup long plain text.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the contract.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('User Name'))
      ->setDescription(new TranslatableMarkup('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the contract.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['docs'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Documents'))
      ->setDescription(new TranslatableMarkup('The documents related to the contract'))
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'contract' => 'contract',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Owner field of the contract.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['attackments'] = BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Attachments'))
      ->setDescription(new TranslatableMarkup('The attachments related to the contract'))
      ->setSettings([
        'uri_scheme' => 'public',
        'description_field' => 0,
        'file_extensions' => 'pdf png jpg jpeg',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'file',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Language code'))
      ->setDescription(new TranslatableMarkup('The language code of Contract entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    return $fields;
  }

}
