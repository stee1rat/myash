<?php

function removeEmptyLines($string) {
   return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
}

$query_mod1 = "";
$query_mod2 = "";
$query_mod3 = "";

if (isset($_POST['waitClass'])) {
   $query_mod2 = "event";

   if ($_POST['waitClass'] === 'CPU') {
      $query_mod1 = "AND wait_class is null";
   } else {
      $query_mod1 = "AND wait_class = '" . $_POST['waitClass'] . "'";
   }
} else {
   $query_mod2 = "wait_class";
}

if (isset($_POST['type'])) {
   if ($_POST['type'] === 'top-sql') {
      $query_mod3 .= "AND sql_id is not null";
   }
}

?>
