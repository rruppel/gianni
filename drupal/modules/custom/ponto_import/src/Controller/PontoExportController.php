<?php

/**
 * @file
 * Contains \Drupal\ponto_import\Controller\PontoExportController.
 */

namespace Drupal\ponto_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;


/**
 * Controller routines for ponto import routes.
 */
class PontoExportController extends ControllerBase {
  
 

  public function start() {
    $render_array = array(
      '#theme' => 'ponto_export_start',
      '#form_action' => Url::fromRoute('ponto_export_download')
    );
   return $render_array;
  }
  
  public function download(Request $request){
    $date = $request->request->get('ponto_export_date');
    
    
    // pegar todas as marcações do dia
    
    
    // gerar, da seguinte forma:
    
    //18/07/2016 09:32 0001001 E
    
    
    
    
    return array('#markup' => "Recebi $date no request.");
  }

  
  
}