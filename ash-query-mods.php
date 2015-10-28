<?php
   $query_mod1 = "";
   $query_mod2 = "";

   if (isset($_POST['waitclass'])) {
      $query_mod2 = "event";

      if ($_POST['waitclass'] === 'CPU') {
         $query_mod1 = "\n   and wait_class is null";
      } else {
         $query_mod1 = "\n   and wait_class = '" . $_POST['waitclass'] . "'";
      };
   } else {
      $query_mod2 = "wait_class";
   }
?>
