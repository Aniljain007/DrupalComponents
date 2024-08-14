<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\MenuBuilder;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\ThemeConfiguratorService;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Footer Block.
 *
 * @Block(
 *   id = "footer_block",
 *   admin_label = @Translation("MARS: Footer block"),
 *   category = @Translation("Global elements"),
 * )
 */
class FooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use OverrideThemeTextColorTrait;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;
  /**
   * Theme configuration service.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorService
   */
  protected $themeConfiguratorService;
  /**
   * Menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * ThemeConfiguratorParser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Menu builder service.
   *
   * @var \Drupal\mars_common\MenuBuilder
   */
  private $menuBuilder;

  /**
   * File url generator service.
   *
   * @var Drupal\Core\File\FileUrlGenerator
   */
  private $fileUrlGenerator;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Custom cache tag.
   *
   * @var string
   */
  const CUSTOM_CACHE_TAG = 'custom_region_cache';

  /**
   * Vocabulary id of taxonomy terms region.
   *
   * @var string
   */
  const VID_TAXONOMY_REGION = 'mars_regions';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'cta_button_target' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ThemeConfiguratorService $theme_configurator_service,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageHelper $language_helper,
    ThemeConfiguratorParser $themeConfiguratorParser,
    MenuBuilder $menu_builder,
    ConfigFactoryInterface $config,
    FileUrlGenerator $file_generator,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeConfiguratorService = $theme_configurator_service;
    $this->menuStorage = $entity_type_manager->getStorage('menu');
    $this->themeConfiguratorParser = $themeConfiguratorParser;
    $this->languageHelper = $language_helper;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->config = $config;
    $this->menuBuilder = $menu_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_generator;
    $this->currentUser = $current_user;
  }

  /**
   * Get footer logo settings data.
   */
  protected function getFooterLogoData(string $key, array $config = NULL) {
    return $this->getData('footer_logo', $key, $config);
  }

  /**
   * Get data from the passed config array or from current theme.
   */
  protected function getData(string $subject, string $key, array $config = NULL) {
    return !empty($config[$subject][$key]) ? $config[$subject][$key] : "";
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mars_common.theme_configurator_service'),
      $container->get('entity_type.manager'),
      $container->get('mars_common.language_helper'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('mars_common.menu_builder'),
      $container->get('config.factory'),
      $container->get('file_url_generator'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();

    // Footer settings data.
    $build['#footer_logo_toogle'] = $conf['footer_logo']['footer_logo_toogle'] ?? FALSE;
    $build['#remove_social_icon_space'] = $conf['remove_social_icon_space'] ?? FALSE;
    $build['#remove_top_footer_space'] = $conf['remove_top_footer_space'] ?? FALSE;
    $build['#footer_logo_alt'] = !empty($this->getFooterLogoData('footer_logo_alt', $conf)) ? $this->getFooterLogoData('footer_logo_alt', $conf) : strtolower((string) $this->languageHelper->translate('footer logo'));
    $build['#footer_logo_id'] = $conf['footer_logo_id'] ?? "";
    $build['#footer_logo_path'] = $conf['footer_logo_path'] ?? "";
    $build['#logo'] = $this->themeConfiguratorParser->getLogoFromTheme();

    $theme_logo_alt = $this->themeConfiguratorParser->getLogoAltFromTheme();
    $build['#logo_alt'] = ($theme_logo_alt)
      ? $this->languageHelper->translate($theme_logo_alt)
      : $theme_logo_alt;

    // Get brand border path.
    $build['#brand_border'] = $this->themeConfiguratorParser->getBrandBorder();
    $build['#cookie_banner_brand_border'] = $this->themeConfiguratorParser->getSettingValue('cookie_banner_brand_border');
    // Get cookie banner override toggle value.
    $build['#cookie_banner_override'] = $this->themeConfiguratorParser->getSettingValue('cookie_banner_override');
    $build['#top_footer_menu'] = $this->menuBuilder->getMenuItemsArray($conf['top_footer_menu']);
    $build['#legal_links'] = $this->menuBuilder->getMenuItemsArray($conf['legal_links']);
    // Get current year and update copyright in marketing value.
    $current_year = date("Y");
    $marketing_value = preg_replace("/\b\d{4}\b/", $current_year, $conf['marketing']['value']);
    $build['#marketing'] = $this->languageHelper->translate($marketing_value);
    $build['#corporate_tout_text'] = $this->languageHelper->translate($conf['corporate_tout']['title']);
    $build['#corporate_tout_url'] = [
      'href' => $conf['corporate_tout']['url'],
      'name' => $build['#corporate_tout_text'],
    ];

    $label_config = $this->config->get('mars_common.site_labels');
    $region_title = $label_config->get('footer_region');
    $social_header = $label_config->get('footer_social_header');
    ;
    $build['#region_title'] = $this->languageHelper->translate($region_title);
    $build['#social_header'] = $this->languageHelper->translate($social_header);

    $build['#social_links'] = [];
    if ($conf['social_links_toggle']) {
      $build['#social_links'] = $this->themeConfiguratorParser->socialLinks();
    }
    if ($conf['region_selector_toggle']) {
      $terms = $this->termStorage->loadTree(self::VID_TAXONOMY_REGION, 0, NULL, TRUE);
      $build['#region_selector'] = [];
      if (!empty($terms)) {
        foreach ($terms as $term) {
          $term = $this->languageHelper->getTranslation($term);
          $region_url = '#';
          $url = $term->get('field_mars_url')->first();
          if (!is_null($url)) {
            $region_url = $url->getUrl();
          }
          $build['#region_selector'][] = [
            'title' => $term->getName(),
            'url' => $region_url,
          ];
        }
        $terms_objects = $this->termStorage->loadByProperties([
          'vid' => self::VID_TAXONOMY_REGION,
          'field_default_region' => TRUE,
        ]);
        if ($terms_objects) {
          /** @var \Drupal\taxonomy\TermInterface $default_region */
          $default_region = reset($terms_objects);
          $default_region = $this->languageHelper->getTranslation($default_region);
          $build['#current_region_title'] = $default_region->getName();
        }
      }
    }

    // Language selector link in footer.
    $build['#footer_language_selector_lable_link'] = '';
    $language_selector_config = $this->config->get('mars_common.geoip');
    if ($language_selector_config->get('enabled') && $language_selector_config->get('use_language_selector') && $conf['footer_language_selector_toggle']) {
      $all_languages = mars_common_get_country_language_links();
      $anonymous = $this->currentUser->isAnonymous();
      if (count($all_languages) > 1 && $anonymous) {
        $build['#footer_language_selector_lable_link'] = $this->languageHelper->translate($conf['footer_language_selector_label']);
      }
    }

    $theme_settings = $this->config->get('emulsifymars.settings');

    $build['#text_color_override'] = FALSE;
    if (!empty($theme_settings->get('footer_menu_text_color'))) {
      $build['#text_color_override'] = $theme_settings->get('footer_menu_text_color');
    }
    elseif (!empty($conf['override_text_color']['override_color'])) {
      $build['#text_color_override'] = static::$overrideColor;
    }

    $build['#hover_color'] = FALSE;
    if (!empty($theme_settings->get('footer_menu_hover_color'))) {
      $build['#hover_color'] = $theme_settings->get('footer_menu_hover_color');
    }
    elseif (!empty($conf['override_text_color']['сhoose_override_hover']) &&
      !empty($conf['override_text_color']['hover_color'])
    ) {
      $build['#hover_color'] = $conf['override_text_color']['hover_color'];
    }

    $build['#cta_button_label'] = isset($conf['cta_button_label']) ? $this->languageHelper->translate($conf['cta_button_label']) : strtoupper($this->languageHelper->translate('See All'));
    $build['#cta_button_target'] = ($conf['cta_button_target'] == TRUE) ? '_blank' : '_self';
    CacheableMetadata::createFromRenderArray($build)
      ->merge(
        $this->themeConfiguratorParser->getCacheMetadataForThemeConfigurator()
      )
      ->addCacheableDependency($label_config)
      ->applyTo($build);
    $block_build_styles = mars_common_block_build_style($conf);
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'footer_block';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->config->get('mars_common.character_limit_page');
    $language_selector_config = $this->config->get('mars_common.geoip');

    $form['top_footer_menu'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'menu',
      '#title' => $this->t('Top Footer menu'),
      '#required' => TRUE,
      '#default_value' => isset($config['top_footer_menu']) ? $this->menuStorage->load($this->configuration['top_footer_menu']) : NULL,
    ];
    $form['legal_links'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'menu',
      '#title' => $this->t('Legal links menu'),
      '#required' => TRUE,
      '#default_value' => isset($config['legal_links']) ? $this->menuStorage->load($this->configuration['legal_links']) : NULL,
    ];
    $form['marketing'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Marketing & Copyright Messaging'),
      '#default_value' => $config['marketing']['value'] ?? '',
      '#format' => $config['marketing']['format'] ?? 'plain_text',
    ];
    $form['corporate_tout'] = [
      '#type' => 'details',
      '#title' => $this->t('Mars corporate tout'),
      '#open' => TRUE,
    ];
    $form['corporate_tout']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Link URL'),
      '#maxlength' => !empty($character_limit_config->get('footer_block_link_url')) ? $character_limit_config->get('footer_block_link_url') : 2048,
      '#default_value' => $config['corporate_tout']['url'] ?? '',
    ];
    $form['corporate_tout']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title'),
      '#maxlength' => !empty($character_limit_config->get('footer_block_link_title')) ? $character_limit_config->get('footer_block_link_title') : 2048,
      '#default_value' => $config['corporate_tout']['title'] ?? '',
    ];
    $form['social_links_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display social links'),
      '#default_value' => $config['social_links_toggle'] ?? TRUE,
    ];
    $form['region_selector_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display region selector'),
      '#default_value' => $config['region_selector_toggle'] ?? TRUE,
    ];
    if ($language_selector_config->get('enabled') && $language_selector_config->get('use_language_selector')) {
      $form['footer_language_selector_toggle'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display language selector link'),
        '#default_value' => $config['footer_language_selector_toggle'] ?? FALSE,
      ];
      $form['footer_language_selector_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Language selector link title'),
        '#default_value' => !empty($config['footer_language_selector_label']) ? $config['footer_language_selector_label'] : $this->t('Choose your language'),
        '#states' => [
          'visible' => [
            [':input[name="settings[footer_language_selector_toggle]"]' => ['checked' => TRUE]],
          ],
          'required' => [
            [':input[name="settings[footer_language_selector_toggle]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
    }
    $form['remove_social_icon_space'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove space when social links disabled for Mobile/Tablet.'),
      '#default_value' => $config['remove_social_icon_space'] ?? FALSE,
    ];
    $form['remove_top_footer_space'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove space when Top Footer disabled for Mobile/Tablet.'),
      '#default_value' => $config['remove_top_footer_space'] ?? FALSE,
    ];

    $this->buildOverrideColorElement($form, $config);

    $form['override_text_color']['сhoose_override_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Сhoose an alternative color to override the on-hover'),
      '#default_value' => $config['override_text_color']['сhoose_override_hover'] ?? NULL,
    ];

    $form['override_text_color']['hover_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Сhoose color B on-hover'),
      '#default_value' => $config['override_text_color']['hover_color'] ?? NULL,
      '#states' => [
        'visible' => [
          [':input[name="settings[override_text_color][сhoose_override_hover]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['cta_button_label'] = [
      '#title' => $this->languageHelper->translate('CTA button label'),
      '#type' => 'textfield',
      '#size' => 200,
      '#default_value' => $config['cta_button_label'] ?? strtoupper($this->languageHelper->translate('See All')),
    ];

    $form['cta_button_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open CTA link in a new tab'),
      '#default_value' => $config['cta_button_target'],
    ];
    // Footer logo settings.
    $form['footer_logo'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Footer logo settings'),
      '#open'        => TRUE,
    ];
    $form['footer_logo']['footer_logo_toogle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display footer logo'),
      '#default_value' => $config['footer_logo']['footer_logo_toogle'] ?? FALSE,
    ];
    $form['footer_logo']['upload_footer_logo'] = [
      '#title'           => $this->t('Upload footer logo'),
      '#type'            => 'managed_file',
      '#description'     => $this->t('Will be designed by each brand team.
      Size and format requirements detailed out in the Style Guide.'),
      '#upload_location' => 'public://theme_config/',
      '#required'        => FALSE,
      '#process'         => [
        [ManagedFile::class, 'processManagedFile'],
        [$this->themeConfiguratorService, 'processImageWidget'],
      ],
      '#upload_validators' => [
        'file_validate_extensions' => ['svg'],
      ],
      '#theme'               => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#default_value'       => $this->getFooterLogoData('upload_footer_logo', $config),
    ];
    $form['footer_logo']['footer_logo_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative image text'),
      '#default_value' => !empty($this->getFooterLogoData('footer_logo_alt', $config)) ? $this->getFooterLogoData('footer_logo_alt', $config) : strtolower((string) $this->languageHelper->translate('footer logo')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $upload_footer_logo = [];
    $footer_settings_data = $form_state->getValue('footer_logo', 'upload_footer_logo');
    if (!empty($footer_settings_data['upload_footer_logo'][0])) {
      $id = $footer_settings_data['upload_footer_logo'][0];
      $file = $this->entityTypeManager->getStorage('file')->load($id);
      $file_uri = $file->getFileUri();
      $file_path = $this->fileUrlGenerator->generateString($file_uri);
      $file->setPermanent();
      $file->save();
      $upload_footer_logo['file_id'] = [$file->id()];
      $upload_footer_logo['file_path'] = $file_path;
    }
    $this->setConfiguration($form_state->getValues());
    $this->configuration['footer_logo_id'] = !empty($upload_footer_logo['file_id'][0]) ? $upload_footer_logo['file_id'][0] : "";
    $this->configuration['footer_logo_path'] = !empty($upload_footer_logo['file_path']) ? $upload_footer_logo['file_path'] : "";
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Include taxonomies
    // update process dependencies cache.
    $cache_tags = Cache::mergeTags($cache_tags, [self::CUSTOM_CACHE_TAG]);
    return $cache_tags;
  }

}
