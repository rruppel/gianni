<?php

namespace Drupal\ponto_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
/**
 * Implements InputDemo form controller.
 *
 * This example demonstrates the different input elements that are used to
 * collect data in a form.
 */
class ExportPonto extends FormBase {

    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
                $container->get('entity.manager')
        );
    }

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
            '#default_value' => (date("M")),
        ];

        $form['year'] = [
            '#type' => 'select',
            '#title' => $this->t('Ano'),
            '#options' => [
                date("Y", strtotime("-2 year")) => date("Y", strtotime("-2 year")),
                date("Y", strtotime("-1 year")) => date("Y", strtotime("-1 year")),
                date("Y") => date("Y"),
                date("Y", strtotime("+1 year")) => date("Y", strtotime("+1 year")),
                date("Y", strtotime("+2 year")) => date("Y", strtotime("+2 year")),
            ],
            '#empty_option' => $this->t('- Escolha o ano -'),
            '#default_value' => date("Y"),
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

        $response = new StreamedResponse();
        // Find out what was submitted.
        $values = $form_state->getValues();
        $month = $values['month'];
        $year = $values['year'];


        $firstDate = DrupalDateTime::createFromArray(array('year' => $year, 'month' => $month, 'day' => 1));
        $firstDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $firstDateFormatted = $firstDate->format(DATETIME_DATETIME_STORAGE_FORMAT);

        $lastDate = DrupalDateTime::createFromArray(array('year' => $year, 'month' => ($month + 1), 'day' => 1));
        $lastDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $lastDateFormatted = $lastDate->format(DATETIME_DATETIME_STORAGE_FORMAT);



        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'marcacao_ponto')
                ->condition('field_data.value', array($firstDateFormatted, $lastDateFormatted), 'BETWEEN');

        $nids = $query->execute();

        // We get the node storage object.
        $node_storage = $this->entityManager->getStorage('node');

        $nodes = $node_storage->loadMultiple($nids);

        $response->headers->set('Content-Type', 'text/plain');
        $response->setCallback(function () use($nodes) {
            $i = 1;
            foreach ($nodes as $node) {
                $inicio =  $node->field_horario_inicio_oficial->getString() ? 
                                $node->field_horario_inicio_oficial->getString() :
                                $node->field_horario_inicio_real->getString();
                $fim = $node->field_horario_fim_oficial->getString() ? 
                                $node->field_horario_fim_oficial->getString() :
                                $node->field_horario_fim_real->getString();
                if($inicio && $fim){
                    $noFolha = $node->field_colaborador->entity->field_no_folha->getString();
                    if($noFolha){
                        $this->printLine($i, $inicio,$noFolha , "E01");        
                        $i++;
                        $this->printLine($i, $node->field_data->getString() . "T12:00:00", $noFolha, "S01");        
                        $i++;
                        $this->printLine($i, $node->field_data->getString() . "T13:00:00", $noFolha, "E02");        
                        $i++;
                        $this->printLine($i, $fim, $noFolha, "S02");        
                        $i++;
                    }
                }
            }
            
        });
        // TODO: uncomment for downloading file
        $filename = "export.txt";  
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->send();

        //000000003205022017084002794044530300000000000000001E01O
    }
    
    private function printLine($i, $time, $noFolha, $code){
        printf("%09d", $i);
        print "2";
        print $this->formatDateTime($time);
        print str_pad($noFolha, 12, "0", STR_PAD_LEFT);
        print "00000000000000001".$code."O";
        print "\r\n";
        
    }
    
    public function formatDateTime($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'Y-m-d\TH:i:s';
    $finalFormat = 'dmYHi';
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }

}
