<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FullWidthCarouselBlock is used for Full width Carousel component logic.
 *
 * @Block(
 *   id = "full_width_carousel_block",
 *   admin_label = @Translation("MARS: Fullwidth Carousel component"),
 *   category = @Translation("Page components"),
 * )
 *
 * @package Drupal\mars_common\Plugin\Block
 */
class FullWidthCarouselBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;

  /**
   * Lighthouse entity browser id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_ID = 'lighthouse_browser';

  /**
   * Lighthouse entity browser video id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_VIDEO_ID = 'lighthouse_video_browser';

  /**
   * Key option video.
   */
  const KEY_OPTION_VIDEO = 'video';

  /**
   * Key option image.
   */
  const KEY_OPTION_IMAGE = 'image';

  /**
   * Default background style.
   */
  const KEY_OPTION_OTHER_COLOR = 'other';

  /**
   * Default background style.
   */
  const KEY_OPTION_TEXT_COLOR_DEFAULT = 'color_e';

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Theme configurator parser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * File url generator service.
   *
   * @var Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
    MediaHelper $media_helper,
    ThemeConfiguratorParser $theme_configurator_parser,
    FileUrlGenerator $file_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
    $this->mediaHelper = $media_helper;
    $this->themeConfiguratorParser = $theme_configurator_parser;
    $this->fileUrlGenerator = $file_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('mars_common.language_helper'),
      $container->get('mars_media.media_helper'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items = [];
    $build = [];
    foreach ($config['carousel'] as $key => $item_value) {
      $title_override = !empty($item_value['next_line_label']['value']) ? $item_value['next_line_label']['value'] : '';
      $item = [
        'background_assets' => $this->getBgAssets($key),
        'icon_image_assets' => $this->getBgAssets($key, TRUE),
        'content' => $this->languageHelper->translate($item_value['description']),
        'description_read_more' => $item_value['description_read_more'] ?? FALSE,
        'video' => ($item_value['item_type'] == self::KEY_OPTION_VIDEO),
        'image' => ($item_value['item_type'] == self::KEY_OPTION_IMAGE),
        'hide_volume' => !empty($item_value['hide_volume']) ? TRUE : FALSE,
        'eyebrow' => $item_value['eyebrow'] ?? '',
        'weight' => $item_value['weight'],
        'title_url' => $item_value['url'] ?? '',
        'title_label' => $this->languageHelper->translate($item_value['label']) ?? '',
        'title_label_override' => $this->languageHelper->translate($title_override),
        'cta_url' => ['href' => $item_value['cta']['url']] ?? '',
        'cta_title' => $this->languageHelper->translate($item_value['cta']['title']) ?? '',
        'link_url_new_tab' => $item_value['cta']['link_url_new_tab'] == 1 ? '_blank' : '_self',
        'item_cta_bg_color' => $item_value['cta']['item_cta_bg_color'] ?? '',
        'item_cta_text_color' => $item_value['cta']['item_cta_text_color'] ?? '',
        'stop_autoplay' => !empty($item_value['stop_autoplay']) ? TRUE : FALSE,
        'use_dark_overlay' => $item_value['use_dark_overlay'] ? TRUE : FALSE,
        'text_block_alignment' => $item_value['text_block_alignment'] ?? '',
        'text_alignment' => $this->languageHelper->translate($item_value['text_alignment']) ?? '',
        'tablet_image_size' => $this->languageHelper->translate($item_value['tablet_image_size']) ?? '',
        'block_type' => $this->languageHelper->translate($item_value['block_type']) ?? '',
        'styles' => 'color:' . $this->getTextColor($key),
        'graphic_divider' => $this->themeConfiguratorParser->getGraphicDivider(),
        'eyebrow_override_letter_case' => $item_value['eyebrow_override_letter_case'] ?? FALSE,
        'img_clickable' => (array_key_exists('img_clickable', $item_value) ? $item_value['img_clickable'] : '') == 1 && $item_value['item_type'] == self::KEY_OPTION_IMAGE ? 1 : 0,
        'carousel_item_bg_color' => $item_value['carousel_item_bg_color'] ?? '',
      ];
      $items[] = $item;
    }
    if ($items) {
      usort($items, fn($a, $b) => $a['weight'] <=> $b['weight']);
    }
    $build['#use_navigation_disable'] = $this->config['use_navigation_disable'] ?? FALSE;
    $build['#brand_borders'] = $this->themeConfiguratorParser->getBrandBorder();
    $build['#enable_set_slider_time'] = !empty($config['enable_set_slider_time']) ? $config['enable_set_slider_time'] : FALSE;
    $build['#set_slider_time'] = $config['enable_set_slider_time'] == TRUE && !empty($config['set_slider_time']) ? $config['set_slider_time'] : '';
    $build['#high_resolution_image'] = !empty($config['high_resolution_image']) ? $config['high_resolution_image'] : FALSE;
    $build['#attached']['drupalSettings']['full_width_carousel_block']['fullwidth_loop'] = TRUE;
    $build['#description_read_more_label'] = $config['description_read_more_label'] ?? '';
    $build['#element_id'] = $config['element_id'] ?? '';
    $build['#title'] = $this->languageHelper->translate($config['carousel_label'] ?? '');
    $build['#carousel_version'] = !empty($config['version']['carousel_version']) ? $config['version']['carousel_version'] : 'version1';
    $build['#image_position'] = !empty($config['version']['image_position']) ? $config['version']['image_position'] : 'top';
    $build['#hide_show_arrow'] = !empty($config['hide_show_arrow']) ? $config['hide_show_arrow'] : FALSE;
    $build['#items'] = $items;
    $build['#use_cta_background_text_color'] = $config['cta_bg_text'] ?? FALSE;
    $block_build_styles = mars_common_block_build_style($config);
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'fullwidth_carousel_component';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->get('mars_common.character_limit_page');

    $form['carousel_label'] = [
      '#title'         => $this->t('Carousel title'),
      '#type'          => 'textfield',
      '#default_value' => $config['carousel_label'],
      '#maxlength' => !empty($character_limit_config->get('carousel_component_title')) ? $character_limit_config->get('carousel_component_title') : 55,
    ];

    $form['version'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Carousel version'),
    ];
    $form['version']['carousel_version'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Carousel version'),
      '#description' => $this->t("Version 1 - Default existing Layout and features will be applied. <br />
        Version 2 - Option to Toggle the Image and Text Positions. <br />
        When Version 2 is selected it's suggested to add description and images consistently for all carousel items for best performance."),
      '#options' => [
        'version1' => $this->t('Version 1'),
        'version2' => $this->t('Version 2'),
      ],
      '#default_value' => $config['version']['carousel_version'] ?? 'version1',
    ];
    $form['version']['image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image/Video position of carousel items for Mobile'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $config['version']['image_position'] ?? 'top',
      '#states' => [
        'visible' => [
          [':input[name="settings[version][carousel_version]"]' => ['value' => 'version2']],
        ],
      ],
    ];

    $form['carousel'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Carousel items'),
      '#prefix' => '<div id="carousel-wrapper">',
      '#suffix' => '</div>',
    ];

    $submitted_input = $form_state->getUserInput()['settings'] ?? [];
    $saved_items = !empty($config['carousel']) ? $config['carousel'] : [];
    $submitted_items = $submitted_input['carousel'] ?? [];
    $current_items_state = $form_state->get('carousel_storage');

    if (empty($current_items_state)) {
      if (!empty($submitted_items)) {
        $current_items_state = $submitted_items;
      }
      else {
        $current_items_state = $saved_items;
      }
    }

    $form_state->set('carousel_storage', $current_items_state);

    foreach ($current_items_state as $key => $value) {
      $form['carousel'][$key] = [
        '#type' => 'details',
        '#title' => $this->t('Carousel items'),
        '#open' => TRUE,
      ];

      $form['carousel'][$key]['item_type'] = [
        '#title' => $this->t('Carousel item type'),
        '#type' => 'select',
        '#required' => TRUE,
        '#default_value' => $config['carousel'][$key]['item_type'] ?? self::KEY_OPTION_IMAGE,
        '#options' => [
          self::KEY_OPTION_IMAGE => $this->t('Image'),
          self::KEY_OPTION_VIDEO => $this->t('Video'),
        ],
      ];
      $form['carousel'][$key]['img_clickable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable to make image clickable'),
        '#default_value' => $config['carousel'][$key]['img_clickable'] ?? FALSE,
        '#description'   => $this->t("Use to make image as clickable. The image URL will be same as CTA link URL and if CTA is not required, remove CTA Link title and retain only the URL for image to be clickable."),
        '#states' => [
          'visible' => [
            [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
          ],
        ],
      ];
      $form['carousel'][$key]['eyebrow'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Eyebrow'),
        '#maxlength' => !empty($character_limit_config->get('carousel_component_eyebrow')) ? $character_limit_config->get('carousel_component_eyebrow') : 15,
        '#default_value' => $config['carousel'][$key]['eyebrow'] ?? '',
        '#states' => [
          'required' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['eyebrow_override_letter_case'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override letter case for Eyebrow'),
        '#default_value' => $config['carousel'][$key]['eyebrow_override_letter_case'] ?? FALSE,
      ];
      $form['carousel'][$key]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title label'),
        '#maxlength' => !empty($character_limit_config->get('carousel_component_title_label')) ? $character_limit_config->get('carousel_component_title_label') : 55,
        '#description' => $this->t('Default label for title, if choose override title label option make this field empty.'),
        '#default_value' => $config['carousel'][$key]['label'] ?? '',
        '#states' => [
          'visible' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#default_value' => $config['carousel'][$key]['weight'] ?? 0,
        '#delta' => !empty($character_limit_config->get('carousel_component_weight')) ? $character_limit_config->get('carousel_component_weight') : 15,
        '#description' => $this->t('Items with weight -5/-4/-3 etc will appear before items with weight -1/0/1/2/3 etc in an ascending order of their defined weight.'),
      ];
      // Title override.
      $form['carousel'][$key]['next_line_label'] = [
        '#type' => 'text_format',
        '#format' => 'rich_text',
        '#maxlength' => !empty($character_limit_config->get('carousel_component_override_title_label')) ? $character_limit_config->get('carousel_component_override_title_label') : 200,
        '#title' => $this->t('Override Title label'),
        '#description' => $this->t('The Site admin will be able to add up to 200 characters including the HTML tags and 55 characters excluding the HTML tags in CK editor for Title override functionality. Please preview the changes made in the layout page before saving the changes to align to the format.'),
        '#default_value' => $config['carousel'][$key]['next_line_label']['value'] ?? '',
        '#states' => [
          'visible' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title Link URL'),
        '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
        '#maxlength' => !empty($character_limit_config->get('carousel_component_title_link_url')) ? $character_limit_config->get('carousel_component_title_link_url') : 2048,
        '#default_value' => $config['carousel'][$key]['url'] ?? '',
        '#states' => [
          'required' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['description'] = [
        '#title' => $this->t('Carousel item description'),
        '#type' => 'textarea',
        '#default_value' => $config['carousel'][$key]['description'] ?? NULL,
        '#maxlength' => !empty($character_limit_config->get('carousel_item_description')) ? $character_limit_config->get('carousel_item_description') : 255,
      ];
      $form['carousel'][$key]['description_read_more'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show Read more text in description for mobile'),
        '#default_value' => $config['carousel'][$key]['description_read_more'] ?? FALSE,
      ];

      $current_icon_selection = !empty($config['carousel'][$key]['icon_image']) ? $config['carousel'][$key]['icon_image'] : NULL;
      $form['carousel'][$key]['icon_image'] = $this->getEntityBrowserForm(
        self::LIGHTHOUSE_ENTITY_BROWSER_ID,
        $current_icon_selection,
        $form_state,
        1,
        'thumbnail',
        FALSE,
      );
      $form['carousel'][$key]['icon_image']['#type'] = 'details';
      $form['carousel'][$key]['icon_image']['#title'] = $this->t('Icon/Logo');
      $form['carousel'][$key]['icon_image']['#open'] = TRUE;

      $form['carousel'][$key]['cta'] = [
        '#type' => 'details',
        '#title' => $this->t('CTA'),
        '#open' => TRUE,
      ];
      $form['carousel'][$key]['cta']['url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link URL'),
        '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
        '#maxlength' => !empty($character_limit_config->get('carousel_component_cta_link_url')) ? $character_limit_config->get('carousel_component_cta_link_url') : 2048,
        '#default_value' => $config['carousel'][$key]['cta']['url'] ?? '',
        '#states' => [
          'required' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['cta']['link_url_new_tab'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Open CTA Link URL in new tab'),
        '#default_value' => $config['carousel'][$key]['cta']['link_url_new_tab'] ?? FALSE,
      ];
      $form['carousel'][$key]['cta']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link Title'),
        '#maxlength' => !empty($character_limit_config->get('carousel_component_cta_link_title')) ? $character_limit_config->get('carousel_component_cta_link_title') : 15,
        '#default_value' => $config['carousel'][$key]['cta']['title'] ?? '',
        '#states' => [
          'required' => [
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
            'or',
            [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['cta']['item_cta_bg_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('CTA Background color'),
        '#default_value' => $config['carousel'][$key]['cta']['item_cta_bg_color'] ?? NULL,
        '#attributes' => ['class' => ['show-clear']],
        '#description' => $this->t('If this field is left empty, it falls back to Global color set in this <a href="#override">block configuration</a>.'),
      ];
      $form['carousel'][$key]['cta']['item_cta_text_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('CTA Text color'),
        '#default_value' => $config['carousel'][$key]['cta']['item_cta_text_color'] ?? NULL,
        '#attributes' => ['class' => ['show-clear']],
        '#description' => $this->t('If this field is left empty, it falls back to Global color set in this <a href="#override">block configuration</a>.'),
      ];

      // Device specific Image elements.
      foreach (MediaHelper::LIST_IMAGE_RESOLUTIONS as $resolution) {
        $name = 'image';

        if ($resolution != 'desktop') {
          $name = 'image_' . $resolution;
        }

        $image_default = $config['carousel'][$key][$name] ?? NULL;

        // Entity Browser element for background image.
        $validate_callback = FALSE;
        if ($resolution == 'desktop') {
          $validate_callback = function ($form_state) use ($key) {
            $item_type = $form_state->getValue([
              'settings',
              'carousel',
              $key,
              'item_type',
            ]);
            return $item_type === self::KEY_OPTION_IMAGE;
          };
        }

        $form['carousel'][$key][$name] = $this->getEntityBrowserForm(
          self::LIGHTHOUSE_ENTITY_BROWSER_ID,
          $image_default,
          $form_state,
          1,
          'thumbnail',
          $validate_callback
        );

        // Convert the wrapping container to a details element.
        $form['carousel'][$key][$name]['#type'] = 'details';
        $form['carousel'][$key][$name]['#required'] = ($resolution == 'desktop');
        $form['carousel'][$key][$name]['#title'] = $this->t('Background Image (@resolution)', ['@resolution' => ucfirst($resolution)]);
        $form['carousel'][$key][$name]['#open'] = TRUE;
        $form['carousel'][$key][$name]['#states'] = [
          'visible' => [
            [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
          ],
        ];

      }

      // Video element.
      $current_video_selection = $config['carousel'][$key]['video'] ?? NULL;
      if (!is_string($current_video_selection)) {
        $current_video_selection = NULL;
      }
      $form['carousel'][$key]['video'] = $this->getEntityBrowserForm(
        self::LIGHTHOUSE_ENTITY_BROWSER_VIDEO_ID,
        $current_video_selection,
        $form_state,
        1,
        'default',
        function ($form_state) use ($key) {
          $type = $form_state->getValue([
            'settings',
            'carousel',
            $key,
            'item_type',
          ]);
          return $type === self::KEY_OPTION_VIDEO;
        }
      );
      $form['carousel'][$key]['video']['#type'] = 'details';
      $form['carousel'][$key]['video']['#title'] = $this->t('List item video');
      $form['carousel'][$key]['video']['#open'] = TRUE;
      $form['carousel'][$key]['video']['#states'] = [
        'visible' => [
          [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
      ];
      $form['carousel'][$key]['hide_volume'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide Volume'),
        '#default_value' => $config['carousel'][$key]['hide_volume'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['stop_autoplay'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Stop Autoplay'),
        '#default_value' => $config['carousel'][$key]['stop_autoplay'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
      $form['carousel'][$key]['tablet_image_size'] = [
        '#type' => 'select',
        '#title' => $this->t('Tablet Image Sizes'),
        '#options' => [
          'tablet_1180' => $this->t('1180*600'),
          'tablet_1024' => $this->t('1024*600'),
          'tablet_768' => $this->t('768*600'),
        ],
        '#default_value' => $config['carousel'][$key]['tablet_image_size'] ?? NULL,
      ];
      $form['carousel'][$key]['text_color'] = [
        '#type' => 'radios',
        '#title' => $this->t('Text color'),
        '#options' => $this->getTextColorOptions(),
        '#default_value' => $config['carousel'][$key]['text_color'] ?? NULL,
      ];

      $form['carousel'][$key]['text_color_other'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Custom text color'),
        '#default_value' => $config['carousel'][$key]['text_color_other'] ?? NULL,
        '#attributes' => ['class' => ['show-clear']],
        '#description' => $this->t('If this field is left empty, it falls back to Theme colors.'),
        '#states' => [
          'visible' => [
            [':input[name="settings[carousel][' . $key . '][text_color]"]' => ['value' => self::KEY_OPTION_OTHER_COLOR]],
          ],
        ],
      ];
      $form['carousel'][$key]['carousel_item_bg_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Carousel Background color'),
        '#default_value' => $config['carousel'][$key]['carousel_item_bg_color'] ?? NULL,
        '#attributes' => ['class' => ['show-clear']],
        '#description' => $this->t('If this field is left empty, it falls back to Global color set in this <a href="#override">block configuration</a>.'),
      ];

      $form['carousel'][$key]['text_alignment'] = [
        '#type' => 'radios',
        '#title' => $this->t('Text alignment'),
        '#default_value' => $config['carousel'][$key]['text_alignment'] ?? 'left',
        '#options' => [
          'left' => $this->t('Left'),
          'center' => $this->t('Center'),
          'right' => $this->t('Right'),
        ],
      ];
      $form['carousel'][$key]['text_block_alignment'] = [
        '#type' => 'select',
        '#title' => $this->t('Text alignment inside block'),
        '#default_value' => $config['carousel'][$key]['text_block_alignment'] ?? '',
        '#options' => [
          '' => $this->t('None'),
          'right' => $this->t('Right'),
          'center' => $this->t('Center'),
          'left' => $this->t('Left'),
        ],
      ];
      $form['carousel'][$key]['block_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Block type'),
        '#default_value' => $config['carousel'][$key]['block_type'] ?? 'homepage_hero',
        '#options' => [
          'homepage_hero' => $this->t('Homepage Hero'),
          'parent_page' => $this->t('Parent page'),
        ],
      ];
      $form['carousel'][$key]['use_dark_overlay'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use dark overlay'),
        '#description' => $this->t('If Dark overlay is enabled, then image will not be clickable.'),
        '#default_value' => $config['carousel'][$key]['use_dark_overlay'] ?? FALSE,
      ];
      $form['carousel'][$key]['remove_item'] = [
        '#type' => 'submit',
        '#name' => 'carousel_' . $key,
        '#value' => $this->t('Remove carousel item'),
        '#ajax' => [
          'callback' => [$this, 'ajaxRemoveCarouselItemCallback'],
          'wrapper' => 'carousel-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'removeCarouselItemSubmitted']],
      ];
    }

    $form['carousel']['add_item'] = [
      '#type'  => 'submit',
      '#name'  => 'carousel_add_item',
      '#value' => $this->t('Add new carousel item'),
      '#ajax'  => [
        'callback' => [$this, 'ajaxAddCarouselItemCallback'],
        'wrapper'  => 'carousel-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'addCarouselItemSubmitted']],
    ];

    // Read more text for mobile.
    $form['description_read_more_label'] = [
      '#title'         => $this->t('Carousel description read more label'),
      '#type'          => 'textfield',
      '#default_value' => $this->configuration['description_read_more_label'] ?? 'Read more',
      '#maxlength' => !empty($character_limit_config->get('carousel_component_title')) ? $character_limit_config->get('carousel_component_title') : 55,
    ];
    $form['description_read_more_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Carousel description read more color'),
      '#default_value' => ($config['description_read_more_color']) ?? NULL,
      '#attributes' => ['class' => ['show-clear']],
      '#description' => $this->t('If this field is left empty, it falls back to color E.') . mars_common_get_color_palette('color_e'),
    ];

    // Set slider time field.
    $form['enable_set_slider_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto scroll'),
      '#default_value' => $config['enable_set_slider_time'] ?? FALSE,
    ];
    $form['set_slider_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Set slider time'),
      '#step' => 0.0000001,
      '#default_value' => $config['enable_set_slider_time'] == TRUE && !empty($config['set_slider_time']) ? $config['set_slider_time'] : '3000',
      '#description' => $this->t('Format is integer only. eg.,3000'),
      '#states' => [
        'visible' => [
          [':input[name="settings[enable_set_slider_time]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // Element Id for Quick link.
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID (Quick link)'),
      '#description' => $this->t('Use element ID("ele_id")directly in Page Link for Quick link component. To use the attribute as deep link reference or to use it as internal link, add #ele_id at the end of the page URL to generate the href reference of that particular component on the page. Use the URL to link the component from any of other component on same page/different page.'),
      '#default_value' => $config['element_id'] ?? '',
    ];
    // Higher resolution image.
    $form['high_resolution_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Full-Width Image for higher resolutions'),
      '#default_value' => $config['high_resolution_image'] ?? FALSE,
      '#description' => $this->t('Please tick the checkbox if you want to see the image populated end to end of your screen for all different higher desktop screen resolutions.'),
    ];
    // Background color override.
    $form['use_background_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Background Color Override'),
      '#default_value' => $config['use_background_color'] ?? FALSE,
      '#prefix' => '<div id="override"',
      '#suffix' => '</div>',
    ];
    $form['background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Background Color Override'),
      '#default_value' => $config['use_background_color'] == TRUE && !empty($config['use_background_color']) ? $config['background_color'] : '',
      '#attributes' => ['class' => ['show-clear']],
      '#description' => $this->t('If this field is left empty, it falls back to color E.') . mars_common_get_color_palette('color_e'),
      '#states' => [
        'visible' => [
          [':input[name="settings[use_background_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    // CTA background and Text color.
    $form['cta_bg_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override CTA background and Text color'),
      '#default_value' => $config['cta_bg_text'] ?? FALSE,
    ];
    $form['cta_background'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('CTA Background Color Override'),
      '#default_value' => $config['cta_bg_text'] == TRUE && !empty($config['cta_bg_text']) ? $config['cta_background'] : '',
      '#attributes' => ['class' => ['show-clear']],
      '#description' => $this->t('If this field is left empty, it falls back to color B.') . mars_common_get_color_palette('color_b'),
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['cta_text_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('CTA Text Color'),
      '#default_value' => $config['cta_bg_text'] == TRUE && !empty($config['cta_bg_text']) ? $config['cta_text_color'] : '',
      '#attributes' => ['class' => ['show-clear']],
      '#description' => $this->t('If this field is left empty, it falls back to color E.') . mars_common_get_color_palette('color_e'),
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['hide_show_arrow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Arrows for Carousel'),
      '#default_value' => $config['hide_show_arrow'] ?? FALSE,
      '#description'   => $this->t("Use this checkbox to hide and show arrows."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $enable_set_slider_time = $form_state->getValue('enable_set_slider_time');
    $set_slider_time = $form_state->getValue('set_slider_time');
    $carousel_storage = $form_state->get('carousel_storage');
    $values = $form_state->getValues();
    if ($enable_set_slider_time == TRUE && empty($set_slider_time)) {
      $form_state->setErrorByName('set_slider_time', $this->t('Please set slider time.'));
    }
    if ($enable_set_slider_time == TRUE) {
      if ($set_slider_time && str_contains($set_slider_time, '.')) {
        $form_state->setErrorByName('set_slider_time', $this->t('Please provide integer format value.'));
      }
    }
    if (!empty($carousel_storage)) {
      foreach ($carousel_storage as $key => $carousel) {
        $title_label = $values['carousel'][$key]['label'];
        $title_label_override = $values['carousel'][$key]['next_line_label']['value'];
        // Validation for title label and title label override.
        if ($title_label && $title_label_override) {
          $form_state->setErrorByName('carousel][' . $key . '][label', $this->t('Title label or Override Title label field must be given.'));
          $form_state->setErrorByName('carousel][' . $key . '][next_line_label', '');
        }
      }
    }
  }

  /**
   * Add new carousel item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxAddCarouselItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['carousel'];
  }

  /**
   * Add remove carousel item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxRemoveCarouselItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['carousel'];
  }

  /**
   * Custom submit carousel configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function addCarouselItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $storage = $form_state->get('carousel_storage');
    array_push($storage, 1);
    $form_state->set('carousel_storage', $storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Custom submit carousel configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function removeCarouselItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $triggered = $form_state->getTriggeringElement();
    if (isset($triggered['#parents'][3]) && $triggered['#parents'][3] == 'remove_item') {
      $carousel_storage = $form_state->get('carousel_storage');
      $id = $triggered['#parents'][2];
      unset($carousel_storage[$id]);
      $form_state->set('carousel_storage', $carousel_storage);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    unset($values['carousel']['add_item']);
    $this->setConfiguration($values);
    if (isset($values['carousel']) && !empty($values['carousel'])) {
      foreach ($values['carousel'] as $key => $item) {

        unset(
          $this->configuration['carousel'][$key][self::KEY_OPTION_VIDEO],
          $this->configuration['carousel'][$key][self::KEY_OPTION_IMAGE]
        );

        $this->configuration['carousel'][$key][$item['item_type']] = $this->getEntityBrowserValue($form_state, [
          'carousel',
          $key,
          $item['item_type'],
        ]);

        foreach (MediaHelper::LIST_IMAGE_RESOLUTIONS as $resolution) {
          $name = 'image';
          if ($resolution != 'desktop') {
            $name = 'image_' . $resolution;
          }

          $this->configuration['carousel'][$key][$name] = $this->getEntityBrowserValue($form_state, [
            'carousel',
            $key,
            $name,
          ]);

        }
        $this->configuration['carousel'][$key]['video'] = $this->getEntityBrowserValue($form_state, [
          'carousel',
          $key,
          'video',
        ]);

        $this->configuration['carousel'][$key]['icon_image'] = $this->getEntityBrowserValue($form_state, [
          'carousel',
          $key,
          'icon_image',
        ]);

      }
    }
  }

  /**
   * Get text color.
   *
   * @return string
   *   Color hex value.
   */
  private function getTextColor($key) {
    $color_option = $this->configuration['carousel'][$key]['text_color'];
    if ($color_option == self::KEY_OPTION_OTHER_COLOR) {
      return $this->configuration['carousel'][$key]['text_color_other'];
    }
    $color_option = !empty($color_option) ? $color_option : self::KEY_OPTION_TEXT_COLOR_DEFAULT;

    return $this->themeConfiguratorParser
      ->getSettingValue($color_option);
  }

  /**
   * Get text color options.
   *
   * @return array
   *   Options.
   */
  private function getTextColorOptions() {
    return [
      'color_a' => '<span class="theme-color-label">Color A</span> ' . mars_common_get_color_palette('color_a'),
      'color_b' => '<span class="theme-color-label">Color B</span> ' . mars_common_get_color_palette('color_b'),
      'color_c' => '<span class="theme-color-label">Color C</span> ' . mars_common_get_color_palette('color_c'),
      'color_d' => '<span class="theme-color-label">Color D</span> ' . mars_common_get_color_palette('color_d'),
      'color_e' => '<span class="theme-color-label">Color E</span> ' . mars_common_get_color_palette('color_e'),
      'color_f' => '<span class="theme-color-label">Color F</span> ' . mars_common_get_color_palette('color_f'),
      self::KEY_OPTION_OTHER_COLOR => 'Other',
    ];
  }

  /**
   * Returns the bg image URL or NULL.
   *
   * @param int $key
   *   Key of Carousel array.
   * @param int $icon_image_asset
   *   Boolean value of icon asset.
   *
   * @return array|null
   *   The bg image url or null of there is none.
   */
  private function getBgAssets($key, $icon_image_asset = NULL): ?array {
    $config = $this->getConfiguration();
    $bg_image_media_ids = [];
    $assets = [];
    $title = 'fullwidth carousel background image';
    $alt = 'fullwidth carousel background image';
    $get_version = $this->configFactory->get('mars_lighthouse.settings')->get('api_version');
    $player_id_lighthouse = $this->configFactory->get('mars_lighthouse.settings')->get('player_id');
    $account_id_lighthouse = $this->configFactory->get('mars_lighthouse.settings')->get('account_id');

    if ($config['carousel'][$key]['item_type'] == self::KEY_OPTION_IMAGE) {
      foreach (MediaHelper::LIST_IMAGE_RESOLUTIONS as $resolution) {
        // Generate image field name.
        // NOTE: "background_image" for desktop without any suffixes
        // for compatibility with existing data.
        $name = $resolution == 'desktop' ? 'image' : 'image_' . $resolution;

        // Set value for each resolution.
        if (!empty($config['carousel'][$key][$name])) {
          $bg_image_media_ids[$resolution] = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['carousel'][$key][$name]);
        }
        else {
          // Set value from previous resolution.
          $bg_image_media_ids[$resolution] = end($bg_image_media_ids);
        }
      }
    }
    elseif ($config['carousel'][$key]['item_type'] == self::KEY_OPTION_VIDEO) {
      $bg_image_media_ids['video'] = NULL;

      if (!empty($config['carousel'][$key]['video'])) {
        $bg_image_media_ids['video'] = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['carousel'][$key]['video']);
      }
    }
    else {
      foreach (MediaHelper::LIST_IMAGE_RESOLUTIONS as $resolution) {
        $bg_image_media_ids[$resolution] = NULL;
      }
    }

    // Checking icon/image asset.
    if ($icon_image_asset == TRUE) {
      $bg_image_media_ids['icon_image'] = NULL;

      if (!empty($config['carousel'][$key]['icon_image'])) {
        $bg_image_media_ids['icon_image'] = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['carousel'][$key]['icon_image']);
      }
    }

    foreach ($bg_image_media_ids as $name => $bg_image_media_id) {
      $bg_image_url = NULL;
      if (!empty($bg_image_media_id)) {
        $media_params = $this->mediaHelper->getMediaParametersById($bg_image_media_id);
        // Resolve dwarning undefined bcove.
        if (!empty($media_params['bcove'])) {
          $bcove_videoid = $media_params['bcove'];
        }
        if ($get_version == 'v3' && !empty($bcove_videoid)) {
          $script_src = 'https://players.brightcove.net/' . $account_id_lighthouse . '/' . $player_id_lighthouse . '_default/index.min.js';
          $assets['bcove_video'] = [
            'video' => TRUE,
            'src' => $media_params['src'] ?? NULL,
            'video_id' => $bcove_videoid,
            'account_id' => $account_id_lighthouse,
            'player' => $player_id_lighthouse,
            'embed' => 'default',
            'script_src' => $script_src,
          ];
        }
        if (!isset($media_params['error'])) {
          $bg_image_url = $media_params['src'];
          $title = !empty($media_params['title']) ? $media_params['title'] : $title;
          $alt = !empty($media_params['alt']) ? $media_params['alt'] : $alt;
        }
      }

      $assets[$name] = [
        'src' => $bg_image_url,
        'alt' => $alt,
        'title' => $title,
      ];
    }

    return $assets;
  }

}
