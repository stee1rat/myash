<?php
$query = <<<SQL
SELECT to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') + LEVEL/24/60/60*15 mm FROM dual CONNECT BY LEVEL <= 60*4
SQL;
?>
