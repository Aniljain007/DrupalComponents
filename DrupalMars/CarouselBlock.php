<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CarouselBlock is responsible for Carousel component logic.
 *
 * @Block(
 *   id = "carousel_block",
 *   admin_label = @Translation("MARS: Carousel component"),
 *   category = @Translation("Page components"),
 * )
 *
 * @package Drupal\mars_common\Plugin\Block
 */
class CarouselBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
    MediaHelper $media_helper,
    ThemeConfiguratorParser $theme_configurator_parser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
    $this->mediaHelper = $media_helper;
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
      $container->get('mars_common.language_helper'),
      $container->get('mars_media.media_helper'),
      $container->get('mars_common.theme_configurator_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items = [];
    $build = [];
    $player_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('player_id');
    $account_id_lighthouse = $this->configFactory->getEditable('mars_lighthouse.settings')->get('account_id');
    foreach ($config['carousel'] as $item_value) {
      if ($item_value['item_type'] == self::KEY_OPTION_IMAGE) {
        $media_id = $this->mediaHelper->getIdFromEntityBrowserSelectValue($item_value['image']);
      }
      elseif ($item_value['item_type'] == self::KEY_OPTION_VIDEO) {
        $media_id = $this->mediaHelper->getIdFromEntityBrowserSelectValue($item_value['video']);
      }
      $media_params = $this->mediaHelper->getMediaParametersById($media_id);
      $bcove_videoid = $media_params['bcove'];
      $script_src = 'https://players.brightcove.net/' . $account_id_lighthouse . '/' . $player_id_lighthouse . '_default/index.min.js';
      if (!($media_params['error'] ?? FALSE) && ($media_params['src'] ?? FALSE)) {
        $item = [
          'src' => $media_params['src'],
          'content' => $this->languageHelper->translate($item_value['description']),
          'video' => ($item_value['item_type'] == self::KEY_OPTION_VIDEO),
          'image' => ($item_value['item_type'] == self::KEY_OPTION_IMAGE),
          'alt' => $media_params['alt'] ?? '',
          'title' => $media_params['title'] ?? '',
          'hide_volume' => !empty($item_value['hide_volume']) ? TRUE : FALSE,
          'stop_autoplay' => !empty($item_value['stop_autoplay']) ? TRUE : FALSE,
          'video' => TRUE,
          'src' => $media_params['src'] ?? NULL,
          'video_id' => $bcove_videoid,
          'account_id' => $account_id_lighthouse,
          'player' => $player_id_lighthouse,
          'embed' => 'default',
          'script_src' => $script_src,
        ];
        $items[] = $item;
      }
    }

    $build['#brand_borders'] = $this->themeConfiguratorParser->getBrandBorder();
    $build['#element_id'] = $config['element_id'] ?? '';
    $build['#title'] = $this->languageHelper->translate($config['carousel_label'] ?? '');
    $build['#items'] = $items;
    $build['#theme'] = 'carousel_component';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');
    $form['carousel_label'] = [
      '#title'         => $this->t('Carousel title'),
      '#type'          => 'textfield',
      '#default_value' => $config['carousel_label'],
      '#maxlength' => !empty($character_limit_config->get('mars_carousel_component_title')) ? $character_limit_config->get('mars_carousel_component_title') : 55,
    ];

    $form['carousel'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Carousel items'),
      '#prefix' => '<div id="carousel-wrapper">',
      '#suffix' => '</div>',
    ];
    // Element Id for Carousel component.
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID (Carousel component)'),
      '#description' => $this->t('Use same element ID("ele_id")directly in Page Link of Quick link component to navigate within the page'),
      '#default_value' => $config['element_id'] ?? '',
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
      $form['carousel'][$key]['description'] = [
        '#title' => $this->t('Carousel item description'),
        '#type' => 'textarea',
        '#default_value' => $config['carousel'][$key]['description'] ?? NULL,
        '#maxlength' => !empty($character_limit_config->get('mars_carousel_item_description')) ? $character_limit_config->get('mars_carousel_item_description') : 255,
      ];

      /*
       * BC fix: There could be wrong array values stored under this key.
       * Currently the only valid value is a string, if it's not it then we
       * throw away this value.
       */
      $current_image_selection = $config['carousel'][$key]['image'] ?? NULL;
      if (!is_string($current_image_selection)) {
        $current_image_selection = NULL;
      }
      $form['carousel'][$key]['image'] = $this->getEntityBrowserForm(
        self::LIGHTHOUSE_ENTITY_BROWSER_ID,
        $current_image_selection,
        $form_state,
        1,
        'thumbnail',
        function ($form_state) use ($key) {
          $type = $form_state->getValue([
            'settings',
            'carousel',
            $key,
            'item_type',
          ]);
          return $type === self::KEY_OPTION_IMAGE;
        }
      );
      $form['carousel'][$key]['image']['#type'] = 'details';
      $form['carousel'][$key]['image']['#title'] = $this->t('List item image');
      $form['carousel'][$key]['image']['#open'] = TRUE;
      $form['carousel'][$key]['image']['#states'] = [
        'visible' => [
          [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
        ],
        'required' => [
          [':input[name="settings[carousel][' . $key . '][item_type]"]' => ['value' => self::KEY_OPTION_IMAGE]],
        ],
      ];

      /*
       * BC fix: There could be wrong array values stored under this key.
       * Currently the only valid value is a string, if it's not it then we
       * throw away this value.
       */
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
      if ($config['carousel'][$key]['item_type'] === self::KEY_OPTION_VIDEO) {
        $form['carousel'][$key]['hide_volume'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide Volume'),
          '#default_value' => $config['carousel'][$key]['hide_volume'] ?? FALSE,
        ];
        $form['carousel'][$key]['stop_autoplay'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Stop Autoplay'),
          '#default_value' => $config['carousel'][$key]['stop_autoplay'] ?? FALSE,
        ];
      }
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

    // Background color override.
    $form['carousel_background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Carousel Background Color Override'),
      '#default_value' => $config['carousel_background_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
    ];

    return $form;
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
    // Set Carousel Component background color.
    $carousel_bg_color = $this->configFactory->getEditable('emulsifymars.settings');
    if (isset($values['carousel']) && !empty($values['carousel'] && array_key_exists('carousel_background_color', $values))) {
      $carousel_bg_color->set('carousel_bg_color', $values['carousel_background_color']);
      $carousel_bg_color->save(TRUE);
    }
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
      }
    }
  }

}
