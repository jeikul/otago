<?php
	include_once "common_include.php" ;
?>

<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<link rel="stylesheet" type="text/css" href="daily.css" />
	<style>
    a{ text-decoration:none; color:lightskyblue;}
	</style>
</head>
<body>
  <div style="float: left; margin-right: 10px;">
  <?php
	  if ( isset($_GET["restaurant"]) == false ) {
		  include_once "_daily_summary.php" ;
		}
	?>
	</div>
  <div style="float: left; margin-right: 10px;">
    <?php include_once "_daily_channel.php" ; ?>
	</div>
  <div style="float: left; margin-right: 10px;">
    <?php include_once "_daily_category.php" ; ?>
	</div>
  <div style="float: left;">
    <?php include_once "_daily_food.php" ; ?>
	</div>
	<?php include_once "footer.php" ; ?>
<body>
</html>
