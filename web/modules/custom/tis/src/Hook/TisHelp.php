<?php

namespace Drupal\tis\Hook;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class detail.
 */
class TisHelp extends Hook {

  use StringTranslationTrait;

  /**
   * Variable that store the module handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler.
   */
  public function __construct(ModuleHandler $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Function detail.
   */
  public function call(array $params = []) {
    $variables = [
      ':toolbar' => Url::fromRoute('help.page', ['name' => 'tis'])->toString(),
      ':automated_cron' => ($this->moduleHandler->moduleExists('automated_cron')) ? Url::fromRoute('help.page', ['name' => 'automated_cron'])->toString() : '#',
    // \Drupal::moduleHandler()
    ];
    $output = '';
    $output .= '<h3>' . $this->t('About') . '</h3>';
    $output .= '<p>' . $this->t('The Admin Toolbar module enhances the <a href=":toolbar">Toolbar</a> module by providing fast access to all the administrative links at the top of your site. Admin Toolbar remains a very "lightweight" module by closely integrating with all Toolbar functionality. It can be used in conjunction with all the sub modules included on Admin Toolbar, for quick access to system commands such as Flush all caches, <a href=":automated_cron">Run cron</a>, Run Updates, etc.', $variables) . '</p>';
    $output .= '<h3>' . $this->t('Uses') . '</h3>';
    $output .= '<p>' . $this->t('The Admin Toolbar greatly improves the user experience for those who regularly interact with the site Toolbar by providing fast, full access to all links in the site Toolbar without having to click to get there.') . '</p>';
    return $output;
  }

}
