<?php
	include_once "../../util/php/mysql.php" ;
	include_once "../../util/php/log.php" ;

  mysql_init ( "localhost", "otago", "UTF8", "otago", "Otago@2022" ) ;
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