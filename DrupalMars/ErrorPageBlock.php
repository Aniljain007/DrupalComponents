<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a parent page header block.
 *
 * @Block(
 *   id = "error_page_block",
 *   admin_label = @Translation("MARS: Error Page Block"),
 *   category = @Translation("Mars Common"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Error page"))
 *   }
 * )
 */
class ErrorPageBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;

  /**
   * Lighthouse entity browser image id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_IMAGE_ID = 'lighthouse_browser';

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * ThemeConfiguratorParser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * Mars Media Helper service.
   *
   * @var \Drupal\mars_media\MediaHelper
   */
  protected $mediaHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('mars_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MenuLinkTreeInterface $menu_link_tree,
    ThemeConfiguratorParser $themeConfiguratorParser,
    MediaHelper $media_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
    $this->themeConfiguratorParser = $themeConfiguratorParser;
    $this->mediaHelper = $media_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if ($node) {
      $build['#title'] = $node->title->value;
      $build['#body'] = $node->body->value;
      $media_id = $this->mediaHelper->getEntityMainMediaId($node);
      $media_params = $this->mediaHelper->getMediaParametersById($media_id);
      if (!($media_params['error'] ?? FALSE) && ($media_params['src'] ?? FALSE)) {
        $build['#image_src'] = $media_params['src'];
        $build['#image_alt'] = $media_params['alt'];
      }
    }

    $linksMenu = $this->buildMenu('error-page-menu');
    $links = [];
    foreach ($linksMenu as $linkMenu) {
      $links[] = [
        'content' => $linkMenu['title'],
        'attributes' => [
          'target' => '_self',
          'href' => $linkMenu['url'],
        ],
      ];
    }

    $build['#links'] = $links;
    $build['#graphic_divider'] = $this->themeConfiguratorParser->getGraphicDivider();
    $build['#brand_shape'] = $this->themeConfiguratorParser->getBrandShapeWithoutFill();
    $build['#theme'] = 'error_page_block';

    return $build;
  }

  /**
   * Render menu by its name.
   *
   * @param string $menu_name
   *   Menu name.
   *
   * @return array
   *   Rendered menu.
   */
  protected function buildMenu($menu_name) {
    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMaxDepth(1);

    // Get the tree.
    $tree = $this->menuLinkTree->load($menu_name, $menu_parameters);

    // Apply some manipulators (checking the access, sorting).
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    // And the last step is to actually build the tree.
    $menu = $this->menuLinkTree->build($tree);
    $menu_links = [];
    if (!empty($menu['#items'])) {
      foreach ($menu['#items'] as $item) {
        array_push($menu_links, [
          'title' => $item['title'],
          'url' => $item['url']->setAbsolute()->toString(),
        ]);
      }
    }
    return $menu_links;
  }

}
