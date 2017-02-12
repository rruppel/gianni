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
  
 const DATE_POS = 0;
 const NAME_POS = 1;
 const START_POS = 2;
 const END_POS = 5;
    

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
        if(!$cells->Cell[self::START_POS]->Data || empty($cells->Cell[self::START_POS]->Data->__toString())){
          continue;
        }
        else if($cells->Cell[self::DATE_POS]->Data->__toString() === "DataRegistro"){
          continue;
        }
        else{
          $data = $this->formatDate($cells->Cell[self::DATE_POS]->Data->__toString());
          $nome = $cells->Cell[self::NAME_POS]->Data->__toString();
          $horario_inicio =  $this->getDateTime($cells->Cell[self::START_POS]->Data->__toString());
          $horario_fim = $this->getDateTime($cells->Cell[self::END_POS]->Data->__toString());
          
          $horario_fim_oficial = $this->getHorarioFimOficial($horario_inicio, $horario_fim);
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
          
          
          $query = \Drupal::entityQuery('node');
          $query->condition('field_colaborador', $ids[0])
                  ->condition('field_data', $data);
          $nids = $query->execute();
          
          $titulo_ponto = $nome . " - ". $data;
           if(empty($nids)){
            $node = Node::create([
                'type'        => 'marcacao_ponto',
                'title'       => $titulo_ponto,
                'field_colaborador' => [
                  'target_id' => $ids[0]
                ],
              'field_data' => array($data),
              'field_horario_inicio_real' =>  array($this->formatDateTime($horario_inicio)),
              'field_horario_fim_oficial' => array($this->formatDateTime($horario_fim_oficial)),
              'field_horario_fim_real' => array($this->formatDateTime($horario_fim)),
              
              ]);
            $node->save();
           
           }
           else{
              $ids_marcacao = array_keys($nids);
              $node_storage = \Drupal::entityManager()->getStorage('node');
              $marcacao = $node_storage->load($ids_marcacao[0]);
              $marcacao->setTitle($titulo_ponto);
              if($horario_inicio){
                $marcacao->set('field_horario_inicio_real', $this->formatDateTime($horario_inicio));
              }
              if($horario_fim){
                $marcacao->set('field_horario_fim_real', $this->formatDateTime($horario_fim));
              }
              if($horario_fim_oficial){
                $marcacao->set('field_horario_fim_oficial', $this->formatDateTime($horario_fim_oficial));
              }
              
              $marcacao->save(); 
               
           }
            $count++;
        }
    }
    

    return array('#markup' => "Foram importados $count registros com sucesso.");
  }

  public function getHorarioFimOficial($horario_inicio, $horario_fim){
     if(!$horario_inicio || !$horario_fim){ return null; }
     $format = 'd/m/Y H:i:s';
     $date_inicio =   \DateTime::createFromFormat( $format, $horario_inicio->format($format) );
     $date_fim =   \DateTime::createFromFormat( $format, $horario_fim->format($format) );
     $diff = $date_inicio->diff($date_fim, true);
     
     // o fim oficial é no máximo 11 horas para frente:
     //  8 horas de trabalho
     //  1 hora de almoço
     //  2 horas extras
     
     // exceto sabado e domingo -> onde tudo é contabilizado no ponto oficial
     // TODO: aplicar regra de sabado e domingo
     
     // TODO: tolerancia dinamica - deixar o usuario informar qtas horas serão toleradas 
     $horas = $diff->h;
    
     if($horas >= 11){
         $horario_fim_oficial = clone $date_inicio;
         $random = rand(50, 59);
         $horario_fim_oficial->add(new \DateInterval("PT10H".$random."M"));
         return DrupalDateTime::createFromFormat($format, $horario_fim_oficial->format($format), drupal_get_user_timezone());
     }
      
  }
  
  public function getDateTime($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'd/m/Y H:i:s';
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate;
  }
  
  public function formatDateTime($drupalDate){
    if(!$drupalDate) return NULL;
    $finalFormat = DATETIME_DATETIME_STORAGE_FORMAT;
    return $drupalDate->format($finalFormat);
  }
 
  
  public function formatDate($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'd/m/Y H:i:s';
    $finalFormat = 'Y-m-d';
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }
  
}