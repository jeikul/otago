<table class="simple">
	<?php
	  $lsTill = "" ;
	  $lbLinkOrderFood = true ;
	  if ( strstr ( $_SERVER["PHP_SELF"], "daily_food" ) ) {
		  $lbFoodReportOnly = true ;
			if ( $_GET["till"] != "" ) {
				$lsTill = Date ( "Y-m-d", strtotime ( $_GET["till"] ) ) ;
	      $lbLinkOrderFood = false ;
			}
		} else
		  $lbFoodReportOnly = false ;
		$liServSummary = 0 ;
		$liIncomeSummary = 0 ;
		$liRestaurantID = 0 ;
		$lsRestaurant = "全部门店" ;
		if ( $_GET["restaurant"] != "" ) {
			$objRestaurant = fnGetObject ( "tbRestaurant", "fdAbbreviate='" . $_GET["restaurant"] . "'", "id,fdName" ) ;
			$liRestaurantID = $objRestaurant->id ;
			$lsRestaurant = $objRestaurant->fdName ;
		}
		if ( $_GET["date"] != "" )
			$lsDate = Date ( "Y-m-d", strtotime ( $_GET["date"] ) ) ;
		else
			$lsDate = Date ( "Y-m-d", time() - 21 * 3600 ) ;
		print "<caption>$lsRestaurant<br>$lsDate " . (strcmp ($lsTill, "") != 0 ? "～ $lsTill" : "")  . "<br>产品销量排行</caption>\r\n" ;
	?>
	<tr><td>名称</td>
	<?php
	  if ( $lbFoodReportOnly )
		  print "<td align='center'>计划</td>" ;
	?>
	<td align="center">件数</td><td align="right">金额</td></tr>
	<?php
	  $lsDateClause = strcmp ( $lsTill, "" ) != 0 ? "fdDate>='$lsDate' AND fdDate<='$lsTill'" : "fdDate='$lsDate'" ;
	  if ( $liRestaurantID == 0 || strcmp ( $lsTill, "" ) != 0 ) {
			$lsSQL = "SELECT fdFoodID,MAX(tbFood.fdName) AS fdName,SUM(fdPlanCount) AS fdPlanCount,SUM(fdServCount) AS fdServCount,SUM(fdIncome) AS fdIncome FROM tbDailyFood LEFT JOIN tbFood ON tbFood.id=fdFoodID WHERE $lsDateClause" ;
			if ( $liRestaurantID > 0 )
			  $lsSQL .= " AND fdRestaurantID=$liRestaurantID" ;
			$lsSQL .= " GROUP BY fdFoodID ORDER BY fdIncome DESC,fdServCount DESC" ;
		} else
			$lsSQL = "SELECT fdFoodID,tbFood.fdName,fdPlanCount,fdServCount,fdIncome FROM tbDailyFood LEFT JOIN tbFood ON tbFood.id=fdFoodID WHERE $lsDateClause AND fdRestaurantID=$liRestaurantID ORDER BY fdIncome DESC,fdServCount DESC" ;
		$rsDailyFood = mysql_exec ( $lsSQL ) ;
		while ( $rowDailyFood = mysqli_fetch_assoc ( $rsDailyFood ) ) {
			print "<tr><td>" ;
			if ( $lbLinkOrderFood ) {
			  $liFoodID = $rowDailyFood["fdFoodID"] ;
				$lsSQL = "SELECT fdOrderID FROM tbOrder_Food LEFT JOIN tbOrder ON tbOrder.id=fdOrderID WHERE fdFoodID=$liFoodID AND fdDateTime>='$lsDate' AND fdDateTime<DATE_ADD('$lsDate', INTERVAL 1 DAY)" ;
				if ( $liRestaurantID > 0 )
				  $lsSQL .= " AND fdRestaurantID=$liRestaurantID" ;
				$lsClause = "" ;
				$rsOrderIDs = mysql_exec ( $lsSQL ) ;
				while ( $rowOrderID = mysqli_fetch_assoc ( $rsOrderIDs ) ) {
				  $lsClause .= (strlen ( $lsClause ) == 0 ? "(" : ",") ;
				  $lsClause .= $rowOrderID["fdOrderID"] ;
				}
				$lsClause .= ")" ;
			  print "<a href='/otago/its/controls.php?table=tbOrder&clause=id IN $lsClause'>" ;
			}
			print $rowDailyFood["fdName"] ;
			if ( $lbLinkOrderFood )
			  print "</a>" ;
			print "</td>" ;
			if ( $lbFoodReportOnly )
			  print "<td>" . $rowDailyFood["fdPlanCount"] . "</td>" ;
			print "<td align='center'>" . intval ($rowDailyFood["fdServCount"]) . "</td><td align='right'>" . $rowDailyFood["fdIncome"] . "</td></tr>\r\n" ;
			$liServSummary += $rowDailyFood["fdServCount"] ;
			$liIncomeSummary += $rowDailyFood["fdIncome"] ;
		}
		mysqli_free_result ( $rsDailyFood ) ;
	?>
	</tr><td>合计</td>
	<?php
	  if ( $lbFoodReportOnly )
		  print "<td />" ;
	?>
	<td align="center"><?php print $liServSummary; ?></td><td align="right"><?php print $liIncomeSummary; ?></td></tr>
</table>
