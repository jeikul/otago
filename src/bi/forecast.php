<?php
	include_once "../../../util/php/mysql.php" ;
	include_once "../../../util/php/log.php" ;

	$giReferDays = 14 ;
	$gfMondayExtra = 0.1 ;
	$gfFridayReduce = 0.1 ;
	$gfHolidayExtra = 0.15 ;
  $gfHolidayReduce = 0.15 ;
	$giNormalWeight = 2 ;
	$giYesterdayWeight = 1 ;
	$giWeekDayWeight = 4 ;

  /* 预测算法: 
	   1. 过去$giReferDays天
		 2. 同一星期X权重按4倍计算，昨天权重按1倍计算，其余日子权重按2倍计算
		 3. 工作日预测忽略周末日历史记录，周末日预测忽略工作日历史记录
		 4. 周一加量10%，周末减量10%
		 5. 长假前一天减量15%, 长假后一天加量15%
	*/
	function fnPredictFood ( $asRestaurant, $asDate )
	{
	  GLOBAL $dbLink ;
	  GLOBAL $giReferDays ;
		GLOBAL $gfMondayExtra, $gfFridayReduce ;
		GLOBAL $gfHolidayExtra, $gfHolidayReduce ;
		GLOBAL $giNormalWeight, $giYesterdayWeight, $giWeekDayWeight ;
		
	  print "Predicting $asRestaurant at $asDate\r\n" ;
		$liRestaurantID = fnGetValue ( "tbRestaurant", "fdAbbreviate='$asRestaurant'", "id" ) ;
	  $liTargetWeekDay = DATE ( 'w', strtotime ( $asDate ) ) ;
		$objWorkDay = fnGetObject ( "tbWorkDay", "fdDate='$asDate'", "fdWork" ) ;
    $lsLastDate = DATE ( "Y-m-d", strtotime ( $asDate ) - 24 * 3600 ) ;
		$objLastDate = fnGetObject ( "tbWorkDay", "fdDate='$lsLastDate'", "fdWork" ) ;
		$lsNextDate = Date ( "Y-m-d", strtotime ( $asDate ) + 24 * 3600 ) ;
	  $objNextDate = fnGetObject ( "tbWorkDay", "fdDate='$lsNextDate'", "fdWork" ) ;
		$lsFridayDate = Date ( "Y-m-d", strtotime ( $asDate ) - 72 * 3600 ) ;
		$objFridayDate = fnGetObject ( "tbWorkDay", "fdDate='$lsFridayDate'", "fdWork" ) ;
		$lsMondayDate = Date ( "Y-m-d", strtotime ( $asDate ) + 72 * 3600 ) ;
		$objMondayDate = fnGetObject ( "tbWorkDay", "fdDate='$lsMondayDate'", "fdWork" ) ;
		// 食材的计划用量是依据配方表叠加的，需要先清零
		$lsSQL = "UPDATE tbDailyFood SET fdPlanCount=0 WHERE fdRestaurantID=$liRestaurantID AND fdDate='$asDate'" ;
		mysql_exec ( $lsSQL ) ;
		// 先根据算法规则1-5逐个预测成品的销量
		$lsSQL = "SELECT id,fdName FROM tbFood WHERE fdProduct>0" ;
		$rsFood = mysql_exec ( $lsSQL ) ;
		while ( $rowFood = mysqli_fetch_assoc ( $rsFood ) ) {
			print "Predicting " . $rowFood["fdName"] . " for $asRestaurant at $asDate ... " ;
			/* 下一句执行算法规则1 */
			$lsSQL = "SELECT fdDate,fdServCount FROM tbDailyFood WHERE fdFoodID=" . $rowFood["id"] . " AND fdDate<'$asDate' AND fdDate>=DATE_ADD('$asDate',INTERVAL -$giReferDays DAY) AND fdRestaurantID=$liRestaurantID" ;
			$liHistoryCount = $liReferDays = 0 ;
			$rsDailyFood = mysql_exec ( $lsSQL ) ;
			while ( $rowDailyFood = mysqli_fetch_assoc ( $rsDailyFood ) ) {
				$liSampleWeekDay = DATE ( 'w', strtotime ( $rowDailyFood["fdDate"] ) ) ;
				if ( $liTargetWeekDay > 0 && $liTargetWeekDay < 6 && $liSampleWeekDay > 0 && $liSampleWeekDay < 6
						|| ( $liTargetWeekDay < 1 || $liTargetWeekDay > 5 ) && ( $liSampleWeekDay < 1 || $liSampleWeekDay > 5 ) ) { // 算法规则3
					// 算法规则2
					// 先计算$rowDailyFood["fdDate"]这一天的权重 ; 						
					if ( $liTargetWeekDay == $liSampleWeekDay )
						$liWeight = $giWeekDayWeight ;
					else if ( strcmp ( $rowDailyFood["fdDate"], $lsLastDate ) == 0 )
						$liWeight = $giYesterdayWeight ;
					else
						$liWeight = $giNormalWeight ;
					$liReferDays += $liWeight ;
					$liHistoryCount += $rowDailyFood["fdServCount"] * $liWeight ;
				}
			}
			mysqli_free_result ( $rsDailyFood ) ;
			if ( $liReferDays > 0 ) {
				$lfPlanCount = $liHistoryCount / $liReferDays ;
				print "as $lfPlanCount\r\n" ;
				// 算法规则4
				if ( $liTargetWeekDay == 1 ) { // 要预测的那天是星期一
					if ( !is_null ( $objMondayDate ) && $objMondayDate->fdWork == 0 ) {
						$lfPlanCount *= ( 1 + $gfHolidayExtra ) ;
						print "Holiday extra " . $gfHolidayExtra * 100 . "%\r\n" ;
					} else {
						$lfPlanCount *= ( 1 + $gfMondayExtra ) ;
						print "Monday extra " . $gfMondayExtra * 100 . "%\r\n" ;
					}
				} else if ( $liTargetWeekDay == 5 ) { // 要预测的那天是星期五
					if ( is_null ( $objNextDate ) || $objNextDate->fdWork == 0 ) {
						if ( !is_null ( $objMondayDate ) && $objMondayDate->fdWork == 0 ) {
							$lfPlanCount *= ( 1 - $gfHolidayReduce ) ;
							print "Holiday reduce " . $gfHolidayReduce * 100 . "%\r\n" ;
						} else {
							$lfPlanCount *= ( 1 - $gfFridayReduce ) ;
							print "Friday reduce " . $gfFridayReduce * 100 . "%\r\n" ;
						}
					}
				}
				// 算法规则5
				if ( ! is_null ( $objWorkDay ) ) {
					if ( $objWorkDay->fdWork == 0 )
						$lfPlanCount = 0 ; // 工作日变休假
					else {
						// 长假补班日子不需要作特别处理
					}
				} else if ( $liTargetWeekDay == 0 || $liTargetWeekDay == 6 ) {
					$lfPlanCount = 0 ; // 正常休息日
				} else {
					if ( !is_null ( $objNextDate ) && $objNextDate.fdWork == 0 ) { // $asDate命中tbWorkDay前一天
						$lfPlanCount *= ( 1 - $gfHolidayReduce ) ; // 长假前一天减量15%
						print "Holiday reduce " . $gfHolidayReduce * 100 . "%\r\n" ;
					} else {
						if ( !is_null ( $objLastDate ) && $objLastDate.fdWork == 0 ) { // $asDate命中tbWorkDay后一天
							$lfPlanCount *= ( 1 + $gfHolidayExtra ) ; // 长假后一天增量15%
							print "Holiday extra " . $gfHolidayExtra * 100 . "%\r\n" ;
						}
					}
				}
				/* 平均算法
				$lfPlanCount = 0 + fnGetValue ( "tbDailyFood", "fdRestaurantID=$liRestaurantID AND fdFoodID=" . $rowFood["id"] . " AND fdDate<'$asDate' AND fdDate>=DATE_ADD('$asDate', INTERVAL -$giReferDays DAY)", "AVG(fdServCount)" ) ;
				*/
			} else
				$lfPlanCount = 0 ;
			$objDailyFood = fnGetObject ( "tbDailyFood", "fdRestaurantID=$liRestaurantID AND fdFoodID=" . $rowFood["id"] . " AND fdDate='$asDate'", "id" ) ;
			if ( is_null ( $objDailyFood ) && $lfPlanCount > 0 )
				$lsSQL = "INSERT INTO tbDailyFood (fdDate,fdFoodID,fdRestaurantID,fdPlanCount) VALUES ('$asDate'," . $rowFood["id"] . ",$liRestaurantID,$lfPlanCount)" ;
			else
				$lsSQL = "UPDATE tbDailyFood SET fdPlanCount=$lfPlanCount+0 WHERE fdRestaurantID=$liRestaurantID AND fdFoodID=" . $rowFood["id"] . " AND fdDate='$asDate'" ;
			mysql_exec ( $lsSQL ) ;
			print "result $lfPlanCount\r\n" ;
		}
		// 再根据配方表叠加计算每项非成品食材的消耗
		$lsSQL = "SELECT tbFood.id,tbFood.fdName FROM tbFormula LEFT JOIN tbFood ON tbFood.id=tbFormula.fdRawFoodID" ;
		$rsFood = mysql_exec ( $lsSQL ) ;
		while ( $rowFood = mysqli_fetch_assoc ( $rsFood ) ) {
		}
		mysqli_free_result ( $rsFood ) ;
	}

	function fnPredictCategory ( $asRestaurant, $asDate )
	{
		$liRestaurantID = fnGetValue ( "tbRestaurant", "fdAbbreviate='$asRestaurant'", "id" ) ;
		$lsSQL = "SELECT id,fdName FROM tbCategory" ;
		$rsCategory = mysql_exec ( $lsSQL ) ;
		while ( $rowCategory = mysqli_fetch_assoc ( $rsCategory ) ) {
		  print "Predicting " . $rowCategory["fdName"] . " for $asRestaurant at $asDate ... " ;
			$lfPlanCount = fnGetValue ( "tbDailyFood", "fdDate='$asDate' AND fdRestaurantID=$liRestaurantID AND fdFoodID IN (SELECT fdFoodID FROM tbCategory_Food WHERE fdCategoryID=" . $rowCategory["id"] . ")", "SUM(fdPlanCount)" ) ;
			$objDailyCategory = fnGetObject ( "tbDailyCategory", "fdDate='$asDate' AND fdCategoryID=" . $rowCategory["id"] . " AND fdRestaurantID=$liRestaurantID", "id" ) ;
			if ( is_null ( $objDailyCategory ) && $lfPlanCount > 0 )
				$lsSQL = "INSERT INTO tbDailyCategory (fdDate,fdCategoryID,fdRestaurantID,fdPlanCount) VALUES ('$asDate'," . $rowCategory["id"] . ",$liRestaurantID,$lfPlanCount)" ;
			else
				$lsSQL = "UPDATE tbDailyCategory SET fdPlanCount=$lfPlanCount+0 WHERE fdDate='$asDate' AND fdCategoryID=" . $rowCategory["id"] . " AND fdRestaurantID=$liRestaurantID" ;
			mysql_exec ( $lsSQL ) ;
			if ( $lfPlanCount > 0 )
			  print " as $lfPlanCount\r\n" ;
			else 
		    print "\r\n" ;
		}
		mysqli_free_result ( $rsCategory ) ;
	}

  $lsRestaurant = "" ;
	$argOptions = getopt ( "d:r:v" ) ;
	if ( array_key_exists ( "v", $argOptions ) ) {
		print "Usage: php forecast.php -r <restaurant> [-d <yyyy-mm-dd>]\r\nExample: php forecast.php -r tiande -d 20220801\r\n" ;
		exit ;
	} else {
	  if ( array_key_exists ( "r", $argOptions ) )
			$lsRestaurant = $argOptions ["r"] ; 
	}
	if ( array_key_exists ( "d", $argOptions ) )
	  $lsDate = Date ( "Y-m-d", strtotime ( $argOptions ["d"] ) ) ;
	else
	  $lsDate = Date ( "Y-m-d" ) ;

  mysql_init ( "localhost", "otago", "UTF8", "otago", "Otago@2022" ) ;

  if ( $lsRestaurant != "" ) {
		print "Going to predict restaurants $lsRestaurant at $lsDate\r\n" ;
	  fnPredictFood ( $lsRestaurant, $lsDate ) ;
		fnPredictCategory ( $lsRestaurant, $lsDate ) ;
	} else {
		print "Going to predict all restaurants at $lsDate\r\n" ;
	}
?>
