<html>
<head />
<body>
<pre>
<?php
  if ( isset ( $_GET["file"] ) )
	  $lsFile = $_GET["file"] ;
	else 
	  $lsFile = "change.log" ;
  echo file_get_contents ( "../$lsFile" ) ;
?>
</pre>
</body>
</html>
