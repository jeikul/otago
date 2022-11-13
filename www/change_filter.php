<script language=javascript>
<!--
  function fnChangeFilter () {
		var ctlChannels = document.getElementsByName ("lstChannel") ;
		var lsURL = "<?php print $_SERVER['PHP_SELF'] . '?date=' ; ?>" + document.all.txtDate.value + (document.all.lstRestaurant.value == -1 ? "" : ("&restaurant=" + document.all.lstRestaurant.value)) ;
		if ( ctlChannels.length > 0 ) {
		  lsURL = lsURL + (document.all.lstChannel.value == -1 ? "" : ("&channel=" + document.all.lstChannel.value)) ;
		}
	  document.location = lsURL ;
	}
-->
</script>
