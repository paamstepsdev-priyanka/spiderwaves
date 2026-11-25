<?php
$lines = file('c:/wamp64/www/spiderwaves/application/controllers/report.php');
echo implode("", array_slice($lines, 65, 120));
