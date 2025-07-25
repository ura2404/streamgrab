<?php
namespace Streamgrab;

require_once 'Twig/autoload.php';

/**
 * Class \Cmatrix\Web\Twig
 * 
 * @author ura@itx.ru
 * @version 1.0 2025-07-24
 */
class Twig{

  private $Twig;
  
  // ------------------------------------------------------------------------
  public function __construct(){
    $this->Twig = new \Twig\Environment($this->getMyLoader(),[
        'cache' => '/var/tmp',
        'debug' => true,
        'auto_reload' => true
    ]);
    $this->Twig->addExtension(new \Twig\Extension\DebugExtension());
  }
	
  // ------------------------------------------------------------------------
//   function __get($name){
//     switch($name){
//       default : throw new Exception\Property($this,$name);
//     }
//   }

  // ------------------------------------------------------------------------
  private function getMyLoader(){
    return new \Twig\Loader\FilesystemLoader(CM_TOP .'/www/tpl');  
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------------------------
  public function render($Template, array $Data = [] ){
    return $this->Twig->render($Template, [ 'data' => $Data ]);
  }

}
?>
