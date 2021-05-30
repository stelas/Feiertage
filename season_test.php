<?php

require 'season.php';

php_sapi_name() == 'cli' || die('Forbidden');

$jz = new Season();
for ($j = 2000; $j < 2100; $j++) {
	$jz->calc($j);
	echo date('d.m.Y', $jz->get(Season::Spring)) . "\t";
	echo date('d.m.Y', $jz->get(Season::Summer)) . "\t";
	echo date('d.m.Y', $jz->get(Season::Autumn)) . "\t";
	echo date('d.m.Y', $jz->get(Season::Winter)) . "\n";
}

?>
