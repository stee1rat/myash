<?php
$query = <<<SQL
SELECT 'Connected to: ' || instance_name || '@' || host_name || ', Version: ' || version instance,
     trunc(sysdate - 1/24,'MI') start_date
FROM v\$instance, v\$license
SQL;
?>
