<?php
  include_once "change_filter.php" ;
?>
<table class="simple">
	<?php
		$liOrderSummary = 0 ;
		$liServSummary = 0 ;
		$liIncomeSummary = 0 ;
		$liRestaurantID = 0 ;
		$lsRestaurant = "全部门店" ;
		$lsRestaurantSelected = "-1" ;
		if ( $_GET["restaurant"] != "" ) {
		  $lsRestaurantSelected = $_GET["restaurant"] ;
			$objRestaurant = fnGetObject ( "tbRestaurant", "fdAbbreviate='$lsRestaurantSelected'", "id,fdName" ) ;
			$liRestaurantID = $objRestaurant->id ;
			$lsRestaurant = $objRestaurant->fdName ;
		}
		if ( $_GET["date"] != "" )
			$lsDate = Date ( "Y-m-d", strtotime ( $_GET["date"] ) ) ;
		else
			$lsDate = Date ( "Y-m-d", time() - 21 * 3600 ) ;
		print "<caption><select name=lstRestaurant onchange='fnChangeFilter()'>\r\n" ;
		fnFillList ( "SELECT fdAbbreviate,fdName FROM tbRestaurant", $lsRestaurantSelected, false, "全部门店" ) ;
		$ltDate = strtotime ( $lsDate ) ;
		$lsLastDate = date ( 'Ymd', $ltDate - 24 * 3600 ) ;
		$lsNextDate = date ( 'Ymd', $ltDate + 24 * 3600 ) ;
	?>
	<br><a href="<?php print $_SERVER['PHP_SELF'] . "?date=$lsLastDate" . ($_GET["restaurant"] != "" ? ("&restaurant=" . $_GET["restaurant"]) : "") ; ?>"> &lt;&lt;</a>
	<input name=txtDate value="<?php print $lsDate ; ?>" size=10 readonly="readonly">
	<a href="<?php print $_SERVER['PHP_SELF'] . "?date=$lsNextDate" . ($_GET["restaurant"] != "" ? ("&restaurant=" . $_GET["restaurant"]) : "") ; ?>"> &gt;&gt;</a>
	<br>渠道销量排行</caption>
	<tr><td colspan=2>名称</td><td align="center" colspan=2>单数</td><td align="center" colspan=2>份数</td><td align="right" colspan=2>金额</td></tr>
	<?php 
	  if ( $liRestaurantID == 0 )
			$lsSQL = "SELECT MAX(tbChannel.fdName) AS fdName,SUM(fdOrderCount) AS fdOrderCount,SUM(fdServCount) AS fdServCount,SUM(fdFoodCount) AS fdFoodCount,SUM(fdIncome) AS fdIncome,fdChannelID FROM tbDailyChannel LEFT JOIN tbChannel ON tbChannel.id=fdChannelID WHERE fdDate='$lsDate' AND fdParentID IS NULL GROUP BY fdChannelID ORDER BY fdIncome DESC,fdServCount DESC,fdOrderCount DESC" ;
		else
			$lsSQL = "SELECT tbChannel.fdName,fdOrderCount,fdServCount,fdFoodCount,fdIncome,fdChannelID FROM tbDailyChannel LEFT JOIN tbChannel ON tbChannel.id=fdChannelID WHERE fdDate='$lsDate' AND fdParentID IS NULL AND fdRestaurantID=$liRestaurantID ORDER BY fdIncome DESC,fdServCount DESC,fdOrderCount DESC" ;
		$rsDailyChannel = mysql_exec ( $lsSQL ) ;
		while ( $rowDailyChannel = mysqli_fetch_assoc ( $rsDailyChannel ) ) {
			print "<tr><td>" . $rowDailyChannel["fdName"] . "</td><td></td><td></td><td align='center'>" . intval ($rowDailyChannel["fdOrderCount"]) . "</td><td></td><td align='center'>" . intval ($rowDailyChannel["fdServCount"]) . "</td><td></td><td align='right'>" . $rowDailyChannel["fdIncome"] . "</td></tr>\r\n" ;
			$liChannelID = $rowDailyChannel["fdChannelID"] ;
			$liOrderSummary += $rowDailyChannel["fdOrderCount"] ;
			$liServSummary += $rowDailyChannel["fdServCount"] ;
			$liIncomeSummary += $rowDailyChannel["fdIncome"] ;
			if ( $liRestaurantID == 0 )
				$lsSQL = "SELECT MAX(tbChannel.fdName) AS fdName,SUM(fdOrderCount) AS fdOrderCount,SUM(fdServCount) AS fdServCount,SUM(fdFoodCount) AS fdFoodCount,SUM(fdIncome) AS fdIncome FROM tbDailyChannel LEFT JOIN tbChannel ON tbChannel.id=fdChannelID WHERE fdDate='$lsDate' AND fdParentID=$liChannelID GROUP BY fdChannelID ORDER BY fdIncome DESC,fdServCount DESC,fdOrderCount DESC" ;
			else
				$lsSQL = "SELECT tbChannel.fdName,fdOrderCount,fdServCount,fdFoodCount,fdIncome FROM tbDailyChannel LEFT JOIN tbChannel ON tbChannel.id=fdChannelID WHERE fdDate='$lsDate' AND fdParentID=$liChannelID AND fdRestaurantID=$liRestaurantID ORDER BY fdIncome DESC,fdServCount DESC,fdOrderCount DESC" ;
			$rsSubChannel = mysql_exec ( $lsSQL ) ;
			while ( $rowSubChannel = mysqli_fetch_assoc ( $rsSubChannel ) ) {
				print "<tr><td></td><td>" . $rowSubChannel["fdName"] . "</td><td align='center'>" . intval ($rowSubChannel["fdOrderCount"]) . "</td><td></td><td align='center'>" . intval ($rowSubChannel["fdServCount"]) . "</td><td></td><td align='right'>" . $rowSubChannel["fdIncome"] . "</td><td></td></tr>\r\n" ;
			}
			mysqli_free_result ( $rsSubChannel ) ;
		}
		mysqli_free_result ( $rsDailyChannel ) ;
	?>
	</tr><td colspan=2>合计</td><td /><td align="center"><?php print $liOrderSummary; ?></td><td /><td align="center"><?php print $liServSummary; ?></td><td align="right" colspan=2><?php print $liIncomeSummary; ?></td></tr>
</table>
