<?php

/**
 * @file
 * Contains \Drupal\ponto_import\Controller\PontoImportController.
 */

namespace Drupal\ponto_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use \Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for ponto import routes.
 */
class PontoImportController extends ControllerBase {
  
 

  public function start() {
    $render_array = array(
      '#theme' => 'ponto_import_start',
      '#form_action' => Url::fromRoute('ponto_import_upload')
    );
   return $render_array;
  }
  
  public function upload(){
    

     $file_upload = \Drupal::request()->files->get("ponto_file", NULL, TRUE);
     
     // todo: já redirecionar para o start
     if(!$file_upload){
        return array('#markup' => 'A última importação foi um sucesso. Para recomeçar, clique em Importação de Ponto.');
      }

    $contents= file_get_contents($file_upload->getRealPath());
    
    $contents = str_replace('xmlns="urn:schemas-microsoft-com:office:spreadsheet"', '', $contents);
    
    $xml = simplexml_load_string($contents);
    
    $sheet = $xml->xpath("Worksheet/Table/Row");
    $rows = $sheet;
    
  //  $date = new \DateTime('2000-01-01');
  //  echo $date->format('Y-m-d\TH:i:s');die;
    $count = 0;
    foreach($rows as $cells){
        if(empty($cells->Cell[2]->Data->__toString())){
          continue;
        }
        else if($cells->Cell[0]->Data->__toString() === "DataRegistro"){
          continue;
        }
        else{
          $data = $this->formatDate($cells->Cell[0]->Data->__toString());
          $nome = $cells->Cell[1]->Data->__toString();
          $horario_inicio =  $this->formatDateTime($cells->Cell[2]->Data->__toString());
          $horario_pausa =  $this->formatDateTime($cells->Cell[3]->Data->__toString());
          $horario_retorno = $this->formatDateTime($cells->Cell[4]->Data->__toString());
          $horario_fim = $this->formatDateTime($cells->Cell[5]->Data->__toString());
          
          
            // Use the factory to create a query object for node entities.
          // TODO: mudar para injeção de dependencias
          $query = \Drupal::entityQuery('node');
          // Add a filter (published).
          $query->condition('title', $nome);
          // Run the query.
          $nids = $query->execute();
          
          if(empty($nids)){
            // vamos criar o colaborador
                $node = Node::create([
                'type'        => 'colaborador',
                'title'       => $nome,
              ]);
              $return = $node->save();
              
            $query = \Drupal::entityQuery('node');
            // Add a filter (published).
            $query->condition('title', $nome);
            // Run the query.
             $nids = $query->execute();
          }
          
          $ids = array_keys($nids);
          
            $node = Node::create([
                'type'        => 'marcacao_ponto',
                'title'       => $nome . " - ". $data,
                'field_colaborador' => [
                  'target_id' => $ids[0]
                ],
              'field_data' => array('2016-03-31'),
              'field_horario_inicio' =>  array($horario_inicio),
              'field_horario_pausa_descanso' => array($horario_pausa),
              'field_horario_retorno_descanso' => array($horario_retorno),
              'field_horario_fim' => array($horario_fim),
              
              ]);
            $node->save();
            $count++;
            //'field_data' => array("2000-01-30")
            
        }
    }
    

    return array('#markup' => "Foram importados $count registros com sucesso.");
  }

  
  public function formatDateTime($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'd/m/Y H:i:s';
    $finalFormat = DATETIME_DATETIME_STORAGE_FORMAT;
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }
 
  
    public function formatDate($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'd/m/Y H:i:s';
    $finalFormat = 'd/m/Y';
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }
  
}