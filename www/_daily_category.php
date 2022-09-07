<table class="simple">
	<?php
	  if ( strstr ( $_SERVER["PHP_SELF"], "daily_category" ) )
		  $lbCategoryReportOnly = true ;
		else
		  $lbCategoryReportOnly = false ;
	  $lbLinkOrderFood = true ;
		$liServSummary = 0 ;
		$liFoodSummary = 0 ;
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
		print "<caption>$lsRestaurant<br>$lsDate<br>品类销量排行</caption>\r\n" ;
	?>
	<tr><td>名称</td>
	<?php
	  if ( $lbCategoryReportOnly )
		  print "<td align='center'>计划</td>" ;
	?>
	<td align="center">份数</td><td align="center">件数</td><td align="right">金额</td></tr>
	<?php 
	  if ( $liRestaurantID == 0 )
			$lsSQL = "SELECT fdCategoryID,MAX(tbCategory.fdName) AS fdName,SUM(fdPlanCount) AS fdPlanCount,SUM(fdServCount) AS fdServCount,SUM(fdIncome) AS fdIncome FROM tbDailyCategory LEFT JOIN tbCategory ON tbCategory.id=fdCategoryID WHERE fdDate='$lsDate' GROUP BY fdCategoryID ORDER BY fdIncome DESC,fdServCount DESC" ;
		else
			$lsSQL = "SELECT fdCategoryID,tbCategory.fdName,fdPlanCount,fdServCount,fdIncome FROM tbDailyCategory LEFT JOIN tbCategory ON tbCategory.id=fdCategoryID WHERE fdDate='$lsDate' AND fdRestaurantID=$liRestaurantID ORDER BY fdIncome DESC,fdServCount DESC" ;
		$rsDailyFood = mysql_exec ( $lsSQL ) ;
		while ( $rowDailyFoods = mysqli_fetch_assoc ( $rsDailyFood ) ) {
			print "<tr><td>" ;
			if ( $lbLinkOrderFood ) {
				$liCategoryID = $rowDailyFoods["fdCategoryID"] ;
				$lsSQL = "SELECT fdOrderID FROM (tbOrder_Food LEFT JOIN tbOrder ON tbOrder.id=fdOrderID) LEFT JOIN tbCategory_Food ON tbCategory_Food.fdFoodID=tbOrder_Food.fdFoodID WHERE fdCategoryID=$liCategoryID AND fdDateTime>='$lsDate' AND fdDateTime<DATE_ADD('$lsDate', INTERVAL 1 DAY)" ;
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
			print $rowDailyFoods["fdName"] ;
			if ( $lbLinkOrderFood )
				print "</a>" ;
			print "</td>" ;
		  if ( $rowDailyFoods["fdCategoryID"] < 9 ) {
				if ( $lbCategoryReportOnly )
					print "<td align=right>" . $rowDailyFoods["fdPlanCount"] . "</td>" ;
				print "<td align='center'>" . intval ($rowDailyFoods["fdServCount"]) . "</td><td /><td align='right'>" . $rowDailyFoods["fdIncome"] . "</td></tr>\r\n" ;
			  $liServSummary += $rowDailyFoods["fdServCount"] ;
			} else {
				print "<td /><td /><td align='center'>" . intval ($rowDailyFoods["fdServCount"]) . "</td><td align='right'>" . $rowDailyFoods["fdIncome"] . "</td></tr>\r\n" ;
			  $liFoodSummary += $rowDailyFoods["fdServCount"] ;
			}
			$liIncomeSummary += $rowDailyFoods["fdIncome"] ;
		}
		mysqli_free_result ( $rsDailyFood ) ;
		$liFreeCount = fnGetValue ( "tbOrder", "fdDateTime>='$lsDate' AND fdDateTime<DATE_ADD('$lsDate', INTERVAL 1 DAY) AND fdFree>0" . ($liRestaurantID > 0 ? " AND fdRestaurantID=$liRestaurantID" : ""), "COUNT(id)" ) ;
		$liServSummary += $liFreeCount ;
	?>
	<tr><td>散点</td><td /><td align="center"><?php print $liFreeCount; ?></td><td /><td align="right">n/a</td></tr>
	</tr><td>合计</td>
	<?php
	  if ( $lbCategoryReportOnly )
		  print "<td />" ;
	?>
	<td align="center"><?php print $liServSummary; ?></td><td align="center"><?php print $liFoodSummary; ?></td><td align="right"><?php print $liIncomeSummary; ?></td></tr>
</table>
