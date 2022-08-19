<script language=javascript>
<!--
  function fnChangeRestaurant () {
	  document.location = "<?php
			print $_SERVER['PHP_SELF'] . "?date=" ;
		?>" + document.all.txtDate.value + (document.all.lstRestaurant.value == -1 ? "" : ("&restaurant=" + document.all.lstRestaurant.value)) ;
	}
-->
</script>
