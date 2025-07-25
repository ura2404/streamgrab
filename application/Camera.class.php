<?php
/**
 * @author ura@itx.ru 
 * @version 1.0 2025-07-24
 */

namespace Streamgrab;

class Camera {
  private array  $Cameras;
  private \IntlDateFormatter $Formatter;
  
  // --------------------------------------------------------------------------
  function __construct(){
    $File = file_get_contents(CM_TOP.'/www/config.json');
    // $Json = json_encode( $File, JSON_PRETTY_PRINT           // форматирование пробелами
    //                           | JSON_UNESCAPED_SLASHES      // не экранировать '/'
    //                           | JSON_UNESCAPED_UNICODE );   // не кодировать текст
    $Json = json_decode( $File, true );
    $this->Cameras = $Json[ 'cameras' ];

    $this->Formatter = new \IntlDateFormatter(
      'ru_RU', // локаль
      \IntlDateFormatter::FULL,
      \IntlDateFormatter::NONE,
      'Europe/Moscow',
      \IntlDateFormatter::GREGORIAN,
      'd MMMM yyyy HH:mm' // формат: 21 июля 2025 12:00
    );
  }

  // --------------------------------------------------------------------------
  function __get($name){
    switch($name){
      case 'Data' : return $this->getMyData();
    }
  }

  // --------------------------------------------------------------------------
  private function getMyData(){
    foreach( $this->Cameras as $Key => $Data ){
      // $Data[ 'data' ] = $this->prepareCameraData( $Data['name'] );
      [ $_Diapason, $_Data ] = $this->prepareCameraData( $Data['name'] );
      $Data[ 'data' ]     = $_Data;
      $Data[ 'diapason' ] = $_Diapason;
      $this->Cameras[ $Key ] = $Data;
    }
    // echo '<pre>'.print_r($this->Cameras).'</pre>';
    return $this->Cameras;
  }

  // --------------------------------------------------------------------------
  private function scanCamera( string $CameraName ){
    $Path = CM_TOP.'/www/data/'.$CameraName.'/streams';

    if(file_exists($Path)){
      $Files = scandir($Path);
      $Files = array_filter( $Files, fn($Value) => $Value !== '.' && $Value != '..' && filesize( $Path.'/'.$Value ));
      usort($Files, function($a, $b) use ($Path) {
        // return filemtime($Path . '/' . $b) <=> filemtime($Path . '/' . $a); // от новых к старым
        return $b <=> $a; // от новых к старым
      });
    }
    else $Files = [];
    // echo '<pre>'.print_r($Files).'</pre>';
    return $Files;
  }

  // --------------------------------------------------------------------------
  private function prepareCameraData( string $CameraName ){
    $Data = [];
    $Diapason = [];
    // $Path = CM_TOP.'/data/'.$CameraName.'/streams';
    $CameraData = $this->scanCamera( $CameraName );

    foreach( $CameraData as $FileName ){
      if( preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $FileName, $Matches) ) {
        $RawDate = $Matches[1];
        $Dt = \DateTime::createFromFormat( 'Y-m-d_H-i-s', $RawDate );

        [ $Date, $Time ] = explode('_', $RawDate);
        [ $Year, $Month, $Day ] = explode('-', $Date);
        $Time = substr($Time, 0, 5);

        if ( !isset( $Data[$Year][$Month]) )       $Data[$Year][$Month] = [];
        if ( !isset( $Data[$Year][$Month][$Day]) ) $Data[$Year][$Month][$Day] = [];

        if (!in_array($Time, $Data[$Year][$Month][$Day])) {
          $Data[$Year][$Month][$Day][] = [ 'data/'.$CameraName.'/streams/'.$FileName, $this->Formatter->format($Dt), $Time ];
        }
      }
    }

    preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $CameraData[0],                    $M1);
    preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $CameraData[count($CameraData)-1], $M2);

    $Dt1 = \DateTime::createFromFormat( 'Y-m-d_H-i-s', $M1[0] );
    $Dt2 = \DateTime::createFromFormat( 'Y-m-d_H-i-s', $M2[0] );

    $Diapason = [ $this->Formatter->format($Dt2), $this->Formatter->format($Dt1) ];

    // var_dump( $Data );
    return [ $Diapason, $Data ];



//   if( preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $File, $Matches) ) {
//     $RawDate = $Matches[1];

// /*    $Click = "setVideo(this, '".$File  ."', this.textContent ,'video/webm'); return false;"; */
//     $Dt = DateTime::createFromFormat( 'Y-m-d_H-i-s', $RawDate );
// /*
//     echo '<div>';
// //    echo '  <a href2="'.$File.'" onclick="' .$Click. '">' .$Dt->format( 'd F Y, H:i' ). '</a>';
//     $Tag = $formatter->format($Dt);

//     echo '  <a href2="'.$File.'" onclick="' .$Click. '">' .$Tag. '</a>';
//     echo '</div>';


//   $Dt = DateTime::createFromFormat( 'Y-m-d_H-i-s', $RawDate );
//   $Catalog[]= [ $File, $formatter->format($Dt) ];

//     // -------------
//     [ $Date, $Time ] = explode('_', $RawDate);
//     [ $Year, $Month, $Day ] = explode('-', $Date);
//     $Time = substr($Time, 0, 5);

//     if ( !isset( $Calendar[$Year][$Month]) )       $Calendar[$Year][$Month] = [];
//     if ( !isset( $Calendar[$Year][$Month][$Day]) ) $Calendar[$Year][$Month][$Day] = [];

//     if (!in_array($Time, $Calendar[$Year][$Month][$Day])) {
//         $Calendar[$Year][$Month][$Day][] = $Time;
//     }
//   }
// */
  }


  // --------------------------------------------------------------------------
  static function scan(){
    return (new self)->Data;
  }

}
?>