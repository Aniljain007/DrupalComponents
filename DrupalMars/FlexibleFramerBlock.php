<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Drupal\mars_common\Traits\SelectBackgroundColorTrait;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Flexible Framer component block.
 *
 * @Block(
 *   id = "flexible_framer_block",
 *   admin_label = @Translation("MARS: Flexible Framer block"),
 *   category = @Translation("Page components"),
 * )
 */
class FlexibleFramerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;
  use SelectBackgroundColorTrait;
  use OverrideThemeTextColorTrait;

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Key override background color.
   */
  const KEY_OPTION_DEFAULT = 'default';

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
   * Mars Theme Configurator Parserr service.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * Image sizes.
   */
  const IMAGE_SIZE = [
    '1:1' => '1:1',
    '16:9' => '16:9',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    MediaHelper $media_helper,
    LanguageHelper $language_helper,
    ThemeConfiguratorParser $theme_configurator_parser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->mediaHelper = $media_helper;
    $this->languageHelper = $language_helper;
    $this->themeConfiguratorParser = $theme_configurator_parser;
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
      $container->get('mars_media.media_helper'),
      $container->get('mars_common.language_helper'),
      $container->get('mars_common.theme_configurator_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');
    $header_option = [
      'h2' => 'H2',
      'h3' => 'H3',
      'h4' => 'H4',
      'h5' => 'H5',
      'h6' => 'H6',
    ];
    $heading_input = $config['heading'] ?? 'H1';
    $keys = array_keys($header_option);
    $sub_heading = (array_search($heading_input, $keys)) + 1;
    if (!empty($heading_input)) {
      $output = array_slice($header_option, $sub_heading, count($header_option));
    }
    else {
      $output = array_slice($header_option, 1, 5);
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_header')) ? $character_limit_config->get('flexible_frame_header') : 55,
      '#default_value' => $config['title'] ?? '',
    ];

    $form['heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose Heading Tag'),
      '#options' => array_slice($header_option, 0, 4),
      '#default_value' => $config['heading'] ?? '',
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'callback' => [$this, 'reloadSubHeading'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'reload-sub-wrapper',
      ],
    ];

    $form['sub_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sub Header'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_sub_header')) ? $character_limit_config->get('flexible_frame_sub_header') : 55,
      '#default_value' => $config['sub_title'] ?? '',
    ];

    $form['sub_heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Sub Heading'),
      '#options' => $output,
      '#default_value' => $config['sub_heading'] ?? '',
      '#empty_option' => $this->t('- Select -'),
      '#description'   => $this->t('NOTE : For sub heading, always choose least heading tag than Heading tag for better accessibility'),
      '#prefix' => '<div id="reload-sub-wrapper">',
      '#suffix' => '</div>',

    ];

    $form['with_cta'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without CTA'),
      '#default_value' => $config['with_cta'] ?? TRUE,
      '#description' => $this->t('To make image as clickable, check With/Without CTA option. The image URL will be same as CTA link URL and if CTA is not required, remove CTA Link title and retain only the URL for image to be clickable.'),
    ];

    $form['datalayer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Brand to Datalayer'),
      '#description' => $this->t('Note: Select only in Click to Buy pages'),
      '#default_value' => $config['datalayer'] ?? FALSE,
    ];

    $form['with_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without description'),
      '#default_value' => $config['with_description'] ?? TRUE,
    ];

    $form['with_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without image'),
      '#default_value' => $config['with_image'] ?? TRUE,
    ];

    $form['optimize_size'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Optimize size'),
      '#default_value' => $config['optimize_size'] ?? FALSE,
    ];

    $form['single_flexible_item'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable single flexible framer item'),
      '#attributes' => ['class' => ['single-item-text-aligment']],
      '#default_value' => $config['single_flexible_item'] ?? FALSE,
    ];
    $form['flexible_item_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Set the item grid count'),
      '#options' => [
        1 => $this->t('One'),
        2 => $this->t('Two'),
        3 => $this->t('Three'),
        4 => $this->t('Four'),
      ],
      '#default_value' => $config['flexible_item_count'] ?? 1,
      '#states' => [
        'visible' => [
          ':input[name="settings[single_flexible_item]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['use_text_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Items Text Color Override'),
      '#default_value' => $config['use_text_color'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
        ],
      ],
    ];
    $form['text_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Text Color for items'),
      '#attributes' => ['class' => ['show-clear']],
      '#default_value' => $config['text_color'] ?? NULL,
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
          'and',
          [':input[name="settings[use_text_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['use_border_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Border Color Override'),
      '#default_value' => $config['use_border_color'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
        ],
      ],
    ];
    $form['border_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Select border color'),
      '#attributes' => ['class' => ['show-clear']],
      '#default_value' => $config['border_color'] ?? NULL,
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
          'and',
          [':input[name="settings[use_border_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['use_background_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Items Background Color Override'),
      '#default_value' => $config['use_background_color'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
        ],
      ],
    ];
    $form['background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Background Color for items'),
      '#attributes' => ['class' => ['show-clear']],
      '#default_value' => $config['background_color'] ?? NULL,
      '#description'   => $this->t('If this field is left empty, it falls back to background color.'),
      '#states' => [
        'visible' => [
          [':input[name="settings[block_type]"]' => ['value' => self::KEY_OPTION_DEFAULT]],
          'and',
          [':input[name="settings[use_background_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['use_carousel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Carousel'),
      '#default_value' => $config['use_carousel'] ?? FALSE,
    ];
    $form['enable_continuous_scroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the feature to scroll through the items in an endless loop'),
      '#default_value' => $config['enable_continuous_scroll'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[use_carousel]"]' => ['checked' => TRUE]],
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
    $form['items'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Setup items'),
      '#prefix' => '<div id="items-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => 'js-form-wrapper',
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[single_flexible_item]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Form fields if Enable single flexible framer item.
    $form['items_single'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Flexible framer item'),
      '#attributes' => [
        'class' => 'js-form-wrapper',
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[single_flexible_item]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['items_single']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item title'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_title')) ? $character_limit_config->get('flexible_frame_item_title') : 60,
      '#default_value' => $config['items_single']['title'] ?? '',
    ];
    // Title override.
    $form['items_single']['next_line_title'] = [
      '#type' => 'text_format',
      '#format' => 'rich_text',
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_title_override')) ? $character_limit_config->get('flexible_frame_item_title_override') : 200,
      '#title' => $this->t('Override Item title'),
      '#description' => $this->t('The Site admin will be able to add up to 200 characters including the HTML tags and 55 characters excluding the HTML tags in CK editor for Title override functionality. Please preview the changes made in the layout page before saving the changes to align to the format.'),
      '#default_value' => $config['items_single']['next_line_title']['value'] ?? '',
    ];
    $form['items_single']['sub_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Sub title'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_sub_title')) ? $character_limit_config->get('flexible_frame_item_sub_title') : 60,
      '#default_value' => $config['items_single']['sub_title'] ?? '',
    ];
    $form['items_single']['enable_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Filter ID for item'),
      '#attributes' => ['class' => ['enable_filter-check']],
      '#default_value' => $config['items_single']['enable_filter'] ?? FALSE,
    ];
    $form['items_single']['filter_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter Id'),
      '#default_value' => $config['items_single']['filter_id'] ?? '',
      '#attributes' => ['class' => ['filter_enable_single']],
      '#states' => [
        'visible' => [
          [':input[name="settings[items_single][enable_filter]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['items_single']['cta']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link Title'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_cta_link_title')) ? $character_limit_config->get('flexible_frame_cta_link_title') : 15,
      '#default_value' => $config['items_single']['cta']['title'] ?? $this->t('Explore'),
    ];
    $form['items_single']['cta']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link URL'),
      '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_cta_link_url')) ? $character_limit_config->get('flexible_frame_cta_link_url') : 2048,
      '#default_value' => $config['items_single']['cta']['url'] ?? '',
    ];
    $form['items_single']['cta']['new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open CTA link in a new tab'),
      '#default_value' => $config['items_single']['cta']['new_window'] ?? FALSE,
    ];
    $form['items_single']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Item description'),
      '#default_value' => $config['items_single']['description'] ?? '',
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_description')) ? $character_limit_config->get('flexible_frame_item_description') : 255,
    ];
    // Override description.
    $form['items_single']['override_item_description'] = [
      '#type' => 'text_format',
      '#format' => 'rich_text',
      '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_description')) ? $character_limit_config->get('flexible_frame_item_description') : 300,
      '#title' => $this->t('Override Item description'),
      '#description' => $this->t('Site admin will be able to add up to 300 characters including the HTML tags and 55 characters excluding the HTML tags in CK editor for Description override functionality. Please preview the changes made in the layout page before saving the changes to align to the format.'),
      '#default_value' => $config['items_single']['override_item_description']['value'] ?? '',
    ];

    $item_image = $config['items_single']['item_image'] ?? NULL;
    $form['items_single']['item_image'] = $this->getEntityBrowserForm(ImageVideoBlockBase::LIGHTHOUSE_ENTITY_BROWSER_IMAGE_ID,
      $item_image, $form_state, 1, 'thumbnail', FALSE);
    // Convert the wrapping container to a details element.
    $form['items_single']['item_image']['#type'] = 'details';
    $form['items_single']['item_image']['#title'] = $this->t('Item Image');
    $form['items_single']['item_image']['#open'] = TRUE;

    $form['items_single']['image_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image size'),
      '#options' => static::IMAGE_SIZE,
      '#default_value' => $config['items_single']['image_size'] ?? static::IMAGE_SIZE['1:1'],
    ];

    $submitted_input = $form_state->getUserInput()['settings'] ?? [];
    $saved_items = !empty($config['items']) ? $config['items'] : [];
    $submitted_items = $submitted_input['items'] ?? [];
    $items_storage = $form_state->get('items_storage');
    if (empty($items_storage)) {
      if (!empty($submitted_items)) {
        $items_storage = $submitted_items;
      }
      else {
        $items_storage = $saved_items;
      }
    }

    $form_state->set('items_storage', $items_storage);

    foreach ($items_storage as $key => $value) {
      $form['items'][$key] = [
        '#type' => 'details',
        '#title' => $this->t('Flexible framer item'),
        '#open' => TRUE,
      ];
      $form['items'][$key]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Item title'),
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_title')) ? $character_limit_config->get('flexible_frame_item_title') : 60,
        '#default_value' => $config['items'][$key]['title'] ?? '',
      ];
      $form['items'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#default_value' => $config['items'][$key]['weight'] ?? 0,
        '#delta' => !empty($character_limit_config->get('flexible_frame_item_weight')) ? $character_limit_config->get('flexible_frame_item_weight') : 15,
        '#description' => $this->t('Items with weight -5/-4/-3 etc will appear before items with weight -1/0/1/2/3 etc in an ascending order of their defined weight.'),
      ];
      // Title override.
      $form['items'][$key]['next_line_title'] = [
        '#type' => 'text_format',
        '#format' => 'rich_text',
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_title_override')) ? $character_limit_config->get('flexible_frame_item_title_override') : 200,
        '#title' => $this->t('Override Item title'),
        '#description' => $this->t('The Site admin will be able to add up to 200 characters including the HTML tags and 55 characters excluding the HTML tags in CK editor for Title override functionality. Please preview the changes made in the layout page before saving the changes to align to the format.'),
        '#default_value' => $config['items'][$key]['next_line_title']['value'] ?? '',
      ];
      $form['items'][$key]['sub_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Item Sub title'),
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_sub_title')) ? $character_limit_config->get('flexible_frame_item_sub_title') : 60,
        '#default_value' => $config['items'][$key]['sub_title'] ?? '',
      ];
      $form['items'][$key]['enable_filter'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Filter ID for item'),
        '#default_value' => $config['items'][$key]['enable_filter'] ?? FALSE,
        '#attributes' => ['class' => ['enable_filter-check']],
      ];
      $form['items'][$key]['filter_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Filter Id'),
        '#default_value' => $config['items'][$key]['filter_id'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $key . '][enable_filter]"]' => ['checked' => TRUE],
          ],
        ],
        '#prefix' => '<div class="filter_id_enable">',
        "#suffix" => '</div>',
      ];
      $form['items'][$key]['cta']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link Title'),
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_cta_link_title')) ? $character_limit_config->get('flexible_frame_cta_link_title') : 15,
        '#default_value' => $config['items'][$key]['cta']['title'] ?? $this->t('Explore'),
      ];
      $form['items'][$key]['cta']['url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link URL'),
        '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_cta_link_url')) ? $character_limit_config->get('flexible_frame_cta_link_url') : 2048,
        '#default_value' => $config['items'][$key]['cta']['url'] ?? '',
      ];
      $form['items'][$key]['cta']['new_window'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Open CTA link in a new tab'),
        '#default_value' => $config['items'][$key]['cta']['new_window'] ?? FALSE,
      ];
      $form['items'][$key]['description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Item description'),
        '#default_value' => $config['items'][$key]['description'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_description')) ? $character_limit_config->get('flexible_frame_item_description') : 255,
      ];
      // Override description.
      $form['items'][$key]['override_item_description'] = [
        '#type' => 'text_format',
        '#format' => 'rich_text',
        '#maxlength' => !empty($character_limit_config->get('flexible_frame_item_description')) ? $character_limit_config->get('flexible_frame_item_description') : 300,
        '#title' => $this->t('Override Item description'),
        '#description' => $this->t('Site admin will be able to add up to 300 characters including the HTML tags and 55 characters excluding the HTML tags in CK editor for Description override functionality. Please preview the changes made in the layout page before saving the changes to align to the format.'),
        '#default_value' => $config['items'][$key]['override_item_description']['value'] ?? '',
      ];

      $item_image = $config['items'][$key]['item_image'] ?? NULL;
      $form['items'][$key]['item_image'] = $this->getEntityBrowserForm(ImageVideoBlockBase::LIGHTHOUSE_ENTITY_BROWSER_IMAGE_ID,
        $item_image, $form_state, 1, 'thumbnail', FALSE);
      // Convert the wrapping container to a details element.
      $form['items'][$key]['item_image']['#type'] = 'details';
      $form['items'][$key]['item_image']['#title'] = $this->t('Item Image');
      $form['items'][$key]['item_image']['#open'] = TRUE;

      $form['items'][$key]['image_size'] = [
        '#type' => 'radios',
        '#title' => $this->t('Image size'),
        '#options' => static::IMAGE_SIZE,
        '#default_value' => $config['items'][$key]['image_size'] ?? static::IMAGE_SIZE['1:1'],
      ];

      $form['items'][$key]['remove_item'] = [
        '#type'  => 'submit',
        '#name' => 'item_' . $key,
        '#value' => $this->t('Remove item'),
        '#ajax'  => [
          'callback' => [$this, 'ajaxRemoveItemCallback'],
          'wrapper' => 'items-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'removeItemSubmitted']],
        '#states' => [
          'visible' => [
            ':input[name="settings[single_flexible_item]"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    if ($config['use_carousel'] || count($items_storage) < 4) {
      $form['items']['add_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add item'),
        '#ajax' => [
          'callback' => [$this, 'ajaxAddItemCallback'],
          'wrapper' => 'items-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'addItemSubmitted']],
        '#states' => [
          'visible' => [
            ':input[name="settings[single_flexible_item]"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    $form['items']['descriptions'] = [
      '#type' => 'label',
      '#title' => $config['use_carousel'] ? $this->t('Add unlimited items.') : $this->t('Up to 4 additional items.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[single_flexible_item]"]' => ['checked' => FALSE],
        ],
      ],
      '#attributes' => [
        'class' => 'js-form-wrapper',
      ],
    ];

    // Add select background color.
    $other = 'yes';
    $this->buildSelectBackground($form, $other);
    $form['other_background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#attributes' => ['class' => ['show-clear']],
      '#title' => $this->t('Background Color Override'),
      '#default_value' => !empty($config['other_background_color']) ? $config['other_background_color'] : '',
      '#states' => [
        'visible' => [
          [':input[name="settings[select_background_color]"]' => ['value' => 'other']],
        ],
      ],
    ];
    $this->buildOverrideColorElement($form, $config);
    $color_a = $this->themeConfiguratorParser->getSettingValue('color_a');
    $color_b = $this->themeConfiguratorParser->getSettingValue('color_b');
    $color_c = $this->themeConfiguratorParser->getSettingValue('color_c');
    $color_d = $this->themeConfiguratorParser->getSettingValue('color_d');
    $color_e = $this->themeConfiguratorParser->getSettingValue('color_e');
    $color_f = $this->themeConfiguratorParser->getSettingValue('color_f');

    $form['override_text_color']['select_text_color_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Override theme text color'),
      '#options' => [
        '' => $this->t('Default'),
        'text-color-A' => 'Color A - ' . $color_a,
        'text-color-B' => 'Color B - ' . $color_b,
        'text-color-C' => 'Color C - ' . $color_c,
        'text-color-D' => 'Color D - ' . $color_d,
        'text-color-E' => 'Color E - ' . $color_e,
        'text-color-F' => 'Color F - ' . $color_f,
      ],
      '#default_value' => $config['override_text_color']['select_text_color_class'] ?? '',
      '#description' => $this->t("Override all text color configuration with selected color for the selected component"),
    ];

    $form['text_alignment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text alignment'),
      '#default_value' => $config['text_alignment'] ?? 'center',
      '#attributes' => ['class' => ['enable-single-text-aligment']],
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['hide_graphic_divider'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Graphic Divider'),
      '#default_value' => $config['hide_graphic_divider'] ?? FALSE,
    ];

    $form['use_padding_top'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable padding at the top'),
      '#default_value' => $config['use_padding_top'] ?? FALSE,
    ];

    $form['with_brand_borders'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without brand border'),
      '#default_value' => $config['with_brand_borders'] ?? FALSE,
    ];

    $form['round_corner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable round corner'),
      '#default_value' => $config['round_corner'] ?? FALSE,
    ];
    $form['border_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter border width'),
      '#maxlength' => 10,
      '#description' => $this->t('Pleas Enter border width as integer value Ex:2px'),
      '#default_value' => $config['border_width'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[round_corner]"]' => [['checked' => TRUE]]],
        ],
      ],
    ];
    $form['overlaps_previous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without overlaps previous'),
      '#default_value' => $config['overlaps_previous'] ?? FALSE,
    ];
    // CTA background and Text color.
    $form['cta_bg_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override CTA background and Text color'),
      '#default_value' => $config['cta_bg_text'] ?? FALSE,
    ];
    $form['cta_background'] = [
      '#type' => 'jquery_colorpicker',
      '#attributes' => ['class' => ['show-clear']],
      '#title' => $this->t('CTA Background Color Override'),
      '#default_value' => $config['cta_bg_text'] == TRUE && !empty($config['cta_bg_text']) ? $config['cta_background'] : '',
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['cta_text_color'] = [
      '#type' => 'jquery_colorpicker',
      '#attributes' => ['class' => ['show-clear']],
      '#title' => $this->t('CTA Text Color'),
      '#default_value' => $config['cta_bg_text'] == TRUE && !empty($config['cta_bg_text']) ? $config['cta_text_color'] : '',
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['#attached']['library'][] = 'mars_common/flexible_framer';
    return $form;
  }

  /**
   * Reload Sub Heading.
   */
  public function reloadSubHeading(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $heading_input = $form_state->getUserInput()['settings']['heading'];
    $tags = [
      'h2' => 'H2',
      'h3' => 'H3',
      'h4' => 'H4',
      'h5' => 'H5',
      'h6' => 'H6',
    ];
    $keys = array_keys($tags);
    $heading_index = array_search($heading_input, $keys);
    $sub_heading = (array_search($heading_input, $keys)) + 1;
    $sub_val = $config['sub_heading'];
    if ((!empty($heading_input) || empty($sub_val) && array_search($config['heading'], $keys) < $heading_index) && (array_search($sub_val, $keys) <= $heading_index)) {
      $output = array_slice($tags, $sub_heading, count($tags));
    }
    else {
      $output = [
        $sub_val => $tags[$sub_val],
      ];
      $sub_heading_new = array_slice($tags, $sub_heading, count($tags));
      foreach ($output as $key => $val) {
        if (array_key_exists($key, $sub_heading_new)) {
          unset($sub_heading_new[$key]);
        }
      }
      array_push($output, $sub_heading_new);

    }
    unset($form['sub_heading']);
    $form['sub_heading']['#type'] = 'select';
    $form['sub_heading']['#name'] = 'settings[sub_heading]';
    $form['sub_heading']['#description'] = $this->t('NOTE : For sub heading, always choose least heading tag than Heading tag for better accessibility');
    $form['sub_heading']['#title'] = $this->t('Sub Heading');
    $form['sub_heading']['#prefix'] = '<div id="reload-sub-wrapper">';
    $form['sub_heading']['#suffix'] = '</div>';
    $form['sub_heading']['#default_value'] = $sub_val ?? '';
    $form['sub_heading']['#options'] = $output;

    return $form['sub_heading'];
  }

  /**
   * Add new item link callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   Item container of configuration settings.
   */
  public function ajaxAddItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['items'];
  }

  /**
   * Add remove item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   Item container of configuration settings.
   */
  public function ajaxRemoveItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['items'];
  }

  /**
   * Custom submit item configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function addItemSubmitted(array $form, FormStateInterface $form_state) {
    $storage = $form_state->get('items_storage');
    array_push($storage, 1);
    $form_state->set('items_storage', $storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Custom submit configuration settings form to remove item.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function removeItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $triggered = $form_state->getTriggeringElement();
    if (isset($triggered['#parents'][3]) && $triggered['#parents'][3] == 'remove_item') {
      $items_storage = $form_state->get('items_storage');
      $id = $triggered['#parents'][2];
      unset($items_storage[$id]);
      $form_state->set('items_storage', $items_storage);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    unset($values['items']['add_item']);
    $border_width = $this->configFactory->getEditable('emulsifymars.settings');
    $border_width->set('border_width', $form_state->getValue('border_width'));
    $border_width->save(TRUE);
    $single_flexible_item = $form_state->getValue('single_flexible_item');
    if ($single_flexible_item == TRUE) {
      $values['text_alignment'] = 'center';
    }

    $this->setConfiguration($values);

    if (isset($values['items']) && !empty($values['items'])) {
      foreach ($values['items'] as $key => $item) {
        $this->configuration['items'][$key]['item_image'] = $this->getEntityBrowserValue($form_state, [
          'items',
          $key,
          'item_image',
        ]);
      }
    }

    if (isset($values['items_single']) && !empty($values['items_single'])) {
      $this->configuration['items_single']['item_image'] = $this->getEntityBrowserValue($form_state, [
        'items_single',
        'item_image',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $block_build_styles = mars_common_block_build_style($config);
    $with_cta_flag = (bool) $config['with_cta'];
    $with_image_flag = (bool) $config['with_image'];
    $with_data_layer = (bool) $config['datalayer'];
    $with_desc_flag = (bool) $config['with_description'];
    $optimize_size = (bool) $config['optimize_size'];

    $ff_items = [];
    $single_flexible_item = $config['single_flexible_item'] ?? FALSE;
    if ($single_flexible_item) {
      $new_window = $config['items_single']['cta']['new_window'] ?? NULL;
      $new_window = ($new_window == TRUE) ? '_blank' : '_self';
      $title_override = !empty($config['items_single']['next_line_title']['value']) ? $config['items_single']['next_line_title']['value'] : '';
      $description_override = !empty($config['items_single']['override_item_description']['value']) ? $config['items_single']['override_item_description']['value'] : '';
      $ff_item = [
        'card__heading' => $this->languageHelper->translate($config['items_single']['title']) ?? NULL,
        'card__heading__override' => $this->languageHelper->translate($title_override),
        'card__subheading' => $this->languageHelper->translate($config['items_single']['sub_title']) ?? NULL,
        'card__link__url' => ($with_cta_flag) ? $config['items_single']['cta']['url'] : NULL,
        'card__link__text' => ($with_cta_flag) ? $this->languageHelper->translate($config['items_single']['cta']['title']) : NULL,
        'card__link__new_window' => ($with_cta_flag) ? $new_window : '_self',
        'card__datalayer' => ($with_data_layer) ? 'true' : 'false',
        'card__body' => ($with_desc_flag) ? $this->languageHelper->translate($config['items_single']['description']) : NULL,
        'card__body__override' => ($with_desc_flag) ? $this->languageHelper->translate($description_override) : NULL,
        'card__optimize__size' => ($optimize_size) ? 'true' : 'false',
        'filter_id' => $config['items_single']['filter_id'] ?? NULL,
      ];

      if (!empty($config['items_single']['item_image']) && $with_image_flag) {

        $image_url = NULL;
        $media_id = $this->mediaHelper
          ->getIdFromEntityBrowserSelectValue($config['items_single']['item_image']);

        if ($media_id) {
          $media_params = $this->mediaHelper->getMediaParametersById($media_id);
          if (!isset($media_params['error'])) {
            $image_url = $media_params['src'];
          }
        }
        $ff_item['card__image__src'] = $image_url;
        $ff_item['card__image__alt'] = $media_params['alt'] ?? NULL;
        $ff_item['card__image__title'] = $media_params['title'] ?? NULL;
        $ff_item['filter_id'] = $config['items_single']['filter_id'] ?? NULL;
        $ff_item['card__image__size'] = $config['items_single']['image_size'] ?? static::IMAGE_SIZE['1:1'];
      }
      $ff_item['text_color_cls_name'] = $config['override_text_color']['select_text_color_class'] ?? '';
      $ff_items[] = $ff_item;
    }
    else {
      foreach ($config['items'] as $key => $item) {
        $new_window = $config['items'][$key]['cta']['new_window'] ?? NULL;
        $new_window = ($new_window == TRUE) ? '_blank' : '_self';
        $title_override = !empty($config['items'][$key]['next_line_title']['value']) ? $config['items'][$key]['next_line_title']['value'] : '';
        $description_override = !empty($config['items'][$key]['override_item_description']['value']) ? $config['items'][$key]['override_item_description']['value'] : '';
        $ff_item = [
          'card__heading' => $this->languageHelper->translate($config['items'][$key]['title']) ?? NULL,
          'card__heading__override' => $this->languageHelper->translate($title_override),
          'card__subheading' => $this->languageHelper->translate($config['items'][$key]['sub_title']) ?? NULL,
          'card__link__url' => ($with_cta_flag) ? $config['items'][$key]['cta']['url'] : NULL,
          'card__link__text' => ($with_cta_flag) ? $this->languageHelper->translate($config['items'][$key]['cta']['title']) : NULL,
          'card__link__new_window' => ($with_cta_flag) ? $new_window : '_self',
          'card__datalayer' => ($with_data_layer) ? 'true' : 'false',
          'card__body' => ($with_desc_flag) ? $this->languageHelper->translate($config['items'][$key]['description']) : NULL,
          'card__body__override' => ($with_desc_flag) ? $this->languageHelper->translate($description_override) : NULL,
          'card__optimize__size' => ($optimize_size) ? 'true' : 'false',
          'filter_id' => $config['items'][$key]['filter_id'] ?? NULL,
          'weight' => array_key_exists('weight', $config['items'][$key]) ? $config['items'][$key]['weight'] : "",
        ];
        if (!empty($config['items'][$key]['item_image']) && $with_image_flag) {
          $image_url = NULL;
          $media_id = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['items'][$key]['item_image']);
          if ($media_id) {
            $media_params = $this->mediaHelper->getMediaParametersById($media_id);
            if (!isset($media_params['error'])) {
              $image_url = $media_params['src'];
            }
          }
          $ff_item['card__image__src'] = $image_url;
          $ff_item['card__image__alt'] = $media_params['alt'] ?? NULL;
          $ff_item['card__image__title'] = $media_params['title'] ?? NULL;
          $ff_item['filter_id'] = $config['items'][$key]['filter_id'] ?? NULL;
          $ff_item['card__image__size'] = $config['items'][$key]['image_size'] ?? static::IMAGE_SIZE['1:1'];
        }
        $ff_item['text_color_cls_name'] = $config['override_text_color']['select_text_color_class'] ?? '';
        $ff_items[] = $ff_item;
      }
      if ($ff_items) {
        usort($ff_items, fn($a, $b) => $a['weight'] <=> $b['weight']);
      }
    }

    $file_divider_content = $this->themeConfiguratorParser->getGraphicDivider();
    $file_border_content = $this->themeConfiguratorParser->getBrandBorder2();

    $background_color = '';
    if (!empty($this->configuration['select_background_color']) && $this->configuration['select_background_color'] != 'default') {
      $background_color = 'bg_' . $this->configuration['select_background_color'];
    }
    if (!empty($this->configuration['select_background_color']) && $this->configuration['select_background_color'] === 'other') {
      $background_color = 'bg_' . $this->configuration['select_background_color'];
    }
    $build['#flexible_item_count'] = $config['flexible_item_count'] ?? 1;
    $build['#text_color_override'] = FALSE;
    $build['#use_background_color'] = $config['use_background_color'] ?? FALSE;
    $build['#use_text_color'] = $config['use_text_color'] ?? FALSE;
    $build['#use_border_color'] = $config['use_border_color'] ?? FALSE;
    // Resolve warning Undefined array key use_carousel.
    if (isset($config['use_carousel'])) {
      $build['#use_carousel'] = $config['use_carousel'] ? 'true' : 'false';
    }
    if (isset($config['enable_continuous_scroll'])) {
      $build['#enable_continuous_scroll'] = $config['enable_continuous_scroll'] ?? FALSE;
    }
    if (!empty($config['items_single']['enable_filter'])) {
      $build['#enable_filter'] = $config['items_single']['enable_filter'] ? 'true' : 'false';
    }
    if (!empty($config['items'][$key]['enable_filter'])) {
      $build['#enable_filter'] = $config['items'][$key]['enable_filter'] ? 'true' : 'false';
    }
    $build['#text_alignment'] = $config['text_alignment'] ?? FALSE;
    $build['#hide_graphic_divider'] = $config['hide_graphic_divider'] ?? FALSE;
    $build['#use_padding_top'] = $config['use_padding_top'] ?? '';
    $build['#select_background_color'] = $background_color;
    $build['#items'] = $ff_items;
    $build['#grid_type'] = 'card';
    $build['#item_type'] = 'card';
    $build['#grid_label'] = $this->languageHelper->translate($config['title'] ?? NULL);
    $build['#grid_data_attribute'] = $config['filter_id'] ?? NULL;
    $build['#grid_sub_label'] = $this->languageHelper->translate($config['sub_title'] ?? NULL);
    $build['#divider'] = $file_divider_content ?? NULL;
    $build['#brand_borders'] = !empty($config['with_brand_borders']) ? $file_border_content : NULL;
    $build['#overlaps_previous'] = $config['overlaps_previous'] ?? NULL;
    $build['#round_corner'] = $config['round_corner'] ?? NULL;
    $build['#element_id'] = $config['element_id'] ?? '';
    $build['#brand_shape'] = $this->themeConfiguratorParser->getBrandShapeWithoutFill();
    $build['#use_cta_background_text_color'] = $config['cta_bg_text'] ?? FALSE;
    $build['#heading'] = !empty($config['heading']) ? preg_replace('/[^0-9]/', '', $config['heading']) : '2';
    $build['#sub_heading'] = !empty($config['sub_heading']) ? preg_replace('/[^0-9]/', '', $config['sub_heading']) : '3';
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'flexible_framer_block';
    $build['#attached']['library'][] = 'mars_common/clicktobuy_datalayer';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $single_flexible_item = $form_state->getValue('single_flexible_item');
    $optimize_size = $form_state->getValue('optimize_size');
    if ($single_flexible_item == TRUE) {
      $url = $form_state->getValue('items_single')['cta']['url'];
      $item_title = $form_state->getValue('items_single')['title'];
      $item_title_override = $form_state->getValue('items_single')['next_line_title']['value'];
      // Validation for Item title and Item title override.
      if (!$optimize_size) {
        if ($item_title && $item_title_override) {
          $form_state->setErrorByName('items_single][title', $this->t('Item title or Override Item title field must be given.'));
          $form_state->setErrorByName('items_single][next_line_title', '');
        }
        if (!$item_title && !$item_title_override) {
          $form_state->setErrorByName('items_single][title', $this->t('Item title or Override Item title field must be given.'));
          $form_state->setErrorByName('items_single][next_line_title', '');
        }
      }
      if (!empty($url) && !((bool) preg_match("/^(http:\/\/|https:\/\/|\/)(?:[\p{L}\p{N}\x7f-\xff#!:\.\?\+=&@$'~*,;_\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url))) {
        $form_state->setErrorByName('items_single][cta][url', $this->t('The URL is not valid.'));
      }
    }
    else {
      $item_storage = $form_state->get('items_storage');
      if (!empty($form_state->get('items_storage')) && is_array($form_state->get('items_storage'))) {
        if (count($form_state->get('items_storage')) < 2 && !$optimize_size) {
          $form_state->setErrorByName('items', $this->t('2 minimum items are required'));
        }

        $keys = array_keys($form_state->get('items_storage'));
        foreach ($keys as $key) {
          $url = $form_state->getValue('items')[$key]['cta']['url'];
          if (!empty($url) && !((bool) preg_match("/^(http:\/\/|https:\/\/|\/)(?:[\p{L}\p{N}\x7f-\xff#!:\.\?\+=&@$'~*,;_\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url))) {
            $form_state->setErrorByName('items][' . $key . '][cta][url', $this->t('The URL is not valid.'));
          }
        }
      }
      else {
        $form_state->setErrorByName('items', $this->t('2 minimum items are required'));
      }
      if (!empty($item_storage)) {
        $values = $form_state->getValues();
        foreach ($item_storage as $key => $item) {
          $item_title = $values['items'][$key]['title'];
          $item_title_override = $values['items'][$key]['next_line_title']['value'];
          // Validation for Item title and Item title override.
          if (!$optimize_size) {
            if ($item_title && $item_title_override) {
              $form_state->setErrorByName('items][' . $key . '][title', $this->t('Item title or Override Item title field must be given.'));
              $form_state->setErrorByName('items][' . $key . '][next_line_title', '');
            }
            if (!$item_title && !$item_title_override) {
              $form_state->setErrorByName('items][' . $key . '][title', $this->t('Item title or Override Item title field must be given.'));
              $form_state->setErrorByName('items][' . $key . '][next_line_title', '');
            }
          }
        }
      }
    }
  }

  /**
   * Function to set the default values.
   */
  public function defaultConfiguration(): array {
    $config = $this->getConfiguration();
    return [
      'use_carousel' => $config['use_carousel'] ?? FALSE,
      'element_id' => $config['element_id'] ?? '',
      'cta_bg_text' => $config['cta_bg_text'] ?? FALSE,
    ];
  }

}
