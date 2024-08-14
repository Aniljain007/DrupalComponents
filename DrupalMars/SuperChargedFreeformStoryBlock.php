<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MARS: Supercharged Freeform Story Block' Block.
 *
 * @Block(
 *   id = "supercharged_freeform_story_block",
 *   admin_label = @Translation("MARS: Supercharged Freeform Story Block"),
 *   category = @Translation("Mars Common"),
 * )
 */
class SuperChargedFreeformStoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;
  use OverrideThemeTextColorTrait;

  /**
   * Lighthouse entity browser id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_ID = 'lighthouse_browser';

  /**
   * Aligned by left side.
   */
  const LEFT_ALIGNED = 'left';

  /**
   * Aligned by right side.
   */
  const RIGHT_ALIGNED = 'right';

  /**
   * Aligned by center left.
   */
  const CENTER_LEFT_ALIGNED = 'center left';

  /**
   * Aligned by center left.
   */
  const CENTER_RIGHT_ALIGNED = 'center right';

  /**
   * Aligned by center.
   */
  const TOP_ALIGNED = 'top';

  /**
   * Aligned by center.
   */
  const BOTTOM_ALIGNED = 'bottom';

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Theme configurator parser.
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
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ThemeConfiguratorParser $theme_configurator_parser,
    LanguageHelper $language_helper,
    MediaHelper $media_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeConfiguratorParser = $theme_configurator_parser;
    $this->languageHelper = $language_helper;
    $this->mediaHelper = $media_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('mars_common.language_helper'),
      $container->get('mars_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items = [];
    // Eyebrow.
    if ($config['eyebrow']['value']) {
      $items['eyebrow']['value'] = $this->languageHelper->translate($config['eyebrow']['value']);
      $items['eyebrow']['order'] = !empty($config['sequence']['eyebrow']) ? $config['sequence']['eyebrow'] : '1';
      $items['eyebrow']['key'] = 'eyebrow';
    }
    // Title/Icon.
    if (!$config['title_icon']['icon_toggle'] && $config['title_icon']['title']) {
      $items['title']['value'] = $this->languageHelper->translate($config['title_icon']['title']);
      $items['title']['order'] = !empty($config['sequence']['title_icon']) ? $config['sequence']['title_icon'] : '2';
      $items['title']['key'] = 'title';
    }
    // Subtitle.
    if ($config['subtitle']['value']) {
      $items['subtitle']['value'] = $this->languageHelper->translate($config['subtitle']['value']);
      $items['subtitle']['order'] = !empty($config['sequence']['subtitle']) ? $config['sequence']['subtitle'] : '3';
      $items['subtitle']['key'] = 'subtitle';
    }
    // Description.
    if ($config['description']['value']['value']) {
      $items['description']['value'] = $this->languageHelper->translate($config['description']['value']['value']);
      $items['description']['order'] = !empty($config['sequence']['description']) ? $config['sequence']['description'] : '4';
      $items['description']['key'] = 'description';
      $items['description']['format'] = $config['description']['value']['format'];
    }
    // Icon/Image.
    if ($config['title_icon']['icon_toggle'] && !empty($this->configuration['title_icon']['icon_image'])) {
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['title_icon']['icon_image']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
        $items['icon_image']['icon_src'] = $mediaParams['src'];
        $items['icon_image']['icon_image_alt'] = $mediaParams['alt'];
        $items['icon_image']['order'] = !empty($config['sequence']['title_icon']) ? $config['sequence']['title_icon'] : '3';
        $items['icon_image']['key'] = 'icon_image';
      }
    }
    $build['#icon_toggle'] = !empty($config['title_icon']['icon_toggle']) ? $config['title_icon']['icon_toggle'] : '';
    // Link/Button 1.
    if ($config['with_cta'] == TRUE && $config['link_button']['link_button_1']['cta_title_1']) {
      $items['cta']['value']['cta_title_url_1']['text'] = $this->languageHelper->translate($config['link_button']['link_button_1']['cta_title_1']);
      $items['cta']['value']['cta_title_url_1']['url'] = $config['link_button']['link_button_1']['cta_url_1'];
      $items['cta']['value']['cta_title_url_1']['cta_button'] = ($config['link_button']['link_button_1']['use_link_button'] == TRUE) ? $config['link_button']['link_button_1']['use_link_button'] : FALSE;
      $items['cta']['value']['cta_title_url_1']['cta_new_window'] = $config['link_button']['link_button_1']['cta_new_window'] == 1 ? '_blank' : '_self';
      $items['cta']['value']['cta_title_url_1']['key'] = 'cta_title_url_1';
    }
    // Link/Button 2.
    if ($config['with_cta'] == TRUE && $config['link_button']['link_button_2']['cta_title_2']) {
      $items['cta']['value']['cta_title_url_2']['text'] = $this->languageHelper->translate($config['link_button']['link_button_2']['cta_title_2']);
      $items['cta']['value']['cta_title_url_2']['url'] = $config['link_button']['link_button_2']['cta_url_2'];
      $items['cta']['value']['cta_title_url_2']['cta_button'] = ($config['link_button']['link_button_2']['use_link_button'] == TRUE) ? $config['link_button']['link_button_2']['use_link_button'] : FALSE;
      $items['cta']['value']['cta_title_url_2']['cta_new_window'] = $config['link_button']['link_button_2']['cta_new_window'] == 1 ? '_blank' : '_self';
      $items['cta']['value']['cta_title_url_2']['key'] = 'cta_title_url_2';
    }
    if ($config['with_cta'] == TRUE) {
      $items['cta']['order'] = !empty($config['sequence']['cta']) ? $config['sequence']['cta'] : '5';
      $items['cta']['key'] = 'cta';
    }
    // With CTA.
    $build['#with_cta'] = ($config['with_cta'] == TRUE) ? $config['with_cta'] : 0;
    // Background Image.
    if (!empty($this->configuration['image'])) {
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['image']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
        $build['#image'] = $mediaParams['src'];
        $build['#image_alt'] = $mediaParams['alt'];
      }
    }
    // Background Image Mobile.
    if (!empty($this->configuration['imagemobile'])) {
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['imagemobile']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
        $build['#imagemobile'] = $mediaParams['src'];
        $build['#image_alt'] = $mediaParams['alt'];
      }
    }
    // Background Image Tablet.
    if (!empty($this->configuration['imagetablet'])) {
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['imagetablet']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
        $build['#imagetablet'] = $mediaParams['src'];
        $build['#image_alt'] = $mediaParams['alt'];
      }
    }
    // Brand shape.
    if ($config['background_shape'] == 1) {
      $build['#brand_shape'] = $this->themeConfiguratorParser->getBrandShapeWithoutFill();
    }
    // Enable lengthy CTA.
    $build['#supercharged_freeform_btn'] = !empty($config['supercharged_freeform_btn']) ? $config['supercharged_freeform_btn'] : '';
    // CTa up/down.
    $build['#cta_up_down'] = !empty($config['cta_up_down']) ? $config['cta_up_down'] : '';
    // Element id.
    $build['#element_id'] = $config['element_id'];
    // Block alignment.
    $build['#block_aligned'] = $config['block_aligned'];
    // Background color.
    $build['#change_background_color'] = $config['change_background_color'];
    // Text Inside Block alignment.
    $build['#text_block_alignment'] = !empty($config['text_block_alignment']) ? $config['text_block_alignment'] : '';
    // All text center align.
    $build['#text_center'] = ($config['text_center'] == 1) ? $config['text_center'] : '';
    $build['#items'] = $items;
    // High resolution image.
    $build['#high_resolution_image'] = ($config['high_resolution_image']) ?? FALSE;
    $block_build_styles = mars_common_block_build_style($config);
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'supercharged_freeform_story_block';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $character_limit_config = $this->configFactory->get('mars_common.character_limit_page');
    $config = $this->getConfiguration();
    $form['block_aligned'] = [
      '#type' => 'select',
      '#title' => $this->t('Block aligned'),
      '#default_value' => $config['block_aligned'],
      '#options' => [
        self::LEFT_ALIGNED => $this->t('Left aligned'),
        self::RIGHT_ALIGNED => $this->t('Right aligned'),
        self::CENTER_LEFT_ALIGNED => $this->t('Center left aligned'),
        self::CENTER_RIGHT_ALIGNED => $this->t('Center right aligned'),
        self::TOP_ALIGNED => $this->t('Top aligned'),
        self::BOTTOM_ALIGNED => $this->t('Bottom aligned'),
      ],
    ];
    $form['text_block_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text inside block alignment'),
      '#default_value' => $config['text_block_alignment'] ?? '',
      '#options' => [
        '' => $this->t('None'),
        'left' => $this->t('Left Text Align'),
        'center' => $this->t('Center Text Align'),
        'right' => $this->t('Right Text Align'),

      ],
    ];
    $form['text_center'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Align all text field value to the center'),
      '#default_value' => $config['text_center'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[block_aligned]"]' => ['value' => self::LEFT_ALIGNED]],
          'or',
          [':input[name="settings[block_aligned]"]' => ['value' => self::RIGHT_ALIGNED]],
        ],
      ],
    ];
    // With CTA.
    $form['with_cta'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without CTA'),
      '#default_value' => $config['with_cta'] ?? FALSE,
    ];

    // Enable lengthy CTA.
    $form['supercharged_freeform_btn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable lengthy CTA'),
      '#default_value' => $config['supercharged_freeform_btn'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_up_down'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Align CTA in two lines'),
      '#default_value' => $config['cta_up_down'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
        'invisible' => [
          [':input[name="settings[supercharged_freeform_btn]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // Font color field.
    $form['change_font_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Font Color Override'),
      '#default_value' => $config['change_font_color'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#attributes' => ['class' => ['show-clear']],
    ];
    // Background color field.
    $form['change_background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Background Color Override'),
      '#default_value' => $config['change_background_color'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to Theme configurator value.'),
      '#attributes' => ['class' => ['show-clear']],
    ];
    // Elemnt ID.
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID'),
      '#description' => $this->t('Use element ID("ele_id")directly in Page Link for Quick link component. To use the attribute as deep link reference or to use it as internal link, add #ele_id at the end of the page URL to generate the href reference of that particular component on the page. Use the URL to link the component from any of other component on same page/different page.'),
      '#default_value' => $config['element_id'] ?? '',
    ];
    $form['sequence'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Sequence'),
      '#description' => $this->t('Sequence can be defined by changing the weight value from dropdown. If you choose "one" for title and "five" for subtitle, order will be changed in the frontend.'),
    ];
    $form['sequence']['eyebrow'] = [
      '#type' => 'select',
      '#title' => $this->t('Eyebrow Weight'),
      '#default_value' => $config['sequence']['eyebrow'] ?? '1',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['title_icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Title/Icon Weight'),
      '#default_value' => $config['sequence']['title_icon'] ?? '2',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['subtitle'] = [
      '#type' => 'select',
      '#title' => $this->t('Subtitle Weight'),
      '#default_value' => $config['sequence']['subtitle'] ?? '3',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Description Weight'),
      '#default_value' => $config['sequence']['description'] ?? '4',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['cta'] = [
      '#type' => 'select',
      '#title' => $this->t('CTA Weight'),
      '#default_value' => $config['sequence']['cta'] ?? '5',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    // Eyebrow.
    $form['eyebrow'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Eyebrow'),
    ];
    $form['eyebrow']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eyebrow'),
      '#default_value' => $config['eyebrow']['value'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_eyebrow')) ? $character_limit_config->get('supercharged_freeform_story_block_eyebrow') : 60,
    ];
    // Title/Icon.
    $form['title_icon'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Title/Icon'),
    ];
    $form['title_icon']['icon_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable icon/image'),
      '#default_value' => $config['title_icon']['icon_toggle'] ?? FALSE,
    ];

    $form['title_icon']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TItle'),
      '#default_value' => $config['title_icon']['title'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_title')) ? $character_limit_config->get('supercharged_freeform_story_block_title') : 60,
      '#states' => [
        'visible' => [
          [':input[name="settings[title_icon][icon_toggle]"]' => ['checked' => FALSE]],
        ],
      ],
    ];
    // Entity Browser element for icon.
    $form['title_icon']['icon_image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
    $this->configuration['title_icon']['icon_image'], $form_state, 1, 'thumbnail', FALSE);
    $form['title_icon']['icon_image']['#type'] = 'details';
    $form['title_icon']['icon_image']['#title'] = $this->t('Icon/Image');
    $form['title_icon']['icon_image']['#open'] = TRUE;
    $form['title_icon']['icon_image']['#required'] = TRUE;
    $form['title_icon']['icon_image']['#states'] = [
      'visible' => [
        [':input[name="settings[title_icon][icon_toggle]"]' => ['checked' => TRUE]],
      ],
    ];
    // Sub title.
    $form['subtitle'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Subtitle'),
    ];
    $form['subtitle']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $config['subtitle']['value'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_subtitle')) ? $character_limit_config->get('supercharged_freeform_story_block_subtitle') : 60,
    ];
    // Description.
    $form['description'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Description'),
    ];
    $form['description']['value'] = [
      '#type' => 'text_format',
      '#format' => 'rich_text',
      '#title' => $this->t('Description'),
      '#default_value' => $config['description']['value']['value'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_description')) ? $character_limit_config->get('supercharged_freeform_story_block_description') : 1000,
    ];
    // Link/button.
    $form['link_button'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Link/Button'),
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // Link/button 1.
    $form['link_button']['link_button_1'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('CTA 1'),
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_1']['cta_title_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link Title'),
      '#default_value' => $config['link_button']['link_button_1']['cta_title_1'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_link_title')) ? $character_limit_config->get('supercharged_freeform_story_block_link_title') : 15,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_1']['cta_url_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link URL'),
      '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_link_url')) ? $character_limit_config->get('supercharged_freeform_story_block_link_url') : 2048,
      '#default_value' => $config['link_button']['link_button_1']['cta_url_1'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_1']['cta_new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open CTA link in a new tab'),
      '#default_value' => $config['link_button']['link_button_1']['cta_new_window'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_1']['use_link_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display CTA link as button'),
      '#default_value' => $config['link_button']['link_button_1']['use_link_button'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // Link/button 2.
    $form['link_button']['link_button_2'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('CTA 2'),
    ];
    $form['link_button']['link_button_2']['cta_title_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link Title'),
      '#default_value' => $config['link_button']['link_button_2']['cta_title_2'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_link_title')) ? $character_limit_config->get('supercharged_freeform_story_block_link_title') : 15,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_2']['cta_url_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link URL'),
      '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
      '#maxlength' => !empty($character_limit_config->get('supercharged_freeform_story_block_link_url')) ? $character_limit_config->get('supercharged_freeform_story_block_link_url') : 2048,
      '#default_value' => $config['link_button']['link_button_2']['cta_url_2'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_2']['cta_new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open CTA link in a new tab'),
      '#default_value' => $config['link_button']['link_button_2']['cta_new_window'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['link_button']['link_button_2']['use_link_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display CTA link as button'),
      '#default_value' => $config['link_button']['link_button_2']['use_link_button'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // Background image.
    $form['image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
    $this->configuration['image'], $form_state, 1, 'thumbnail', TRUE);
    $form['image']['#type'] = 'details';
    $form['image']['#title'] = $this->t('Background Image');
    $form['image']['#open'] = TRUE;
    $form['image']['#required'] = TRUE;
    // Background image Mobile.
    $form['imagemobile'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
    $this->configuration['imagemobile'], $form_state, 1, 'thumbnail', FALSE);
    $form['imagemobile']['#type'] = 'details';
    $form['imagemobile']['#title'] = $this->t('Background Image (Mobile)');
    $form['imagemobile']['#open'] = TRUE;
    $form['imagemobile']['#required'] = FALSE;
    // Background image Tablet.
    $form['imagetablet'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
    $this->configuration['imagetablet'], $form_state, 1, 'thumbnail', FALSE);
    $form['imagetablet']['#type'] = 'details';
    $form['imagetablet']['#title'] = $this->t('Background Image (Tablet)');
    $form['imagetablet']['#open'] = TRUE;
    $form['imagetablet']['#required'] = FALSE;
    // Background shape.
    $form['background_shape'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Background shape'),
      '#default_value' => $config['background_shape'] ?? FALSE,
    ];
    // Higher resolution image.
    $form['high_resolution_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Full-Width Image for higher resolutions'),
      '#default_value' => $config['high_resolution_image'] ?? FALSE,
      '#description' => $this->t('Please tick the checkbox if you want to see the image populated end to end of your screen for all different higher desktop screen resolutions.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $title_toggle = $form_state->getValue('title_icon')['icon_toggle'];
    $title = $form_state->getValue('title_icon')['title'];
    $sequence = [];
    // Title validation.
    if ($title_toggle == 0 && !$title && empty($title)) {
      $form_state->setErrorByName('title_icon][title', $this->t('Title label is required'));
    }
    // Icon.
    $icon_id = $form_state->getValue('title_icon')['icon_image']['selected'];
    if ($title_toggle == 1 && !$icon_id && empty($icon_id)) {
      $form_state->setErrorByName('title_icon][icon_image', $this->t('Icon/Image field is required'));
    }
    // weight.
    $sequence['eyebrow'] = $form_state->getValue('sequence')['eyebrow'];
    $sequence['title_icon'] = $form_state->getValue('sequence')['title_icon'];
    $sequence['subtitle'] = $form_state->getValue('sequence')['subtitle'];
    $sequence['description'] = $form_state->getValue('sequence')['description'];
    $sequence['cta'] = $form_state->getValue('sequence')['cta'];
    $sequence_unique = array_unique($sequence);
    $sequence_duplicates = array_diff_assoc($sequence, $sequence_unique);
    foreach ($sequence_duplicates as $sequence_duplicates_keys => $sequence_duplicates_value) {
      if ($sequence_duplicates_keys == 'eyebrow') {
        $form_state->setErrorByName('sequence][eyebrow', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'title_icon') {
        $form_state->setErrorByName('sequence][title_icon', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'subtitle') {
        $form_state->setErrorByName('sequence][subtitle', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'description') {
        $form_state->setErrorByName('sequence][description', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'cta') {
        $form_state->setErrorByName('sequence][cta', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockSubmit() method can be used to submit the form value.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
    $icon_array = ['title_icon', 'icon_image'];
    $title_icon = $this->getEntityBrowserValue($form_state, $icon_array);
    $this->configuration['title_icon']['icon_image'] = $title_icon;
    $this->configuration['image'] = $this->getEntityBrowserValue($form_state, ['image']);
    $this->configuration['imagemobile'] = $this->getEntityBrowserValue($form_state, ['imagemobile']);
    $this->configuration['imagetablet'] = $this->getEntityBrowserValue($form_state, ['imagetablet']);
  }

}
