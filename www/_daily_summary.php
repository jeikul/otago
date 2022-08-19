<table class="simple">
  <caption>门店销量排行</caption>
	<tr><td>排名</td><td>门店</td><td>单数</td><td>份数</td><td align="right">金额</td></tr>
	<?php
		if ( $_GET["date"] != "" )
			$lsDate = Date ( "Y-m-d", strtotime ( $_GET["date"] ) ) ;
		else
			$lsDate = Date ( "Y-m-d", time() - 21 * 3600 ) ;
		$i = $liOrderrSummary = $liServSummary = $lfIncomeSummary = 0 ;
	  $lsSQL = "SELECT fdRestaurantID,fdName,fdOrderCount,fdServCount,fdIncome FROM tbDailySummary LEFT JOIN tbRestaurant ON tbRestaurant.id=fdRestaurantID WHERE fdDate='$lsDate' ORDER BY fdIncome DESC" ;
		$rsDailySummary = mysql_exec ( $lsSQL ) ;
		while ( $rowDailySummary = mysqli_fetch_assoc ( $rsDailySummary ) ) {
		  print "<tr><td align='center'>" . ( ++$i ) . "</td>" ;
			print "<td>" . $rowDailySummary["fdName"] . "</td>" ;
			print "<td align='center'>" . $rowDailySummary["fdOrderCount"] . "</td>" ;
			print "<td align='center'>" . $rowDailySummary["fdServCount"] . "</td>" ;
			print "<td align='right'>" . $rowDailySummary["fdIncome"] . "</td></tr>\r\n" ;
			$liOrderSummary += $rowDailySummary["fdOrderCount"] ;
			$liServSummary += $rowDailySummary["fdServCount"] ;
			$lfIncomeSummary += $rowDailySummary["fdIncome"] ;
		}
		mysqli_free_result ( $rsDailySummary ) ;
		print "<tr><td>合计</td><td></td>" ;
		print "<td align='center'>$liOrderSummary</td>" ;
		print "<td align='center'>$liServSummary</td>" ;
		print "<td align='right'>$lfIncomeSummary</td></tr>\r\n" ;
	?>
</table>
