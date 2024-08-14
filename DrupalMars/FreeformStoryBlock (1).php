<?php

namespace Drupal\mars_common\Plugin\Block;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Drupal\mars_media\SVG\SVG;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MARS: Freeform Story Block' Block.
 *
 * @Block(
 *   id = "freeform_story_block",
 *   admin_label = @Translation("MARS: Freeform Story Block"),
 *   category = @Translation("Mars Common"),
 * )
 */
class FreeformStoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;
  use OverrideThemeTextColorTrait;

  /**
   * Lighthouse entity browser id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_ID = 'lighthouse_browser';

  /**
   * Lighthouse entity browser video id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_VIDEO_ID = 'lighthouse_video_browser';

  /**
   * Aligned by left side.
   */
  const LEFT_ALIGNED = 'left';

  /**
   * Aligned by right side.
   */
  const RIGHT_ALIGNED = 'right';

  /**
   * Aligned by center.
   */
  const CENTER_ALIGNED = 'center';

  /**
   * Resolution value.
   */
  const VAL_10_90 = '10:90';

  /**
   * Resolution value.
   */
  const VAL_20_80 = '20:80';

  /**
   * Resolution value.
   */
  const VAL_30_70 = '30:70';

  /**
   * Resolution value.
   */
  const VAL_40_60 = '40:60';

  /**
   * Resolution value.
   */
  const VAL_50_50 = '50:50';

  /**
   * Resolution value.
   */
  const VAL_60_40 = '60:40';

  /**
   * Resolution value.
   */
  const VAL_70_30 = '70:30';

  /**
   * Resolution value.
   */
  const VAL_80_20 = '80:20';

  /**
   * Resolution value.
   */
  const VAL_90_10 = '90:10';

  /**
   * Key option 3d asset.
   */
  const KEY_OPTION_3D_ASSET = 'enable_3D_asset';

  /**
   * Key option image.
   */
  const KEY_OPTION_IMAGE = 'image';

  /**
   * Key option video.
   */
  const KEY_OPTION_VIDEO = 'video';

  /**
   * Key option audio.
   */
  const KEY_OPTION_AUDIO = 'audio';

  /**
   * Key option youtube video.
   */
  const KEY_OPTION_YOUTUBE_VIDEO = 'youtube';

  /**
   * Key option null.
   */
  const KEY_OPTION_NULL = NULL;

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
   * File url generator service.
   *
   * @var Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
    MediaHelper $media_helper,
    FileUrlGenerator $file_generator,
    FileSystemInterface $file_system,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeConfiguratorParser = $theme_configurator_parser;
    $this->languageHelper = $language_helper;
    $this->mediaHelper = $media_helper;
    $this->fileUrlGenerator = $file_generator;
    $this->fileSystem = $file_system;
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
      $container->get('mars_media.media_helper'),
      $container->get('file_url_generator'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');

    $form['qr_copy_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('QR Copy Text'),
      '#default_value' => $this->configuration['qr_copy_text'] ?? '',
    ];

    $form['block_aligned'] = [
      '#type' => 'select',
      '#title' => $this->t('Block aligned'),
      '#default_value' => $this->configuration['block_aligned'],
      '#options' => [
        self::LEFT_ALIGNED => $this->t('Left aligned'),
        self::RIGHT_ALIGNED => $this->t('Right aligned'),
        self::CENTER_ALIGNED => $this->t('Center aligned'),
      ],
    ];
    $form['text_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#default_value' => $this->configuration['text_alignment'] ?? 'center',
      '#options' => [
        'center' => $this->t('Center'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[block_aligned]"]' => ['value' => 'center'],
        ],
      ],
    ];

    $form['media_item_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Media Item Type'),
      '#attributes' => ['class' => ['media_item_type']],
      '#default_value' => !empty($this->configuration['media_item_type']) ? $this->configuration['media_item_type'] : $this->getOldDefaultValueOfConfig($this->configuration['image'], $this->configuration['enable_3D_asset']),
      '#options' => [
        self::KEY_OPTION_NULL => $this->t('Select'),
        self::KEY_OPTION_3D_ASSET => $this->t('3D asset (glTF/GLB)'),
        self::KEY_OPTION_IMAGE => $this->t('Image'),
        self::KEY_OPTION_VIDEO => $this->t('Video'),
        self::KEY_OPTION_AUDIO => $this->t('Audio'),
        self::KEY_OPTION_YOUTUBE_VIDEO => $this->t('External Youtube URL'),
      ],
    ];
    $form['asset_url_3D'] = [
      '#type' => 'textfield',
      '#title' => $this->t('3D Asset URL'),
      '#default_value' => $this->configuration['asset_url_3D'],
      '#description' => $this->t("3D Asset URL should be glTF/GLB format."),
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_3D_ASSET]],
        ],
        'required' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_3D_ASSET]],
        ],
      ],
    ];
    $form['select_audio_upload_option'] = [
      '#title' => $this->t('Choose upload type'),
      '#type' => 'select',
      '#attributes' => ['class' => ['select_audio_upload_option']],
      '#options' => [
        'audio_upload' => $this->t('Upload Audio'),
        'audio_upload_url' => $this->t('Using Audio URL'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_AUDIO]],
        ],
      ],
      '#default_value' => $this->configuration['select_audio_upload_option'],
    ];
    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Audio File'),
      '#attributes' => ['class' => ['freeform-story-audio-upload-option']],
      '#upload_location' => 'public://',
      '#upload_validators' => [
        'file_validate_extensions' => ['mp3 wav aac'],
      ],
      '#description' => $this->t('Please upload audio file as per allowed format (mp3, wav, aac) only.'),
      '#default_value' => $this->configuration['file_upload'],
      '#states' => [
        'required' => [
          [':input[name="settings[select_audio_upload_option]"]' => ['value' => 'audio_upload']],
          'and' => 'and',
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_AUDIO]],
        ],
      ],
    ];
    $form['file_upload_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audio URL'),
      '#default_value' => $this->configuration['file_upload_url'],
      '#attributes' => ['class' => ['freeform-story-audio-url-option']],
      '#description' => $this->t("Please enter Audio URL."),
      '#states' => [
        'required' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_AUDIO]],
          'and' => 'and',
          [':input[name="settings[select_audio_upload_option]"]' => ['value' => 'audio_upload_url']],
        ],
      ],
    ];
    $form['audio_player_placement'] = [
      '#type' => 'select',
      '#title' => $this->t('Audio Player Placement'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->configuration['audio_player_placement'] ?? 'top',
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_AUDIO]],
          'and' => 'and',
          [':input[name="settings[block_aligned]"]' => ['value' => self::CENTER_ALIGNED]],
        ],
      ],
    ];
    $form['audio_player_background'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Audio Player Background Color'),
      '#default_value' => $this->configuration['audio_player_background'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#attributes' => ['class' => ['show-clear']],
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_AUDIO]],
        ],
      ],
    ];
    $form['video_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video title'),
      '#default_value' => $this->configuration['video_title'],
      '#description' => $this->t("Video title for GTM tracking"),
      '#maxlength' => 60,
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
        'required' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
      ],
    ];

    // Entity Browser element for background image.
    $form['image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
      $this->configuration['image'], $form_state, 1, 'thumbnail', function ($form_state) {
        return $form_state->getValue(['settings', 'media_item_type']) === self::KEY_OPTION_IMAGE;
      });
    // Convert the wrapping container to a details element.
    $form['image']['#type'] = 'details';
    $form['image']['#title'] = $this->t('Image');
    $form['image']['#open'] = TRUE;
    $form['image']['#states'] = [
      'visible' => [
        [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
      ],
      'required' => [
        ':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE],
      ],
    ];

    $form['use_mobile_video'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the same vertical video on tablets and desktop'),
      '#description' => $this->t('The same vertical videos will display on tablet and desktop devices, if the checkbox is selected.  Before checking the box, please ensure that you have uploaded the appropriate vertical video for mobile. 
      Please note when this checkbox is selected, the desktop video will become disabled and optional, but the vertical video for mobile devices will be required.'),
      '#default_value' => $this->configuration['use_mobile_video'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
      ],
    ];

    // Entity Browser element for video.
    $video_default = $this->configuration['video'] ?? NULL;
    $form['video'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_VIDEO_ID,
      $video_default, $form_state, 1, 'default', function ($form_state) {
        if ($form_state->getValue(['settings', 'media_item_type']) === self::KEY_OPTION_VIDEO &&
          $form_state->getValue(['settings', 'use_mobile_video']) == FALSE) {
          return TRUE;
        }
        else {
          return FALSE;
        }
      });
    // Convert the wrapping container to a details element.
    $form['video']['#type'] = 'details';
    $form['video']['#title'] = $this->t('Desktop Video');
    $form['video']['#open'] = TRUE;
    $form['video']['#description'] = $this->t('<div class="image-note"><div class="focal-note"><b>NOTE</b>: Recommended video size is 1280 * 720</div></div>');
    $form['video']['#states'] = [
      'visible' => [
        [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
      ],
      'disabled' => [
        [':input[name="settings[use_mobile_video]"]' => ['checked' => TRUE]],
      ],
      'required' => [
        ':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO],
      ],
    ];

    // Entity Browser element for video mobile.
    $video_default_mobile = $this->configuration['video_mobile'] ?? NULL;
    $form['video_mobile'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_VIDEO_ID,
      $video_default_mobile, $form_state, 1, 'default', function ($form_state) {
        return FALSE;
      });
    // Convert the wrapping container to a details element.
    $form['video_mobile']['#type'] = 'details';
    $form['video_mobile']['#title'] = $this->t('Mobile Video');
    $form['video_mobile']['#open'] = TRUE;
    $form['video_mobile']['#required'] = FALSE;
    $form['video_mobile']['#description'] = $this->t('<div class="image-note"><div class="focal-note"><b>NOTE</b>: Recommended video size is 720 * 720</div></div>');
    $form['video_mobile']['#states'] = [
      'visible' => [
        [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
      ],
    ];

    $form['hide_volume'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Volume'),
      '#default_value' => $this->configuration['hide_volume'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
      ],
    ];
    $get_version = $this->configFactory->getEditable('mars_lighthouse.settings')->get('api_version');
    if ($get_version == 'v3') {
      $form['stop_autoplay'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Stop Autoplay'),
        '#default_value' => $this->configuration['stop_autoplay'] ?? TRUE,
        '#states' => [
          'visible' => [
            [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
          ],
        ],
      ];
    }

    // External youtube video.
    $form['external_video_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External Youtube URL'),
      '#default_value' => $this->configuration['external_video_url'],
      '#description' => $this->t("Please provide external youtube url."),
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_YOUTUBE_VIDEO]],
        ],
        'required' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_YOUTUBE_VIDEO]],
        ],
      ],
    ];

    $form['external_video_url_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External Youtube Title'),
      '#default_value' => $this->configuration['external_video_url_title'],
      '#description' => $this->t("Please provide external youtube title."),
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_YOUTUBE_VIDEO]],
        ],
        'required' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_YOUTUBE_VIDEO]],
        ],
      ],
    ];

    $form['icon_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Change The Block Resolution'),
      '#default_value' => $this->configuration['icon_view'] ?? FALSE,
      '#states' => [
        'invisible' => [
          [':input[name="settings[block_aligned]"]' => ['value' => 'center']],
        ],
      ],
    ];
    $form['use_actual_size'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use actual size for Mobile Images'),
      '#default_value' => $this->configuration['use_actual_size'] ?? FALSE,
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[icon_view]"]' => ['checked' => TRUE],
            ':input[name="settings[block_aligned]"]' => ['value' => 'left'],
            ':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE],
          ],
          [
            ':input[name="settings[icon_view]"]' => ['checked' => TRUE],
            ':input[name="settings[block_aligned]"]' => ['value' => 'right'],
            ':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE],
          ],
        ],
      ],
    ];
    $form['available_resolutions'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Block Resolution'),
      '#default_value' => $this->configuration['available_resolutions'],
      '#options' => [
        self::VAL_10_90 => $this->t('10:90'),
        self::VAL_20_80 => $this->t('20:80'),
        self::VAL_30_70 => $this->t('30:70'),
        self::VAL_40_60 => $this->t('40:60'),
        self::VAL_50_50 => $this->t('50:50'),
        self::VAL_60_40 => $this->t('60:40'),
        self::VAL_70_30 => $this->t('70:30'),
        self::VAL_80_20 => $this->t('80:20'),
        self::VAL_90_10 => $this->t('90:10'),
      ],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[icon_view]"]' => ['checked' => TRUE],
            ':input[name="settings[block_aligned]"]' => ['value' => 'left'],
          ],
          [
            ':input[name="settings[icon_view]"]' => ['checked' => TRUE],
            ':input[name="settings[block_aligned]"]' => ['value' => 'right'],
          ],
        ],
      ],
    ];
    $form['with_cta'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('With/without CTA'),
      '#default_value' => $this->configuration['with_cta'] ?? FALSE,
      '#description'   => $this->t('To make image as clickable, check With/Without CTA option and enable to make image clickable checkbox. The image URL will be same as CTA link URL and if CTA is not required, remove CTA Link title and retain only the URL for image to be clickable.'),
    ];
    $form['img_clickable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to make image clickable'),
      '#default_value' => $this->configuration['img_clickable'] ?? FALSE,
      '#description'   => $this->t("Please check to make image clickable."),
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
          'and' => 'and',
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
      '#prefix' => '<div class="img_click_div">',
      '#suffix' => '</div>',
    ];

    $form['image_video_position_mobile'] = [
      '#title' => $this->t('Choose Image/Video position for Mobile'),
      '#type' => 'select',
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->configuration['image_video_position_mobile'] ?? 'top',
      '#states' => [
        'visible' => [
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
          'or',
          [':input[name="settings[media_item_type]"]' => ['value' => self::KEY_OPTION_VIDEO]],
        ],
      ],
    ];

    $form['header_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header 1'),
      '#default_value' => $this->configuration['header_1'],
      '#maxlength' => !empty($character_limit_config->get('freeform_story_block_header_1')) ? $character_limit_config->get('freeform_story_block_header_1') : 60,
    ];
    $form['use_custom_header_1_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Header 1 color'),
      '#default_value' => $this->configuration['use_custom_header_1_color'] ?? FALSE,
    ];
    $form['custom_header_1_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Header 1 color override'),
      '#default_value' => $this->configuration['custom_header_1_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color B.'),
      '#states' => [
        'visible' => [
          [':input[name="settings[use_custom_header_1_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['header_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header 2'),
      '#default_value' => $this->configuration['header_2'],
      '#maxlength' => !empty($character_limit_config->get('freeform_story_block_header_2')) ? $character_limit_config->get('freeform_story_block_header_2') : 60,
    ];
    $form['use_custom_header_2_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use header 2 Color'),
      '#default_value' => $this->configuration['use_custom_header_2_color'] ?? FALSE,
    ];
    $form['hide_graphic_divider'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Graphic Divider'),
      '#default_value' => $this->configuration['hide_graphic_divider'] ?? TRUE,
    ];

    $form['custom_header_2_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Header 2 Color override'),
      '#default_value' => $this->configuration['custom_header_2_color'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#attributes' => ['class' => ['show-clear']],
      '#states' => [
        'visible' => [
          [':input[name="settings[use_custom_header_2_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['cta_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link Title'),
      '#maxlength' => !empty($character_limit_config->get('freeform_story_cta_link_title')) ? $character_limit_config->get('freeform_story_cta_link_title') : 15,
      '#default_value' => $this->configuration['cta_title'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link URL'),
      '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
      '#maxlength' => !empty($character_limit_config->get('freeform_story_cta_link_url')) ? $character_limit_config->get('freeform_story_cta_link_url') : 2048,
      '#default_value' => $this->configuration['cta_url'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open CTA link in a new tab'),
      '#default_value' => $this->configuration['cta_new_window'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_border'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('please check to apply border to cta button'),
      '#default_value' => $this->configuration['cta_border'] ?? FALSE,
      '#states' => [
        'visible' => [
          [':input[name="settings[with_cta]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_remove_gradian'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove freeform gradient background from button'),
      '#default_value' => $this->configuration['cta_remove_gradian'] ?? FALSE,
      '#description'   => $this->t("Please check to remove freeform gradient background from button."),
    ];
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID (Quick link)'),
      '#description' => $this->t('Use element ID("ele_id")directly in Page Link for Quick link component. To use the attribute as deep link reference or to use it as internal link, add #ele_id at the end of the page URL to generate the href reference of that particular component on the page. Use the URL to link the component from any of other component on same page/different page.'),
      '#default_value' => $this->configuration['element_id'],
    ];
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['body']['value'] ?? '',
      '#format' => $this->configuration['body']['format'] ?? 'rich_text',
      '#maxlength' => !empty($character_limit_config->get('freeform_story_block_description')) ? $character_limit_config->get('freeform_story_block_description') : 1000,
    ];
    $form['use_custom_description_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use description color'),
      '#default_value' => $this->configuration['use_custom_description_color'] ?? FALSE,
    ];
    $form['add_top_spacing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add top spacing'),
      '#default_value' => $this->configuration['add_top_spacing'] ?? TRUE,
    ];
    $form['add_bottom_spacing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add bottom spacing'),
      '#default_value' => $this->configuration['add_bottom_spacing'] ?? TRUE,
    ];
    $form['custom_description_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Description Color Override'),
      '#default_value' => $this->configuration['custom_description_color'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
      '#attributes' => ['class' => ['show-clear']],
      '#states' => [
        'visible' => [
          [':input[name="settings[use_custom_description_color]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['background_shape'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Background shape'),
      '#default_value' => $this->configuration['background_shape'] ?? FALSE,
    ];
    $form['vertical_alignment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertically Aligned Imaged'),
      '#description'   => $this->t('Select to make the image vertically center aligned'),
      '#default_value' => $this->configuration['vertical_alignment'] ?? FALSE,
      '#states' => [
        'invisible' => [
          [':input[name="settings[block_aligned]"]' => ['value' => 'center']],
        ],
      ],
    ];
    $form['use_custom_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom color'),
      '#default_value' => $this->configuration['use_custom_color'] ?? FALSE,
    ];
    $form['custom_background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Background Color Override'),
      '#default_value' => $this->configuration['custom_background_color'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to color E.'),
      '#attributes' => ['class' => ['show-clear']],
    ];
    // CTA background and Text color.
    $form['cta_bg_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override CTA background and Text color.'),
      '#default_value' => $this->configuration['cta_bg_text'] ?? FALSE,
    ];
    $form['cta_background'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('CTA Background Color Override'),
      '#default_value' => $this->configuration['cta_bg_text'] == TRUE && !empty($this->configuration['cta_bg_text']) ? $this->configuration['cta_background'] : '',
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['cta_text_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('CTA Text Color'),
      '#default_value' => $this->configuration['cta_bg_text'] == TRUE && !empty($this->configuration['cta_bg_text']) ? $this->configuration['cta_text_color'] : '',
      '#states' => [
        'visible' => [
          [':input[name="settings[cta_bg_text]"]' => ['checked' => TRUE]],
        ],
      ],

    ];
    $form['use_original_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use original image'),
      '#default_value' => $this->configuration['use_original_image'] ?? FALSE,
    ];
    $form['override_bullet_points'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override bullet points with icons'),
      '#default_value' => $this->configuration['override_bullet_points'] ?? FALSE,
    ];

    $validate_callback = function ($form_state) {
      return $form_state->getValue(['settings', 'override_bullet_points']) ? TRUE : FALSE;
    };

    $form['icon'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
      $this->configuration['icon'], $form_state, 1, 'thumbnail', $validate_callback);
    // Convert icon field with wrapping container to a details element.
    $form['icon']['#type'] = 'details';
    $form['icon']['#title'] = $this->t('Icon');
    $form['icon']['#open'] = TRUE;
    $form['icon']['#required'] = TRUE;
    $form['icon']['#states'] = [
      'visible' => [
        [':input[name="settings[override_bullet_points]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['icon_color_override'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Icon Color Override'),
      '#default_value' => $this->configuration['icon_color_override'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#states' => [
        'visible' => [
          [':input[name="settings[override_bullet_points]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['icon_bg_color_override'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Icon Background Color Override'),
      '#default_value' => $this->configuration['icon_bg_color_override'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#states' => [
        'visible' => [
          [':input[name="settings[override_bullet_points]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $this->buildOverrideColorElement($form, $this->configuration);
    $form['#attached']['library'][] = 'mars_common/freeformstory';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $body = $this->configuration['body']['value'];
    $qr_copy_txt = $this->configuration['qr_copy_text'] ?? '';
    if (!empty($qr_copy_txt)) {
      $options = new QROptions([
        'version'             => QRCode::VERSION_AUTO,
        'scale'               => 4,
        'imageBase64'         => FALSE,
        'bgColor'             => '#FFFFFF',
        'imageTransparent'    => FALSE,
      ]);
      $out = (new QRCode($options))->render($qr_copy_txt);
      header('Content-type: image/png');
    }

    $build['#qr_copy_text'] = $qr_copy_txt ?? '';
    $build['#qr_code'] = $out ?? '';
    $build['#attached']['drupalSettings']['mars_common']['qr_code'] = $out ?? '';
    $build['#block_aligned'] = $this->configuration['block_aligned'];
    $build['#header_1'] = $this->languageHelper->translate($this->configuration['header_1']);
    $build['#header_2'] = $this->languageHelper->translate($this->configuration['header_2']);
    $build['#element_id'] = $this->configuration['element_id'];
    $build['#graphic_divider'] = $this->themeConfiguratorParser->getGraphicDivider();
    $build['#hide_graphic_divider'] = $this->configuration['hide_graphic_divider'] ?? TRUE;
    $build['#text_alignment'] = $this->configuration['text_alignment'] ?? '';
    $build['#background_shape'] = $this->configuration['background_shape'] == 1 ? 'true' : 'false';
    $build['#add_top_spacing'] = $this->configuration['add_top_spacing'] == 1 ? 'true' : 'false';
    $build['#add_bottom_spacing'] = $this->configuration['add_bottom_spacing'] == 1 ? 'true' : 'false';
    $build['#vertical_alignment'] = !empty($this->configuration['vertical_alignment']) ? $this->configuration['vertical_alignment'] : FALSE;
    $build['#icon_view'] = !empty($this->configuration['icon_view']) ? $this->configuration['icon_view'] : FALSE;
    $build['#use_actual_size'] = !empty($this->configuration['use_actual_size']) ? $this->configuration['use_actual_size'] : FALSE;
    $build['#available_resolutions'] = $this->configuration['available_resolutions'];
    $build['#with_cta'] = $this->configuration['with_cta'];
    if ($this->configuration['with_cta'] == 1) {
      $build['#cta_title'] = $this->languageHelper->translate($this->configuration['cta_title']);
      $build['#cta_url'] = $this->configuration['cta_url'];
      $build['#cta_new_window'] = $this->configuration['cta_new_window'] == 1 ? '_blank' : '_self';
      $build['#cta_border'] = $this->configuration['cta_border'] == 1 ? 'yes_border' : 'no_border';
    }
    $build['#cta_remove_gradian'] = $this->configuration['cta_remove_gradian'] == 1 ? 'cta_remove_gradian' : '';
    if (!empty($this->configuration['image'])) {
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['image']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
        $build['#image'] = $mediaParams['src'];
        $build['#image_alt'] = $mediaParams['alt'];
      }
    }

    $build['#use_mobile_video'] = $this->configuration['use_mobile_video'] ?? FALSE;
    if (!empty($this->configuration['video'])) {
      $get_version = $this->configFactory->getEditable('mars_lighthouse.settings')->get('api_version');
      $player_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('player_id');
      $account_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('account_id');
      $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['video']);
      $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
      $video_url = NULL;
      $media_id = $this->mediaHelper
        ->getIdFromEntityBrowserSelectValue($this->configuration['video']);

      if ($media_id) {
        $media_params = $this->mediaHelper->getMediaParametersById($media_id);
        $bcove_videoid = $media_params['bcove'];
        if ($get_version == 'v3' && !empty($bcove_videoid)) {
          $script_src = 'https://players.brightcove.net/' . $account_id_lighthouse . '/' . $player_id_lighthouse . '_default/index.min.js';
          $build['#media'] = [
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
          $video_url = $this->fileUrlGenerator->generateAbsoluteString($media_params['src']);
        }
      }
      $build['#video_src'] = $video_url;
      $build['#hide_volume'] = !empty($this->configuration['hide_volume']) ? TRUE : FALSE;
      $build['#stop_autoplay'] = $this->configuration['stop_autoplay'] ?? TRUE;
    }

    if (!empty($this->configuration['video_mobile'])) {
      $get_version = $this->configFactory->getEditable('mars_lighthouse.settings')->get('api_version');
      $player_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('player_id');
      $account_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('account_id');
      $video_mobile_url = NULL;
      $video_mobile_id = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['video_mobile']);
      if ($video_mobile_id) {
        $video_params_mob = $this->mediaHelper->getMediaParametersById($video_mobile_id);
        $bcove_videoid = $video_params_mob['bcove'];
        if ($get_version == 'v3' && !empty($bcove_videoid)) {
          $script_src = 'https://players.brightcove.net/' . $account_id_lighthouse . '/' . $player_id_lighthouse . '_default/index.min.js';
          $build['#media_mobile'] = [
            'video' => TRUE,
            'src' => $video_params_mob['src'] ?? NULL,
            'video_id' => $bcove_videoid,
            'account_id' => $account_id_lighthouse,
            'player' => $player_id_lighthouse,
            'embed' => 'default',
            'script_src' => $script_src,
          ];
        }
        if (!isset($video_params_mob['error'])) {
          $video_mobile_url = $this->fileUrlGenerator->generateAbsoluteString($video_params_mob['src']);
        }
      }
      $build['#video_src_mobile'] = $video_mobile_url;
    }

    if (!empty($this->configuration['override_bullet_points']) && !empty($this->configuration['icon'])) {
      $iconMediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($this->configuration['icon']);
      $iconParams = $this->mediaHelper->getMediaParametersById($iconMediaId);
      if (!($iconParams['error'] ?? FALSE) && ($iconParams['src'] ?? FALSE)) {
        $icon_url = $this->fileUrlGenerator->generateAbsoluteString($iconParams['src']);
        if (strpos($icon_url, 'svg') !== FALSE) {
          $icon_path = DRUPAL_ROOT . $icon_url;
          $svg = SVG::createFromFile($icon_path, '');
          $icon_color_pattern = $this->configuration['icon_color_override'] ? $this->configuration['icon_color_override'] : '';
          $icon_bg_color_pattern = $this->configuration['icon_bg_color_override'] ? $this->configuration['icon_bg_color_override'] : '';
          $body = $this->overrideBulletPointsInBody($svg, $icon_bg_color_pattern, $icon_color_pattern);
        }
      }
    }
    $build['#body'] = $this->languageHelper->translate($body);

    if ($this->configuration['background_shape'] == 1) {
      $build['#brand_shape'] = $this->themeConfiguratorParser->getBrandShapeWithoutFill();
    }
    $build['#custom_background_color'] = $this->configuration['custom_background_color'];
    $build['#use_custom_color'] = (bool) $this->configuration['use_custom_color'];
    $build['#use_original_image'] = (bool) $this->configuration['use_original_image'];
    $build['#use_custom_header_1_color'] = (bool) $this->configuration['use_custom_header_1_color'];
    $build['#use_custom_header_2_color'] = (bool) $this->configuration['use_custom_header_2_color'];
    $build['#use_custom_description_color'] = (bool) $this->configuration['use_custom_description_color'];
    $build['#text_color_override'] = FALSE;
    if (!empty($this->configuration['override_text_color']['override_color'])) {
      $build['#text_color_override'] = static::$overrideColor;
    }
    $build['#custom_header_1_color'] = ($this->configuration['use_custom_header_1_color'] && !$build['#text_color_override']) ? $this->configuration['custom_header_1_color'] : $build['#text_color_override'];
    $build['#custom_header_2_color'] = ($this->configuration['use_custom_header_2_color'] && !$build['#text_color_override']) ? $this->configuration['custom_header_2_color'] : $build['#text_color_override'];
    $build['#custom_description_color'] = ($this->configuration['use_custom_description_color'] && !$build['#text_color_override']) ? $this->configuration['custom_description_color'] : $build['#text_color_override'];
    $build['#override_bullet_points'] = !empty($this->configuration['override_bullet_points']) ? $this->configuration['override_bullet_points'] : FALSE;
    $build['#media_item_type'] = !empty($this->configuration['media_item_type']) ? $this->configuration['media_item_type'] : $this->getOldDefaultValueOfConfig($this->configuration['image'], $this->configuration['enable_3D_asset']);
    $build['#asset_url_3D'] = $this->configuration['asset_url_3D'];
    $build['#video_title'] = $this->configuration['video_title'];
    $build['#audio_player_placement'] = $this->configuration['audio_player_placement'];
    $build['#audio_player_background'] = !empty($this->configuration['audio_player_background']) ? $this->configuration['audio_player_background'] : '';
    $build['#use_cta_background_text_color'] = $this->configuration['cta_bg_text'] ?? FALSE;
    $build['#external_video_url'] = $this->configuration['external_video_url'];
    $build['#external_video_url_title'] = $this->configuration['external_video_url_title'];
    if (!empty($this->configuration['media_item_type']) && $this->configuration['media_item_type'] == 'image' && (bool) $this->configuration['with_cta'] == TRUE) {
      $build['#img_clickable'] = (bool) $this->configuration['img_clickable'];
    }
    if ($this->configuration['select_audio_upload_option'] == 'audio_upload' && !empty($this->configuration['file_upload'])) {
      $fid = $this->configuration['file_upload'][0];
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($file) {
        $build['#audio_url'] = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
      }
    }
    elseif ($this->configuration['select_audio_upload_option'] == 'audio_upload_url' && !empty($this->configuration['file_upload_url'])) {
      $build['#audio_url'] = $this->configuration['file_upload_url'];
    }
    $block_build_styles = mars_common_block_build_style($this->configuration);
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    // Image video position mobile.
    $build['#image_video_position_mobile'] = !empty($this->configuration['image_video_position_mobile']) ? $this->configuration['image_video_position_mobile'] : 'top';
    $build['#theme'] = 'freeform_story_block';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $media_item_type = $form_state->getValue('media_item_type');
    $asset_url_3D = $form_state->getValue('asset_url_3D');
    $video_title = $form_state->getValue('video_title');
    $file_upload = $form_state->getValue('file_upload');
    $file_upload_url = $form_state->getValue('file_upload_url');
    $select_audio_upload_option = $form_state->getValue('select_audio_upload_option');
    $use_mobile_video = $form_state->getValue('use_mobile_video');
    $video_mobile = $form_state->getValue('video_mobile');
    $external_video_url = $form_state->getValue('external_video_url');
    $external_video_url_title = $form_state->getValue('external_video_url_title');
    if ($media_item_type == self::KEY_OPTION_AUDIO && $select_audio_upload_option == 'audio_upload' && empty($file_upload)) {
      $form_state->setErrorByName('file_upload', $this->t('Audio File Upload is required, if Media Item Type is selected as "Audio".'));
    }
    if ($media_item_type == self::KEY_OPTION_AUDIO && $select_audio_upload_option == 'audio_upload_url' && empty($file_upload_url)) {
      $form_state->setErrorByName('file_upload_url', $this->t('Audio URL is required, if Media Item Type is selected as "Audio".'));
    }
    if ($media_item_type == self::KEY_OPTION_3D_ASSET && empty($asset_url_3D)) {
      $form_state->setErrorByName('asset_url_3D', $this->t('3D Asset URL is required, if Media Item Type is selected as "Enable 3D Asset capabilities".'));
    }
    if ($media_item_type == self::KEY_OPTION_VIDEO && empty($video_title)) {
      $form_state->setErrorByName('video_title', $this->t('Video title is required, if Media Item Type is selected as "Video".'));
    }
    if ($media_item_type == self::KEY_OPTION_YOUTUBE_VIDEO && empty($external_video_url)) {
      $form_state->setErrorByName('external_video_url', $this->t('External video url is required, if Media Item Type is selected as "External Youtube URL".'));
    }
    if ($media_item_type == self::KEY_OPTION_YOUTUBE_VIDEO && empty($external_video_url_title)) {
      $form_state->setErrorByName('external_video_url_title', $this->t('External video title is required, if Media Item Type is selected as "External Youtube URL".'));
    }
    if ((($use_mobile_video)) && (empty($video_mobile['selected'])) && ($media_item_type == self::KEY_OPTION_VIDEO)) {
      $form_state->setErrorByName('video_mobile', $this->t('Required field Mobile video.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockSubmit() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['block_aligned'] == 'center') {
      $values['icon_view'] = '';
      $values['use_actual_size'] = '';
      $values['vertical_alignment'] = '';
    }
    if ($values['media_item_type'] == self::KEY_OPTION_AUDIO && $values['select_audio_upload_option'] == 'audio_upload' && isset($values['file_upload']) && !empty($values['file_upload'])) {
      $file_id = $values['file_upload'][0];
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $values['file_upload_url'] = '';
      }
    }
    elseif ($values['media_item_type'] == self::KEY_OPTION_AUDIO && $values['select_audio_upload_option'] == 'audio_upload_url' && !empty($values['file_upload_url'])) {
      $values['file_upload'] = '';
    }
    $this->setConfiguration($values);
    $this->configuration['image'] = $this->getEntityBrowserValue($form_state, 'image');
    $this->configuration['video'] = $this->getEntityBrowserValue($form_state, 'video');
    $this->configuration['video_mobile'] = $this->getEntityBrowserValue($form_state, 'video_mobile');
    $this->configuration['icon'] = $this->getEntityBrowserValue($form_state, 'icon');

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = $this->getConfiguration();
    return [
      'block_aligned' => $config['block_aligned'] ?? '',
      'header_1' => $config['header_1'] ?? $this->t('Header 1'),
      'header_2' => $config['header_2'] ?? '',
      'element_id' => $config['element_id'] ?? '',
      'body' => $config['body']['value'] ?? '',
      'background_shape' => $config['background_shape'] ?? '',
      'image' => $config['image'] ?? '',
      'use_mobile_video' => $config['use_mobile_video'] ?? FALSE,
      'video' => $config['video'] ?? '',
      'video_mobile' => $config['video_mobile'] ?? '',
      'custom_background_color' => $config['custom_background_color'] ?? '',
      'cta_background' => $config['cta_background'] ?? '',
      'cta_bg_text' => $config['cta_bg_text'] ?? '',
      'use_custom_color' => $config['use_custom_color'] ?? '',
      'use_original_image' => $config['use_original_image'] ?? 0,
      'add_top_spacing' => $config['add_top_spacing'] ?? TRUE,
      'add_bottom_spacing' => $config['add_bottom_spacing'] ?? TRUE,
      'media_item_type' => $config['media_item_type'] ?? '',
      'asset_url_3D' => $config['asset_url_3D'] ?? '',
      'video_title' => $config['video_title '] ?? '',
      'text_alignment' => $config['text_alignment'] ?? '',
      'override_bullet_points' => $config['override_bullet_points'] ?? FALSE,
      'image_video_position_mobile' => $config['image_video_position_mobile'] ?? 'top',
      'external_video_url' => $config['external_video_url '] ?? '',
      'external_video_url_title' => $config['external_video_url_title '] ?? '',
    ];
  }

  /**
   * Override Bullet points with icon in Body.
   */
  public function overrideBulletPointsInBody($svg, $bg_clr_pattern, $icon_color_pattern) {
    $body = $this->configuration['body']['value'];
    $body = htmlspecialchars_decode(htmlentities($body));
    $bodyHtml = '';
    $dom = new \DOMDocument();
    $dom->loadHTML($body);
    $img_data = self::convertSvgToBase64WithFill($svg, $bg_clr_pattern, $icon_color_pattern);
    $img_element = $dom->createElement('img');
    $img_element->setAttribute('src', $img_data);
    $img_element->setAttribute('class', 'icon-blist');
    $li_nodes = $dom->getElementsByTagName('li');
    foreach ($li_nodes as $li) {
      $li->appendChild($img_element->cloneNode(TRUE));
      $bodyHtml = $dom->saveHTML($dom);
    }
    return $bodyHtml;
  }

  /**
   * Converting Svg to base64 data with fill colors.
   */
  public static function convertSvgToBase64WithFill($svg, $bg_clr_pattern, $icon_color_pattern) {
    $img_data = '';
    $dom2 = new \DOMDocument();
    $dom2->loadXML($svg);
    if (!empty($icon_color_pattern)) {
      $svg_path_node = $dom2->getElementsByTagName('path')->item(0);
      $svg_path_node->setAttribute('fill', $icon_color_pattern);
    }
    if (!empty($bg_clr_pattern)) {
      $svg_path_node = $dom2->getElementsByTagName('path')->item(1);
      $svg_path_node->setAttribute('fill', $bg_clr_pattern);
    }
    $svg_string = $dom2->saveXML();
    $img_data = 'data:image/svg+xml;base64,' . base64_encode($svg_string);
    return $img_data;
  }

  /**
   * Checking old config default value.
   */
  protected function getOldDefaultValueOfConfig($old_default_image, $old_default_3d_asset) {
    $default_value_of_config = NULL;
    if (!empty($old_default_3d_asset)) {
      $default_value_of_config = self::KEY_OPTION_3D_ASSET;
    }
    elseif (!empty($old_default_image)) {
      $default_value_of_config = self::KEY_OPTION_IMAGE;
    }
    return $default_value_of_config;
  }

}
