<?php

namespace Drupal\pn_common\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search Filters configuration form controller.
 */
class SearchFiltersConfigurationForm extends ConfigFormBase {

  /**
   * Setting configuration ID.
   */
  public const CONFIG_NAME = 'pn_common.searchfilters_settings';

  /**
   * Search FiltersConfiguration constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pn_common.searchfilters_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(self::CONFIG_NAME);

    $form['search_filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Search Filtters Enable'),
      '#open' => TRUE,
    ];

    $form['search_filters']['enable_list'] = [
      '#type' => 'select',
      '#title' => $this->t('EnableList'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => [
        'food_type' => $this->t('Food Type'),
        'lifestage' => $this->t('Lifestage'),
        'size' => $this->t('Size'),
        'sub_brand' => $this->t('Sub Brand'),
        'format' => $this->t('Format'),
        'flavor' => $this->t('Flavor'),
        'texture' => $this->t('Texture'),
        'specie' => $this->t('Specie'),
        'allergies' => $this->t('Allergies'),
        'habitat' => $this->t('Habitat'),
        'health_goal' => $this->t('Health Goal'),
      ],
      '#default_value' => $config->get('search_filters.enable_list'),
      '#description' => $this->t('Dynamic search filters list selection for search and content hub pages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Exclude unnecessary elements before saving, buttons etc.
    $form_state->cleanValues();

    $config = $this->config(self::CONFIG_NAME);

    $config->set('search_filters.enable_list', $form_state->getValue('enable_list'));
    $config->save();

    parent::submitForm($form, $form_state);

  }

}
