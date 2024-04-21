<?php

namespace Drupal\contract\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\core_event_dispatcher\FormHookEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber Class.
 */
final class AlterContractForm extends FormAlterEvent implements EventSubscriberInterface {

  /**
   * Var ConfigFactoryInterface.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Var CurrentRouteMatch.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Var EntityTypeManagerInterface.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HookFormAlterSubscriber constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CurrentRouteMatch $route_match,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FormHookEvents::FORM_ALTER => 'alterForm',
    ];
  }

  /**
   * Implementing hook_form_alter()
   */
  public function alterForm(FormAlterEvent $event) {
    $form = &$event->getForm();

    $formIds = [
      'contract_default_add_form',
      'contract_default_edit_form',
      'contract_seller_add_form',
      'contract_seller_edit_form',
      'contract_sale_add_form',
      'contract_sale_edit_form',
    ];

    if (in_array($event->getFormId(), $formIds)) {
      $source = $this->configFactory->get('yaml_editor.config')->get('editor_source');
      $form['#attached']['drupalSettings']['yamlEditor']['source'] = $source;

      $form['#attached']['library'][] = 'yaml_editor/yaml_editor';

      $form['manifest']['widget'][0]['value']['#attributes'] += [
        'data-yaml-editor' => 'true',
      ];
    }
  }

}
