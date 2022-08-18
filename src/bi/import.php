<?php

	include_once "../../../PHPExcel/Classes/PHPExcel.php" ;
	include_once "../../../util/php/mysql.php" ;
	include_once "../../../util/php/log.php" ;

	$gsOtagoPath = "/home/jeikul/otago" ;
	$gbUseXML = false ;

  mysql_init ( "localhost", "otago", "UTF8", "otago", "Otago@2022" ) ;

	function fnImportFile ( $asRestaurant, $asChannel, $asSystem, $asDate )
	{
	  Global $gsOtagoPath ;
		Global $dbLink ;
		Global $gbUseXML ;
	  $ltDate = strtotime ( $asDate ) ;
		$lsDailyPath = "$gsOtagoPath/data/" . Date ( "Y/m/d", $ltDate ) ;
		$lsSalesFile = sprintf ( "%s_%s_", $asRestaurant, $asChannel ) . Date ( "Ymd", $ltDate ) ;
    fnLog ( "Going to import $lsDailyPath/$lsSalesFile.*, using system " . ($asSystem == "" ? "default" : $asSystem) ) ;
		$arrFiles = scandir ( $lsDailyPath ) ;
		foreach ( $arrFiles as $strFile ) {
		  if ( strncmp ( $strFile, $lsSalesFile, strlen ($lsSalesFile) ) == 0 ) {
			  fnLog ( "Going to import $strFile" ) ;
				$liRestaurantID = fnGetValue ( "tbRestaurant", "fdAbbreviate='$asRestaurant'", "id" ) ;
				$liChannelID = fnGetValue ( "tbChannel", "fdAbbreviate='$asChannel'", "id" ) ;
				$liSystemID = fnGetValue ( "tbSystem", "fdAbbreviate='$asSystem'", "id" ) ;
				if ( $gbUseXML && strstr ( "$lsDailyPath/$strFile", ".xml" ) ) {
	        $lsXML = str_replace ( "&hellip;", "...", str_replace ( "&middot;", "-", file_get_contents ( "$lsDailyPath/$strFile" ) ) ) ;
	        $objXML = simplexml_load_string ( $lsXML ) ;
				} else try {
				  $excelFileType = PHPExcel_IOFactory::identify ( "$lsDailyPath/$strFile" ) ;
					$objReader = PHPExcel_IOFactory::createReader ( $excelFileType ) ;
					if ( strcmp ( $asSystem, "mt" ) == 0 ) {
					  $objReader->setInputEncoding ( "GBK" ) ;
					}
					$objPHPExcel = $objReader->load ( "$lsDailyPath/$strFile" ) ;
				} catch (Exception $e) {
				  die ( "Error load excel: " . $e->getMessage() ) ;
				}
			  $liOrders = 0 ;
				fnLog ( "excel reading" ) ;
				if ( strcmp ( $asSystem, "mc") == 0 ) { // 美餐系统
				  $sheet = $objPHPExcel->getSheet (0) ;
					$liRows = $sheet->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $i = 2; $i <= $liRows; $i ++ ) {
					  $lsRestaurant = $sheet->getCell ("C$i")->getValue () ;
						if ( strncmp ($lsRestaurant, "OTAGO", 5) == 0 ) {
						  $liOrders ++ ;
							$lsSerial = sprintf ( "%s%02d%02d%03d", $asDate, $liRestaurantID, $liChannelID, $i - 1 ) ;
							$lsDatetime = $sheet->getCell ("B$i")->getValue () ;
							$lsFoods = $sheet->getCell ("D$i")->getValue () ;
							$lsAmount = $sheet->getCell ("F$i")->getValue () ;
							$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
							if ( $liOrderID > 0 )
							  $lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
							else
							  $lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
							mysql_exec ( $lsSQL ) ;
							if ( $liOrderID == 0 )
							  $liOrderID = mysqli_insert_id ( $dbLink ) ;
							fnLog ( "liOrderID=$liOrderID,$lsFoods" ) ;
							$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
							mysql_exec ( $lsSQL ) ;
							$lsFoodSeg = strstr ( $lsFoods, "」", true ) ;
							while ( $lsFoodSeg != false ) {
							  // print ( "lsFoodSeg=$lsFoodSeg<-\r\n" ) ;
							  $liPos = mb_strpos ( $lsFoodSeg, "," ) ;
								$liCount = intval ( mb_substr ($lsFoodSeg, $liPos + 4) ) ;
                $liPos2 = mb_strpos ( $lsFoodSeg, ",", $liPos + 1 ) ;
								sscanf ( mb_substr ( $lsFoodSeg, $liPos2 + 5), "%f", $lfPrice ) ;
								$lsFood = mb_substr ( $lsFoodSeg, 4, $liPos - 4 ) ;
							  $liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
								if ( $liFoodID == 0 )
								  $liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
								if ( $liFoodID == 0 )
								  $liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
								if ( $liFoodID == 0 )
								  $liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
								if ( $liFoodID > 0 ) {
								  // print ( "lsFood=$lsFood,liCount=$liCount,lfAmount=$lfAmount\r\n" ) ;
									$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdName,fdAmount) VALUES ($liOrderID,$liFoodID,$liCount,'$lsFood',$lfPrice*$liCount)" ;
									mysqli_query ( $dbLink, $lsSQL ) ;
									if ( mysqli_errno ( $dbLink ) == 1062 ) {
									  $lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lfPrice*$liCount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
										mysql_exec ( $lsSQL ) ;
									}
									fnLog ($lsSQL ) ;
								  // $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
									// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
								} else {
								  print ( "Unknown food $lsFood\r\n" ) ;
								}
								$lsFoods = mb_substr ( $lsFoods, mb_strlen ( $lsFoodSeg ) + 2 ) ;
							  $lsFoodSeg = strstr ( $lsFoods, "」", true ) ;
							} // while
						}
					} // for i
					fnLog ( "$liOrders orders found" ) ;
				} else if ( strcmp ( $asSystem, "3n" ) == 0 ) { // 3N分销商城
				  $sheet = $objPHPExcel->getSheet (0) ;
					$liRows = $sheet->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $j = $i = 2; $i <= $liRows; ) {
					  fnLog ( "Processing line $i" ) ;
						$liOrders ++ ;
						$lsSerial = $sheet->getCell ("A$i")->getValue () ;
						$lsDatetime = $sheet->getCell ("AA$i")->getValue () ;
						$lsAmount = $sheet->getCell ("S$i")->getValue () ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						do {
						  $lsFood = $sheet->getCell ("C$j")->getValue () ;
							fnLog ( "1.lsFood=$lsFood" ) ;
							$liCount = $sheet->getCell ( "F$j" )->getValue () ;
							$lsAmount = $sheet->getCell ( "M$j" )->getValue () ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
								//print "ready $lsFood\r\n" ;
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lsAmount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lsAmount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$lsFood = strtok ( "," ) ;
							$j ++ ;
							if ( $sheet->getCell ("A$j")->getValue () != "" )
							  break ; // next order
						} while ( $j <= $liRows ) ;
						$i = $j ;
					} // for i
					print "$liOrders orders found\r\n" ;
				} else if ( strcmp ( $asSystem, "mt") == 0 ) { // 美团外卖
				  $sheet = $objPHPExcel->getSheet (0) ;
					$liRows = $sheet->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $i = 2; $i <= $liRows; $i ++ ) {
						$liOrders ++ ;
						$lsSerial = mb_substr ( $sheet->getCell ("B$i")->getValue (), 3 ) ;
						$lsDatetime = $sheet->getCell ("G$i")->getValue () ;
						$lsFoods = $sheet->getCell ("M$i")->getValue () ;
						$lsAmount = $sheet->getCell ("R$i")->getValue () ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						fnLog ( "liOrderID=$liOrderID,$lsFoods" ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						$lsFood = strtok ( $lsFoods, "/" ) ;
						while ( $lsFood != false ) {
							fnLog ( "1.lsFood=$lsFood" ) ;
							$liPos = mb_strpos ( $lsFood, "*" ) ;
							$liCount = intval ( mb_substr ( $lsFood, $liPos + 3 ) ) ;
							$liPos = mb_strrpos ( $lsFood, "," ) ;
							$lfPrice = floatval ( mb_substr ( $lsFood, $liPos + 3 ) ) ;
							fnLog ( "2.liCount=$liCount, lfPrice=$lfPrice" ) ;
							$liPos = mb_strpos ( $lsFood, "(" ) ;
							$lsFood = mb_substr ( $lsFood, 0, $liPos ) ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
								//print "ready $lsFood\r\n" ;
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lfPrice*$liCount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lfPrice*$liCount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$lsFood = strtok ( "/" ) ;
						}
					} // for i
					print "$liOrders orders found\r\n" ;
				} else if ( strcmp ( $asSystem, "ele") == 0 ) { // 饿了么外卖
				  $sheet = $objPHPExcel->getSheet (0) ;
					$liRows = $sheet->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $i = 2; $i <= $liRows; $i ++ ) {
						$liOrders ++ ;
						$lsSerial = $sheet->getCell ("E$i")->getValue () ;
						$lsDatetime = $sheet->getCell ("F$i")->getValue () ;
						$lsFoods = $sheet->getCell ("S$i")->getValue () ;
						$lsAmount = $sheet->getCell ("V$i")->getValue () ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						fnLog ( "liOrderID=$liOrderID,$lsFoods" ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						$lsFood = strtok ( $lsFoods, "+" ) ;
						while ( $lsFood != false ) {
							fnLog ( "1.lsFood=$lsFood" ) ;
							$liPos = mb_strpos ( $lsFood, "_" ) ;
							$liCount = intval ( mb_substr ( $lsFood, $liPos + 1 ) ) ;
							$liPos = mb_strrpos ( $lsFood, "*" ) ;
							$lfPrice = floatval ( mb_substr ( $lsFood, $liPos + 1 ) ) ;
							fnLog ( "2.liCount=$liCount, lfPrice=$lfPrice" ) ;
							$liPos = mb_strpos ( $lsFood, "_" ) ;
							$lsFood = mb_substr ( $lsFood, 0, $liPos ) ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
								//print "ready $lsFood\r\n" ;
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lfPrice*$liCount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lfPrice*$liCount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$lsFood = strtok ( "+" ) ;
						}
					} // for i
					print "$liOrders orders found\r\n" ;
				} else if ( strcmp ( $asSystem, "mtpos" ) == 0 ) { // 美团POS
				  $sheet = $objPHPExcel->getSheet (0) ;
				  $sheetDetail = $objPHPExcel->getSheet (1) ;
					$liRows = $sheet->getHighestRow () ;
					$liRowsDetail = $sheetDetail->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $j = $i = 4; $i <= $liRows - 1; $i ++ ) {
					  fnLog ( "Processing line $i" ) ;
						$liOrders ++ ;
						$lsSerial = $sheet->getCell ("A$i")->getValue () ;
						$lsDatetime = $sheet->getCell ("F$i")->getValue () ;
						$lsAmount = $sheet->getCell ("O$i")->getValue () ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						do {
						  $lsFood = $sheetDetail->getCell ("D$j")->getValue () ;
						  $lsAttachs = $sheetDetail->getCell ("H$j")->getValue () ;
							$arrAttachs = preg_split ( "/,/", $lsAttachs ) ;
							fnLog ( "1.j=$j,lsFood=$lsFood,$lsAttachs" ) ;
							$liCount = $sheetDetail->getCell ( "J$j" )->getValue () ;
							$lfAmount = $sheetDetail->getCell ( "N$j" )->getValue () ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
							  foreach ( $arrAttachs as $lsAttach ) {
									$liAttachID = fnGetValue ( "tbFood", "fdName='$lsAttach'", "id" ) ;
									if ( $liAttachID == 0 )
										$liAttachID = fnGetValue ( "tbAlias", "fdName='$lsAttach'", "fdFoodID" ) ;
									if ( $liAttachID == 0 )
										$liAttachID = fnGetValue ( "tbFood", "INSTR('$lsAttach',fdName)>0", "id" ) ;
									if ( $liAttachID == 0 )
										$liAttachID = fnGetValue ( "tbAlias", "INSTR('$lsAttach',fdName)>0", "fdFoodID" ) ;
									if ( $liAttachID > 0 ) {
									  $lfAttachPrice = fnGetValue ( "tbFood", "id=$liAttachID", "fdAttachPrice" ) ;
										if ( empty ( $lfAttachPrice ) )
										  $lfAttachPrice = 0 ;
										$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liAttachID,$liCount,$lfAttachPrice*$liCount,'$lsAttach')" ;
										$lfAmount -= $lfAttachPrice * $liCount ;
								    mysqli_query ( $dbLink, $lsSQL ) ;
										if ( mysqli_errno ( $dbLink ) == 1062 ) {
											$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lfAttachPrice*$liCount,fdName='$lsAttach' WHERE fdOrderID=$liOrderID AND fdFoodID=$liAttachID" ;
											mysql_exec ( $lsSQL ) ;
										}
								    fnLog ($lsSQL ) ;
									} else if ( strcmp ( $lsAttach, "--" ) ) {
								    print ( "Unknown attach $lsAttach\r\n" ) ;
									}
								}
								//print "ready $lsFood\r\n" ;
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lfAmount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lfAmount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$j ++ ;
							if ( $sheetDetail->getCell ("A$j")->getValue () != $lsSerial )
							  break ; // next order
						} while ( $j <= $liRowsDetail ) ;
					} // for i
					print "$liOrders orders found\r\n" ;
				} else if ( strcmp ( $asSystem, "fs") == 0 ) { // 丰食团餐
				  $sheet = $objPHPExcel->getSheet (0) ;
					$liRows = $sheet->getHighestRow () ;
					print "$liRows rows found.\r\n" ;
					for ( $i = 2; $i <= $liRows; $i ++ ) {
						$liOrders ++ ;
						$lsSerial = $sheet->getCell ("B$i")->getValue () ;
						$lsDatetime = $sheet->getCell ("AB$i")->getValue () ;
						$lsFoods = $sheet->getCell ("D$i")->getValue () ;
						$lsAmount = $sheet->getCell ("H$i")->getValue () ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						fnLog ( "liOrderID=$liOrderID,$lsFoods" ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						$lsFood = strtok ( $lsFoods, "," ) ;
						while ( $lsFood != false ) {
							fnLog ( "1.lsFood=$lsFood" ) ;
							$liCount = $sheet->getCell ("F$i")->getValue () ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
								//print "ready $lsFood\r\n" ;
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lsAmount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lsAmount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$lsFood = strtok ( "," ) ;
						}
					} // for i
					print "$liOrders orders found\r\n" ;
				} else if ( strcmp ( $asSystem, "myt") == 0 ) { // 麦芽田
				  if ( $gbUseXML ) {
						$rows = $objXML->Worksheet->Table->Row ;
						$liRows = count ( $rows ) ;
					} else {
						$sheet = $objPHPExcel->getSheet (0) ;
						$liRows = $sheet->getHighestRow () ;
					}
					print "$liRows rows found.\r\n" ;
					for ( $i = 2; $i <= $liRows; $i ++ ) {
						$liOrders ++ ;
						if ( $gbUseXML ) {
							$cells = $rows[$i-1]->Cell ;
							$lsSerial = substr ( $cells[5]->Data, 1 ) ;
							$lsDatetime = $cells[7]->Data ;
							$lsFoods = $cells[21]->Data ;
							$lsAmount = $cells[19]->Data ;
							$lsRestaurant = $cells[24]->Data ;
							$lsChannel = $cells[1]->Data ;
						} else {
							$lsSerial = substr ( $sheet->getCell ("F$i")->getValue (), 1 ) ;
							$lsDatetime = $sheet->getCell ("H$i")->getValue () ;
							$lsFoods = $sheet->getCell ("V$i")->getValue () ;
							$lsAmount = $sheet->getCell ("T$i")->getValue () ;
				      $lsRestaurant = $sheet->getCell ("Y$i")->getValue () ;
							$lsChannel = $sheet->getCell ("B$i")->getValue () ;
						}
				    $liRestaurantID = fnGetValue ( "tbRestaurant", "fdAbbreviate='$lsRestaurant'", "id" ) ;
				    $liChannelID = fnGetValue ( "tbChannel", "fdAbbreviate='" . (strcmp ($lsChannel, "美团") == 0 ? "mt" : "ele") . "'", "id" ) ;
						$liOrderID = fnGetValue ( "tbOrder", "fdSerial='$lsSerial'", "id" ) ;
						if ( $liOrderID > 0 )
							$lsSQL = "UPDATE tbOrder SET fdDatetime='$lsDatetime',fdRestaurantID=$liRestaurantID,fdChannelID=$liChannelID,fdSystemID=$liSystemID,fdAmount=$lsAmount WHERE fdSerial='$lsSerial'" ;
						else
							$lsSQL = "INSERT INTO tbOrder (fdSerial,fdDatetime,fdRestaurantID,fdChannelID,fdSystemID,fdAmount ) VALUES ('$lsSerial','$lsDatetime',$liRestaurantID,$liChannelID,$liSystemID,$lsAmount)" ;
						mysql_exec ( $lsSQL ) ;
						if ( $liOrderID == 0 )
							$liOrderID = mysqli_insert_id ( $dbLink ) ;
						fnLog ( "liOrderID=$liOrderID,$lsFoods" ) ;
						$lsSQL = "DELETE FROM tbOrder_Food WHERE fdOrderID=$liOrderID" ;
						mysql_exec ( $lsSQL ) ;
						// print "lsFoods=$lsFoods\r\n" ; 
						$lbMultiFoods = $lbQuoted = false ;
						for ( $j = 0; $j < mb_strlen ( $lsFoods ); $j ++ ) {
						  $chr = mb_substr ( $lsFoods, $j, 1 ) ;
						  if ( strcmp ( $chr, '[' ) == 0 )
							  $lbQuoted = true ;
							else if ( strcmp ( $chr, ']' ) == 0 )
							  $lbQuoted = false ;
							else if ( strcmp ( $chr, "+" ) == 0 ) {
							  if ( $lbQuoted )
							    $lsFoods = mb_substr ( $lsFoods, 0, $j ) . "&" . mb_substr ( $lsFoods, $j + 1 ) ;
								else
								  $lbMultiFoods = true ;
							}
						}
						$lfOriginAmount = 0 ;
						$arrFoodIDs = array () ;
						$arrPrices = array () ;
						$lsFood = strtok ( $lsFoods, "+" ) ;
						while ( $lsFood != false ) {
						  $liPos = mb_strpos ( $lsFood, '[' ) ;
							if ( $liPos === false )
						    $liPos = mb_strpos ( $lsFood, '|' ) ;
							if ( $liPos > 0 )
							  $lsFood = trim ( mb_substr ( $lsFood, 0, $liPos ) ) ;
							if ( $gbUseXML )
							  $liCount = $lbMultiFoods ? 1 : $cells[22]->Data ;
							else
							  $liCount = $lbMultiFoods ? 1 : $sheet->getCell ( "W$i")->getValue () ;
							$liFoodID = fnGetValue ( "tbFood", "fdName='$lsFood'", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "fdName='$lsFood'", "fdFoodID" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbFood", "INSTR('$lsFood',fdName)>0", "id" ) ;
							if ( $liFoodID == 0 )
								$liFoodID = fnGetValue ( "tbAlias", "INSTR('$lsFood',fdName)>0", "fdFoodID" ) ;
							if ( $liFoodID > 0 ) {
						    if ( $lbMultiFoods ) {
									$lfPrice = fnGetValue ( "tbPrice", "fdRestaurantID=$liRestaurantID AND fdChannelID=$liChannelID AND fdFoodID=$liFoodID", "fdPrice" ) ;
									if ( is_null ( $lfPrice ) ) {
										$lfPrice = fnGetValue ( "tbRestaurant_Food", "fdRestaurantID=$liRestaurantID AND fdFoodID=$liFoodID", "fdPrice" ) ;
										if ( is_null ( $lfPrice ) )
										  $lfPrice = 0 ;
									}
									if ( $lfPrice == 0 )
										$lfPrice = 0 + fnGetValue ( "tbFood", "id=$liFoodID", "fdPrice" ) ;
									if ( $lfPrice == 0 )
										print "Price missed, $lsFood(liFoodID=$liFoodID),$lsRestaurant,$lsChannel\r\n" ;
									else {
									  $lfOriginAmount += $lfPrice ;
								    $arrFoodIDs[] = $liFoodID ;
										$arrPrices[] = $lfPrice ;
									}
								}
								$lsSQL = "INSERT INTO tbOrder_Food (fdOrderID,fdFoodID,fdCount,fdAmount,fdName) VALUES ($liOrderID,$liFoodID,$liCount,$lsAmount,'$lsFood')" ;
								mysqli_query ( $dbLink, $lsSQL ) ;
								if ( mysqli_errno ( $dbLink ) == 1062 ) {
									$lsSQL = "UPDATE tbOrder_Food SET fdCount=fdCount+$liCount,fdAmount=fdAmount+$lsAmount WHERE fdOrderID=$liOrderID AND fdFoodID=$liFoodID" ;
									mysql_exec ( $lsSQL ) ;
								}
								fnLog ($lsSQL ) ;
								// $lsFoodMatched = fnGetValue ( "tbFood", "id=$liFoodID", "fdName" ) ;
								// print ( "Matched $lsFood as $lsFoodMatched\r\n" ) ;
							} else {
								print ( "Unknown food $lsFood\r\n" ) ;
							}
							$lsFood = strtok ( "+" ) ;
						}
						if ( $lbMultiFoods ) {
						  for ( $j = 0; $j < count ($arrFoodIDs); $j ++ ) {
							  $lsSQL = "UPDATE tbOrder_Food SET fdAmount=" . ($arrPrices[$j] / $lfOriginAmount * $lsAmount) . " WHERE fdOrderID=$liOrderID AND fdFoodID=" . $arrFoodIDs[$j] ;
								mysql_exec ( $lsSQL ) ;
							}
						}
					} // for i
					print "$liOrders orders found\r\n" ;
				} else {
				  print "Unknown system $asSystem\r\n" ;
				}
			} else {
			  fnLog ( "Skipped $strFile" ) ;
			}
		}
	}

  $lsRestaurant = $lsChannel = $lsSystem = "" ;
	$argOptions = getopt ( "c:d:r:s:v" ) ;
	if ( array_key_exists ( "v", $argOptions ) ) {
		print "Usage: php import.php -r <restaurant> -c <channel> -s <system> [-d <yyyy-mm-dd>]\r\nExample: php import.php -r tiande -c hall -s mc -d 20220801\r\n" ;
		exit ;
	} else {
	  if ( array_key_exists ( "r", $argOptions ) )
			$lsRestaurant = $argOptions ["r"] ; 
		if ( array_key_exists ( "c", $argOptions ) )
			$lsChannel = $argOptions ["c"] ;
		if ( array_key_exists ( "s", $argOptions ) )
			$lsSystem = $argOptions ["s"] ;
	}
	if ( array_key_exists ( "d", $argOptions ) )
	  $lsDate = Date ( "Y-m-d", strtotime ( $argOptions ["d"] ) ) ;
	else
	  $lsDate = Date ( "Y-m-d" ) ;

  if ( strcmp ( $lsSystem, "myt" ) == 0 )
	  fnImportFile ( "", "", "myt", $lsDate ) ;
  else if ( $lsRestaurant != "" ) {
	  if ( $lsChannel != ""  ) {
		  fnImportFile ( $lsRestaurant, $lsChannel, $lsSystem, $lsDate ) ;
		} else {
		  print "Going to import restaurant $lsRestaurant from all channels at $lsDate\r\n" ;
		} 
	} else {
	  if ( $lsChannel != "" ) {
		  print "Going to import all restaurants from channel $lsChannel at $lsDate\r\n" ;
		} else {
		  print "Going to import all restaurants from all channels at $lsDate\r\n" ;
		} 
	}
?>
