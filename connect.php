<?php
   $connect = oci_connect($_POST["username"], $_POST["password"], $_POST["host"].':'. $_POST["port"] . '/' . $_POST["service"]);

   if (!$connect) {
      $m = oci_error();
      trigger_error(htmlentities($m['message']), E_USER_ERROR);
   }

   date_default_timezone_set('UTC');
?>
