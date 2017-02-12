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
class PontoSeculumImportController extends ControllerBase {
  
 const DATE_POS = 0;
 const NAME_POS = 1;
 const START_POS = 2;
 const END_POS = 5;
    

  public function start() {
    $render_array = array(
      '#theme' => 'ponto_import_start',
      '#form_action' => Url::fromRoute('ponto_seculum_import_upload')
    );
   return $render_array;
  }
  
  public function upload(){
    

     $file_upload = \Drupal::request()->files->get("ponto_file", NULL, TRUE);
     
     // todo: já redirecionar para o start
     if(!$file_upload){
        return array('#markup' => 'A última importação foi um sucesso. Para recomeçar, clique no link de Importação de Ponto correspondente.');
      }
    
    $file = new \SplFileObject($file_upload->getRealPath());

    //discard 1st line
    if(!$file->eof()) $file->fgets();
   
    $count = 0;
    // Loop until we reach the end of the file.
    while (!$file->eof()) {
        // Echo one line from the file.
        $lineContent = $file->fgets();
        // checking if 10th character code is 2, so it can be imported
        if(strlen($lineContent) > 10 && $lineContent[9] == '2'){
            $this->importLine($lineContent);
            $count++;
        }
    }

    // Unset the file to call __destruct(), closing the file handle.
    $file = null;
    

    return array('#markup' => "Foram importados $count registros com sucesso.");
  }

  public function importLine($contentLine){
        // setup variables
          $data = $this->formatDate(substr($contentLine, 10, 12));
          $noFolha = intval(substr($contentLine, 23, 11));
          $horario =  $this->formatDateTime(substr($contentLine, 10, 12));
          $codigo = substr($contentLine, 51, 3);
          $field_names = array ("E01" => array("field_horario_inicio_oficial","field_horario_fim_oficial"), "S02" => array("field_horario_fim_oficial","field_horario_inicio_oficial") );
          if(!array_key_exists($codigo, $field_names)) return;
          $field_name = $field_names[$codigo][0];  
          $field_name_2 = $field_names[$codigo][1];
          
          // find colaborador
          $query = \Drupal::entityQuery('node');
          $query->condition('field_no_folha', $noFolha);
          $nids = $query->execute();
        
          $nome = "";
          
          if(empty($nids)){
            // vamos criar o colaborador
               $nome = "Falta Nome - $noFolha";
                $node = Node::create([
                'type'        => 'colaborador',
                'title'       => "Falta Nome - $noFolha",
                'field_no_folha' => $noFolha 
              ]);
            $return = $node->save();
            $query = \Drupal::entityQuery('node');
            $query->condition('field_no_folha', $noFolha);
            $nids = $query->execute();
            $ids = array_keys($nids);
          }
          else {
            $ids = array_keys($nids);
            $node_storage = \Drupal::entityManager()->getStorage('node');
            $node = $node_storage->load($ids[0]);
            $nome = $node->getTitle();
          }
          
          
          
          $query = \Drupal::entityQuery('node');
          $query->condition('field_colaborador', $ids[0])
                  ->condition('field_data', $data);
          $nids = $query->execute();

          if(empty($nids)){
            $node = Node::create([
                  'type'        => 'marcacao_ponto',
                  'title'       => $nome . " - ". $data,
                  'field_colaborador' => [
                    'target_id' => $ids[0]
                  ],
                'field_data' => array($data),
                 $field_name =>  array($horario)
                ]);
              $node->save();
          }
          else{
            $ids_marcacao = array_keys($nids);
            $node_storage = \Drupal::entityManager()->getStorage('node');
            $marcacao = $node_storage->load($ids_marcacao[0]);
            $marcacao->setTitle($nome . " - ". $data);
            $marcacao->set($field_name, $horario);
            $marcacao->save();
          }
            
  }
  
  public function formatDateTime($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'dmYHi';
    $finalFormat = DATETIME_DATETIME_STORAGE_FORMAT;
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }
 
  
    public function formatDate($date){
    
    if(!$date) return NULL;
    
    $initialFormat = 'dmYHi';
    $finalFormat = 'Y-m-d';
    $drupalDate = DrupalDateTime::createFromFormat($initialFormat, $date, drupal_get_user_timezone());
    
    $drupalDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $drupalDate->format($finalFormat);
    
  }
  
}