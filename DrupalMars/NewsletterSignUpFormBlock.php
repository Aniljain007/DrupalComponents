<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Newsletter SignUp Form block.
 *
 * @Block(
 *   id = "newsletter_signup_form",
 *   admin_label = @Translation("MARS: Newsletter SignUp Form"),
 *   category = @Translation("Mars Common")
 * )
 */
class NewsletterSignUpFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();

    $form_url_prefix = 'https://cloud.confectionery.mars.com/';
    $form_id = $conf['form_id'] ?? '';

    $build['#form_url'] = $form_url_prefix . $form_id;
    $build['#theme'] = 'newsletter_signup_form_block';

    return $build;
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID'),
      '#default_value' => $this->configuration['form_id'] ?? '',
      '#required' => TRUE,
      '#size' => 65,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['form_id'] = $form_state->getValue('form_id');
  }

}
