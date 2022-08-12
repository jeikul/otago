<table class="simple">
	<?php
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
		print "<caption>$lsRestaurant<br>$lsDate<br>产品销量排行</caption>\r\n" ;
	?>
	<tr><td>名称</td><td align="center">件数</td><td align="right">金额</td></tr>
	<?php
	  if ( $liRestaurantID == 0 )
			$lsSQL = "SELECT MAX(tbFood.fdName) AS fdName,SUM(fdServCount) AS fdServCount,SUM(fdIncome) AS fdIncome FROM tbDailyFood LEFT JOIN tbFood ON tbFood.id=fdFoodID WHERE fdDate='$lsDate' GROUP BY fdFoodID ORDER BY fdIncome DESC,fdServCount DESC" ;
		else
			$lsSQL = "SELECT tbFood.fdName,fdServCount,fdIncome FROM tbDailyFood LEFT JOIN tbFood ON tbFood.id=fdFoodID WHERE fdDate='$lsDate' AND fdRestaurantID=$liRestaurantID ORDER BY fdIncome DESC,fdServCount DESC" ;
		$rsDailyFood = mysql_exec ( $lsSQL ) ;
		while ( $rowDailyFood = mysqli_fetch_assoc ( $rsDailyFood ) ) {
			print "<tr><td>" . $rowDailyFood["fdName"] . "</td><td align='center'>" . intval ($rowDailyFood["fdServCount"]) . "</td><td align='right'>" . $rowDailyFood["fdIncome"] . "</td></tr>\r\n" ;
			$liServSummary += $rowDailyFood["fdServCount"] ;
			$liIncomeSummary += $rowDailyFood["fdIncome"] ;
		}
		mysqli_free_result ( $rsDailyFood ) ;
	?>
	</tr><td>合计</td><td align="center"><?php print $liServSummary; ?></td><td align="right"><?php print $liIncomeSummary; ?></td></tr>
</table>
