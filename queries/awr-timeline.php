<?php
$query = <<<SQL
SELECT to_date(:day,'DD.MM.YYYY') + LEVEL/24/60*5 mm FROM dual CONNECT BY LEVEL <= 24*60/5
SQL;
?>
