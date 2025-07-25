<?php
include '../application/common.php';

use Streamgrab\Page;

try {
  echo Page::render( 'main' );
}
catch(\Throwable $e) {
  die( 'Die: '.$e->getMessage() );
}
?>
