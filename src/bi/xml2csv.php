<?php
	$argOptions = getopt ( "x:" ) ;
	$lsXML_File = $argOptions ["x"] ;
	$lsXML = file_get_contents ( $lsXML_File ) ;
	$lsXML || die ( "php xml2csv -x <xml_file>\r\n" ) ;
	$xml = simplexml_load_string ( $lsXML ) ;
  foreach ( $xml->Worksheet->Table->Row as $row) {
	  $cells = $row->Cell ;
	  print $cells[5]->Data . "," . $cells[7]->Data . "," . $cells[21]->Data ;
	  print ( "\r\n" ) ;
	}
?>
