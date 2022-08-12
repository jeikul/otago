<?php
  // require "../settings.php" ;

  date_default_timezone_set ( "PRC" ) ;

  function fnMakeDir ( $path ) {
    if ( ! file_exists ( $path ) ) {
      mkdir ( $path, 0750, true ) ;
    }
  }

  fnMakeDir ( "../data/" . date ( "Y/m/d" )  ) ;
?>
