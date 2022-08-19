<?php
  include_once "common_include.php" ;
?>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<link rel="stylesheet" type="text/css" href="daily.css" />
	<style>
    a{ text-decoration:none; color:lightskyblue;}
		td.weekday{ background: Cornsilk;}
		td.summary{ background: Bisque ;}
		td.weekend{ background: Lavender ;}
	</style>
	<?php
		include_once "change_restaurant.php" ;
	?>
</head>
<body>
	<table class="simple">
		<?php
			$liRestaurantID = 0 ;
			$lsRestaurant = "全部门店" ;
			$lsRestaurantSelected = "-1" ;
			if ( $_GET["restaurant"] != "" ) {
				$lsRestaurantSelected = $_GET["restaurant"] ;
				$objRestaurant = fnGetObject ( "tbRestaurant", "fdAbbreviate='$lsRestaurantSelected'", "id,fdName" ) ;
				$liRestaurantID = $objRestaurant->id ;
				$lsRestaurant = $objRestaurant->fdName ;
			}
			if ( $_GET["month"] != "" )
				$lsMonth = Date ( "Y-m", strtotime ( $_GET["month"] ) ) ;
			else
				$lsMonth = Date ( "Y-m", time() - 15 * 24 * 3600 ) ;
			print "<caption><select name=lstRestaurant onchange='fnChangeRestaurant()'>\r\n" ;
			fnFillList ( "SELECT fdAbbreviate,fdName FROM tbRestaurant", $lsRestaurantSelected, false, "全部门店" ) ;
			$ltDate = strtotime ( $lsMonth ) ;
			$lsLastMonth = date ( 'Y-m', strtotime ( "last month", $ltDate ) ) ;
			$lsNextMonth = date ( 'Y-m', strtotime ( "next month", $ltDate ) ) ;
		?>
		<br><a href="<?php print $_SERVER['PHP_SELF'] . "?month=$lsLastMonth" . ($_GET["restaurant"] != "" ? ("&restaurant=" . $_GET["restaurant"]) : "") ; ?>"> &lt;&lt;</a>
		<input name=txtDate value="<?php print $lsMonth ; ?>" size=10 readonly="readonly">
		<a href="<?php print $_SERVER['PHP_SELF'] . "?month=$lsNextMonth" . ($_GET["restaurant"] != "" ? ("&restaurant=" . $_GET["restaurant"]) : "") ; ?>"> &gt;&gt;</a>
		<br>销售月报</caption>
		<?php
  		fnDrawA_Block ( $liRestaurantID ) ;
		  if ( $liRestaurantID == 0 ) {
			  $lsSQL = "SELECT id FROM tbRestaurant" ;
				$rsRestaurant = mysql_exec ( $lsSQL ) ;
				$i = 0 ;
				while ( $rowRestaurant = mysqli_fetch_assoc ( $rsRestaurant ) ) {
				  fnDrawA_Block ( $rowRestaurant["id"], $liRestaurantID > 0 && $i == 0 ) ;
					$i ++ ;
				}
				mysqli_free_result ( $rsRestaurant ) ;
			}
		?>
	</table>
</body>
</html>
<?php
  function fnDrawA_Row ( $rs, $asCaption, $asField, $asAlign = "right", $asSummaryCaption = NULL, $asLeftCaption = NULL )
	{
	  global $liRestaurantID ;
		print "<tr>\r\n" ;
		if ( ! is_null ( $asLeftCaption ) ) {
		  if ( strcmp ( $asLeftCaption, "" ) == 0 )
			  print "<td />" ;
			else
		  print "<td rowspan=4>$asLeftCaption</td>" ;
		}
		print "<td>$asCaption</td>\r\n" ;
		mysqli_data_seek ( $rs, 0 ) ;
		$liCount = $summary = 0 ;
		while ( $row = mysqli_fetch_assoc ( $rs ) ) {
			$summary += ($data = $row["$asField"]) ;
			if ( $data > 0 ) $liCount ++ ;
			$wday = date ( 'w', strtotime ($row["fdDate"]) ) ;
			print "<td align=$asAlign" ;
			if ( $wday == 0 || $wday == 6 )
			  print " class=weekend" ;
			else
			  print " class=weekday" ;
			print ">" ;
			if ( strcmp ( $asField, "fdDay" ) == 0 ) {
			  print "<a href='daily.php?date=" . $row["fdDate"] ;
				if ( $liRestaurantID > 0 )
				  print "&restaurant=" . fnGetValue ( "tbRestaurant", "id=$liRestaurantID", "fdAbbreviate" ) ;
				print "'>" ;
			}
			if ( $data > 0 )
			  print $data ;
			if ( strcmp ( $asField, "fdDay" ) == 0 )
			  print "</a>" ;
			print "</td>\r\n" ;
		}
		if ( is_null ( $asSummaryCaption ) )
			print "<td align=$asAlign class=summary>" . $summary . "</td>\r\n" ;
		else if ( strcmp ( $asSummaryCaption, "average" ) == 0 ) {
			print "<td align=$asAlign class=summary>" . round ( $summary / $liCount, 2 ) . "</td>\r\n" ;
		} else
			print "<td align=center class=summary>$asSummaryCaption</td>\r\n" ;
		print "</tr>\r\n" ;
	}

	function fnDrawA_Block ( $aiRestaurantID, $abDrawHeader = true )
  {
	  global $lsMonth ;
		$lsDate = $lsMonth . "-01" ;
		if ( $aiRestaurantID > 0 )
			$lsSQL = "SELECT fdDate,DAY(fdDate) AS fdDay,fdOrderCount,fdServCount,fdIncome,ROUND(fdIncome/fdOrderCount,2) AS AvgPrice FROM tbDailySummary WHERE fdRestaurantID=$aiRestaurantID AND fdDate>='$lsDate' AND fdDate<DATE_ADD('$lsDate', INTERVAL 1 MONTH) ORDER BY fdDate" ;
		else
			$lsSQL = "SELECT fdDate,DAY(fdDate) AS fdDay,SUM(fdOrderCount) AS fdOrderCount,SUM(fdServCount) AS fdServCount,SUM(fdIncome) AS fdIncome,ROUND(SUM(fdIncome)/SUM(fdOrderCount),2) AS AvgPrice FROM tbDailySummary GROUP BY fdDate HAVING fdDate>='$lsDate' AND fdDate<DATE_ADD('$lsDate', INTERVAL 1 MONTH) ORDER BY fdDate" ;
		$rsDailySummary = mysql_exec ( $lsSQL ) ;
		if ( $abDrawHeader )
		  fnDrawA_Row ( $rsDailySummary, "日历", "fdDay", "center", "合计", "" ) ;
		fnDrawA_Row ( $rsDailySummary, "金额", "fdIncome", "right", NULL, $aiRestaurantID > 0 ? fnGetValue ( "tbRestaurant", "id=$aiRestaurantID", "fdName" ) : "合计" ) ;
		fnDrawA_Row ( $rsDailySummary, "订单", "fdOrderCount" ) ;
		fnDrawA_Row ( $rsDailySummary, "单价", "AvgPrice", "right", "average" ) ;
		fnDrawA_Row ( $rsDailySummary, "份数", "fdServCount" ) ;
		mysqli_free_result ( $rsDailySummary ) ;
  }
?>
