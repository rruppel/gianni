<?php

namespace Drupal\ponto_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements InputDemo form controller.
 *
 * This example demonstrates the different input elements that are used to
 * collect data in a form.
 */
class ExportPonto extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

       

    // Select.
    $form['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Mês'),
      '#options' => [
        '1' => $this->t('Janeiro'),
        '2' => $this->t('Fevereiro'),
        '3' => $this->t('Março'),
        '4' => $this->t('Abril'),
        '5' => $this->t('Maio'),
        '6' => $this->t('Junho'),
        '7' => $this->t('Julho'),
        '8' => $this->t('Agosto'),
        '9' => $this->t('Setembro'),
        '10' => $this->t('Outubro'),
        '11' => $this->t('Novembro'),
        '12' => $this->t('Dezembro'),
       ],
      '#empty_option' => $this->t('- Escolha o mês -'),
      '#default_value' => (date("m")),
    ];

    $form['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Ano'),
      '#options' => [
        '-2' => date("Y", strtotime("-2 year") ),
        '-1' => date("Y", strtotime("-1 year") ),
        '0' => date("Y"),
        '1' => date("Y", strtotime("+1 year") ),
        '2' => date("Y", strtotime("+2 year") ),
       ],
      '#empty_option' => $this->t('- Escolha o ano -'),
      '#default_value' => 0,
    ];
    

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];


    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#description' => $this->t('Submit, #type = submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_ponto_export_month_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Find out what was submitted.
    $values = $form_state->getValues();
    $month = $values['month'];
    
    $query = \Drupal::entityQuery('node');
          $query->condition('type', '')
                  ->condition('field_data', $data);
    $nids = $query->execute();
    
    
    
    drupal_set_message($message);
    
  }

}
