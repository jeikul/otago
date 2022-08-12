<?php
	include_once "../../../util/php/mysql.php" ;
	include_once "../../../util/php/log.php" ;

  function fnDailyChannel ( $asDate )
	{
	  $lsSQL = "SELECT id,fdName FROM tbRestaurant" ;
		$rsRestaurant = mysql_exec ( $lsSQL ) ;
		while ( $rowRestaurant = mysqli_fetch_assoc ( $rsRestaurant ) ) {
		  $liRestaurantID = $rowRestaurant["id"] ;
			$lsSQL = "SELECT id,fdName FROM tbChannel" ;
			$rsChannel = mysql_exec ( $lsSQL ) ;
			while ( $rowChannel = mysqli_fetch_assoc ( $rsChannel ) ) {
        $liChannelID = $rowChannel["id"] ;
				$lsSQL = "SELECT SUM(fdCount),SUM(tbOrder_Food.fdAmount)
				    FROM tbOrder_Food LEFT JOIN tbOrder ON tbOrder.id=fdOrderID
				    WHERE fdRestaurantID=$liRestaurantID
						  AND fdChannelID=$liChannelID
							AND fdDateTime>='$asDate'
							AND fdDateTime<DATE_ADD('$asDate', INTERVAL 1 DAY)" ;
				$rsCount = mysql_exec ( $lsSQL ) ;
				$rowCount = mysqli_fetch_row ( $rsCount ) ;
				$liFoodCount = $rowCount[0] ;
				$liAmount = 0 + $rowCount[1] ;
				mysqli_free_result ( $rsCount ) ;
				$lsClause = "fdRestaurantID=$liRestaurantID AND fdChannelID=$liChannelID AND fdDate='$asDate'" ;
				if ( $liFoodCount > 0 ) {
				  $objOrders = fnGetObject ( "tbOrder", "fdDateTime>='$asDate' AND fdDateTime<DATE_ADD('$asDate', INTERVAL 1 DAY) AND fdRestaurantID=$liRestaurantID AND fdChannelID=$liChannelID", "COUNT(id) AS OrderCount,SUM(fdServCount) AS ServCount" ) ;
				  $liDailyChannelID = fnGetValue ( "tbDailyChannel", $lsClause, "id" ) ;
					if ( $liDailyChannelID > 0 )
					  $lsSQL = "UPDATE tbDailyChannel SET fdOrderCount=" . $objOrders->OrderCount . ",fdServCount=" . $objOrders->ServCount . ",fdFoodCount=$liFoodCount,fdIncome=$liAmount WHERE $lsClause" ;
					else
					  $lsSQL = "INSERT INTO tbDailyChannel (fdDate,fdRestaurantID,fdChannelID,fdOrderCount,fdServCount,fdFoodCount,fdIncome) VALUES ('$asDate',$liRestaurantID,$liChannelID,$objOrders->OrderCount,$objOrders->ServCount,$liFoodCount,$liAmount)" ;
					mysql_exec ( $lsSQL ) ;
				} else {
				  $lsSQL = "DELETE FROM tbDailyChannel WHERE $lsClause" ;
					mysql_exec ( $lsSQL ) ;
				}
			}
			mysqli_data_seek ( $rsChannel, 0 ) ;
			while ( $rowChannel = mysqli_fetch_assoc ( $rsChannel ) ) {
        $liChannelID = $rowChannel["id"] ;
				$lsClause = "fdRestaurantID=$liRestaurantID AND fdChannelID=$liChannelID AND fdDate='$asDate'" ;
				$objSummary = fnGetObject ( "tbDailyChannel LEFT JOIN tbChannel ON tbChannel.id=fdChannelID", "fdRestaurantID=$liRestaurantID AND fdParentID=$liChannelID AND fdDate='$asDate'", "SUM(fdOrderCount) AS OrderCount,SUM(fdServCount) AS ServCount,SUM(fdFoodCount) AS FoodCount,SUM(fdIncome) AS Income" ) ;
			  if ( $objSummary->OrderCount > 0 ) {
				  $liDailyChannelID = fnGetValue ( "tbDailyChannel", $lsClause, "id" ) ;
					if ( $liDailyChannelID > 0 )
					  $lsSQL = "UPDATE tbDailyChannel SET fdOrderCount=fdOrderCount+" . $objSummary->OrderCount . ",fdServCount=fdServCount+" . $ojbSummary->OrderCount . ",fdFoodCount=fdFoodCount+" . $objSummary->FoodCount . ",fdIncome=fdIncome+" . $objSummary->Income . " WHERE $lsClause" ;
					else
					  $lsSQL = "INSERT INTO tbDailyChannel (fdDate,fdRestaurantID,fdChannelID,fdOrderCount,fdServCount,fdFoodCount,fdIncome) VALUES ('$asDate',$liRestaurantID,$liChannelID," . $objSummary->OrderCount . "," . $objSummary->ServCount . "," . $objSummary->FoodCount . "," . $objSummary->Income . ")" ;
					mysql_exec ( $lsSQL ) ;
				}
			}
			mysqli_free_result ( $rsChannel ) ;
		}
		mysqli_free_result ( $rsRestaurant ) ;
	}

	function fnDailyCategory ( $asDate )
	{
	  $lsSQL = "SELECT id,fdName FROM tbRestaurant" ;
		$rsRestaurant = mysql_exec ( $lsSQL ) ;
		while ( $rowRestaurant = mysqli_fetch_assoc ( $rsRestaurant ) ) {
		  $liRestaurantID = $rowRestaurant["id"] ;
			$lsSQL = "SELECT id,fdName FROM tbCategory" ;
			$rsCategory = mysql_exec ( $lsSQL ) ;
			while ( $rowCategory = mysqli_fetch_assoc ( $rsCategory ) ) {
        $liCategoryID = $rowCategory["id"] ;
				$lsSQL = "SELECT SUM(fdCount),SUM(tbOrder_Food.fdAmount)
				    FROM (tbOrder_Food LEFT JOIN tbOrder ON tbOrder.id=fdOrderID) LEFT JOIN tbCategory_Food ON tbCategory_Food.fdFoodID=tbOrder_Food.fdFoodID
				    WHERE fdRestaurantID=$liRestaurantID
						  AND fdCategoryID=$liCategoryID
							AND fdDateTime>='$asDate'
							AND fdDateTime<DATE_ADD('$asDate', INTERVAL 1 DAY)" ;
				$rsCount = mysql_exec ( $lsSQL ) ;
				$rowCount = mysqli_fetch_row ( $rsCount ) ;
				$liCount = $rowCount[0] ;
				$liAmount = 0 + $rowCount[1] ;
				mysqli_free_result ( $rsCount ) ;
				$lsClause = "fdRestaurantID=$liRestaurantID AND fdCategoryID=$liCategoryID AND fdDate='$asDate'" ;
				if ( $liCount > 0 ) {
				  $liDailyCategoryID = fnGetValue ( "tbDailyCategory", $lsClause, "id" ) ;
					if ( $liDailyCategoryID > 0 )
					  $lsSQL = "UPDATE tbDailyCategory SET fdServCount=$liCount,fdIncome=$liAmount WHERE $lsClause" ;
					else
					  $lsSQL = "INSERT INTO tbDailyCategory (fdDate,fdRestaurantID,fdCategoryID,fdServCount,fdIncome) VALUES ('$asDate',$liRestaurantID,$liCategoryID,$liCount,$liAmount)" ;
					mysql_exec ( $lsSQL ) ;
				} else {
				  $lsSQL = "DELETE FROM tbDailyCategory WHERE $lsClause" ;
					mysql_exec ( $lsSQL ) ;
				}
			}
			mysqli_free_result ( $rsCategory ) ;
		}
		mysqli_free_result ( $rsRestaurant ) ;
	}

	function fnDailyFood ( $asDate )
	{
	  $lsSQL = "SELECT id,fdName FROM tbRestaurant" ;
		$rsRestaurant = mysql_exec ( $lsSQL ) ;
		while ( $rowRestaurant = mysqli_fetch_assoc ( $rsRestaurant ) ) {
		  $liRestaurantID = $rowRestaurant["id"] ;
			$lsSQL = "SELECT id,fdName FROM tbFood /*WHERE fdProduct=1*/" ;
			$rsFood = mysql_exec ( $lsSQL ) ;
			while ( $rowFood = mysqli_fetch_assoc ( $rsFood ) ) {
        $liFoodID = $rowFood["id"] ;
				$lsSQL = "SELECT SUM(fdCount),SUM(tbOrder_Food.fdAmount)
				    FROM tbOrder_Food LEFT JOIN tbOrder ON tbOrder.id=fdOrderID
				    WHERE fdRestaurantID=$liRestaurantID
						  AND fdFoodID=$liFoodID
							AND fdDateTime>='$asDate'
							AND fdDateTime<DATE_ADD('$asDate', INTERVAL 1 DAY)" ;
				$rsCount = mysql_exec ( $lsSQL ) ;
				$rowCount = mysqli_fetch_row ( $rsCount ) ;
				$liCount = $rowCount[0] ;
				$liAmount = 0 + $rowCount[1] ;
				mysqli_free_result ( $rsCount ) ;
				$lsClause = "fdRestaurantID=$liRestaurantID AND fdFoodID=$liFoodID AND fdDate='$asDate'" ;
				if ( $liCount > 0 ) {
				  $liDailyFoodID = fnGetValue ( "tbDailyFood", $lsClause, "id" ) ;
					if ( $liDailyFoodID > 0 )
					  $lsSQL = "UPDATE tbDailyFood SET fdServCount=$liCount,fdIncome=$liAmount WHERE $lsClause" ;
					else
					  $lsSQL = "INSERT INTO tbDailyFood (fdDate,fdRestaurantID,fdFoodID,fdServCount,fdIncome) VALUES ('$asDate',$liRestaurantID,$liFoodID,$liCount,$liAmount)" ;
					mysql_exec ( $lsSQL ) ;
				} else {
				  $lsSQL = "DELETE FROM tbDailyFood WHERE $lsClause" ;
					mysql_exec ( $lsSQL ) ;
				}
			}
			mysqli_free_result ( $rsFood ) ;
		}
		mysqli_free_result ( $rsRestaurant ) ;
	}

	function fnDailySummary ( $asDate )
	{
	  $lsSQL = "SELECT id,fdName FROM tbRestaurant" ;
		$rsRestaurant = mysql_exec ( $lsSQL ) ;
		while ( $rowRestaurant = mysqli_fetch_assoc ( $rsRestaurant ) ) {
		  $liRestaurantID = $rowRestaurant["id"] ;
			$lsClause = "fdRestaurantID=$liRestaurantID AND fdDateTime>='$asDate' AND fdDateTime<DATE_ADD('$asDate', INTERVAL 1 DAY)" ;
			$lsSQL = "SELECT id,fdAmount FROM tbOrder WHERE $lsClause" ;
			$rsOrder = mysql_exec ( $lsSQL ) ;
			$liIncomeSummary = 0 ;
			$liServCount = 0 ;
			while ( $rowOrder = mysqli_fetch_assoc ( $rsOrder ) ) {
        $liOrderID = $rowOrder["id"] ;
				$liIncomeSummary += $rowOrder["fdAmount"] ;
				$liOrderServCount = 0 ;
				$liServAmount = 0 ;
				$lsSQL = "SELECT fdFoodID,fdCount,fdName FROM tbOrder_Food WHERE fdOrderID=" . $rowOrder["id"] ;
				$rsOrderFood = mysql_exec ( $lsSQL ) ;
				while ( $rowOrderFood = mysqli_fetch_assoc ( $rsOrderFood ) ) {
			    $liCategoryCount = fnGetValue ( "tbCategory_Food", "fdFoodID=" . $rowOrderFood["fdFoodID"] . " AND fdCategoryID<9", "COUNT(fdCategoryID)" ) ;
					if ( $liCategoryCount > 0 )
					  $liOrderServCount += $rowOrderFood["fdCount"] ;
					else {
			      $liCategoryCount = fnGetValue ( "tbCategory_Food", "fdFoodID=" . $rowOrderFood["fdFoodID"] . " AND fdCategoryID IN (9,10,12)", "COUNT(fdCategoryID)" ) ;
						if ( $liCategoryCount > 0 ) {
						  $lfListPrice = fnGetValue ( "tbFood", "id=" . $rowOrderFood["fdFoodID"], "fdPrice" ) ;
							if ( "$lfListPrice" == "" ) {
								print "Price missed, " . $rowOrderFood["fdName"] . "(liFoodID=" . $rowOrderFood["fdFoodID"] . ")\r\n" ;
							} else
						    $liServAmount += 0 + $lfListPrice ;
						}
					}
				}
				$lbFree = 0 ;
				if ( $liOrderServCount == 0 && $liServAmount >= 15 ) {
				  $liOrderServCount = 1 ;
					$lbFree = 1 ;
				}
				$liServCount += $liOrderServCount ;
				mysqli_free_result ( $rsOrderFood ) ;
			  $lsSQL = "UPDATE tbOrder SET fdServCount=$liOrderServCount,fdFree=$lbFree WHERE id=" . $rowOrder["id"] ;
				mysql_exec ( $lsSQL ) ;
			}
			$liOrderCount = mysqli_num_rows ( $rsOrder ) ;
			$lsClause = "fdRestaurantID=$liRestaurantID AND fdDate>='$asDate' AND fdDate<DATE_ADD('$asDate', INTERVAL 1 DAY)" ;
			$liSummaryID = fnGetValue ( "tbDailySummary", $lsClause, "id" ) ;
			if ( $liSummaryID > 0 )
			  $lsSQL = "UPDATE tbDailySummary SET fdOrderCount=$liOrderCount,fdServCount=$liServCount,fdIncome=$liIncomeSummary WHERE $lsClause" ;
			else
			  $lsSQL = "INSERT INTO tbDailySummary (fdDate,fdRestaurantID,fdOrderCount,fdServCount,fdIncome) VALUES ('$asDate',$liRestaurantID,$liOrderCount,$liServCount,$liIncomeSummary)" ;
			mysql_exec ( $lsSQL ) ;
			mysqli_free_result ( $rsOrder ) ;
		}
		mysqli_free_result ( $rsRestaurant ) ;
	}

	$argOptions = getopt ( "d:v" ) ;
	if ( array_key_exists ( "d", $argOptions ) )
	  $lsDate = Date ( "Y-m-d", strtotime ( $argOptions ["d"] ) ) ;
	else
	  $lsDate = Date ( "Y-m-d" ) ;

  mysql_init ( "localhost", "otago", "UTF8", "otago", "Otago@2022" ) ;

  fnDailyFood ( $lsDate ) ;
	fnDailyCategory ( $lsDate ) ;
	fnDailyChannel ( $lsDate ) ;
	fnDailySummary ( $lsDate ) ;
?>
