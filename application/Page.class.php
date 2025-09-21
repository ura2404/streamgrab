<?php
/**
 * @author ura@itx.ru 
 * @version 1.0 2025-07-24
 */

namespace Streamgrab;

class Page {
  private string $PageName;

  // --------------------------------------------------------------------------
  function __construct( string $PageName ){
    $this->PageName = $PageName.'.twig';
  }

  // --------------------------------------------------------------------------
  function __get($name){
    switch($name){
      case 'Html' : return $this->getMyHtml();
    }
  }

  // --------------------------------------------------------------------------
  private function getMyHtml(){
    return (new Twig())->render( $this->PageName, Camera::scan() ?? [] );
  }

  // --------------------------------------------------------------------------
  static function render( string $PageName ){
    // php-intl
    // инструменты для работы с локализованными данными, такие как 
    //   форматирование дат, чисел и валют, транслитерация, сравнение строк и определение границ текста, делая приложения независимыми от локали и культурных особенностей
    if( !extension_loaded('intl') ) throw new \Exception( 'Не установлен php-intl' );
    return ( new self($PageName) )->Html;
  }

}
?>