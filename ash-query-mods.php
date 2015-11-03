<?php
   $query_mod1 = "";
   $query_mod2 = "";
   $query_mod3 = "";

   if (isset($_POST['waitclass'])) {
      $query_mod2 = "event";

      if ($_POST['waitclass'] === 'CPU') {
         $query_mod1 = "\n   AND wait_class is null";
      } else {
         $query_mod1 = "\n   AND wait_class = '" . $_POST['waitclass'] . "'";
      }
   } else {
      $query_mod2 = "wait_class";
   }

   if (isset($_POST['type'])) {
      if ($_POST['type'] === 'top-sql') {
         $query_mod3 .= "\n   AND sql_id is not null";
      }
   }
?>
