<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\mars_common\LanguageHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Theme override block.
 *
 * @Block(
 *   id = "theme_override_block",
 *   admin_label = @Translation("MARS: Theme Override block"),
 *   category = @Translation("Mars Common"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Current Node"))
 *   }
 * )
 */
class ThemeOverrideBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * Entity type manager.
   *
   * @var \Drupal\core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    UuidInterface $uuid,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPathStack = $current_path;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('mars_common.language_helper'),
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('uuid'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['theme_override_node'] = [
      '#type' => 'select',
      '#title' => $this->t('Base Theme Override'),
      '#options' => $this->getCampaignNodeList(),
      '#default_value' => $config['theme_override_node'] ?? NULL,
    ];
    $form['theme_override_configuration'] = [
      '#type' => 'value',
      '#value' => $config['theme_override_configuration'] ?? NULL,
    ];
    $form['current_page_node_id'] = [
      '#type' => 'value',
      '#value' => $config['current_page_node_id'] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockSubmit() method can be used to the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $current_path = $this->currentPathStack->getPath();
    $path_arr = explode('/', $current_path);
    $node_str = preg_grep('/node./', $path_arr);
    $current_page_nid = str_replace('node.', '', implode('', $node_str));
    $theme_override_node_id = $form_state->getValue('theme_override_node');
    $source_node = $this->entityTypeManager->getStorage('node')->load($theme_override_node_id);
    $source_layout = $source_node->get('layout_builder__layout')->getValue();
    $theme_configuration_block_config = [];

    if (!empty($source_layout)) {
      foreach ($source_layout as $section) {
        if (isset($section['section']) && $section['section']->getLayoutId() == 'campaign_page_theme_configurator') {
          $components = $section['section']->getComponents();
          foreach ($components as $component) {
            if ($component instanceof SectionComponent && $component->getPluginId() === 'theme_configuration_block') {
              $theme_configuration_block_config = $component->get('configuration');
            }
          }
        }
      }
    }

    if (!empty($current_page_nid) && !empty($theme_configuration_block_config)) {
      $values['theme_override_configuration'] = $theme_configuration_block_config;
      $values['current_page_node_id'] = $current_page_nid;
    }

    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCampaignNodeList() {
    $node_list = [];
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('type', 'campaign');
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      if (!empty($nodes)) {
        $node_list[NULL] = $this->t('Select');
        foreach ($nodes as $key => $node) {
          $node_list[$key] = $node->getTitle();
        }
      }
    }
    return $node_list;
  }

}
