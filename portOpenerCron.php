#!/usr/bin/php
<?php

/*
    Port Opener is a set of programs and instructions designed to allow the temporary opening of ports on a Linux firewall.
    Copyright (C) 2013  Scott Wright scott@wrightzone.com

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see [http://www.gnu.org/licenses/].

*/

require_once(__DIR__ . '/portOpenerConfig.php');
$cmd = IPTABLES . ' --list TEMP_OPENINGS --numeric --line-numbers';
$rtn = exec($cmd, $out, $rtnCode);
if ($rtnCode != 0){
	// TEMP_OPENINGS does not exist

	// create the chain
	$cmd = IPTABLES . ' --new TEMP_OPENINGS';
	$rtn = exec($cmd, $out, $rtnCode);

	// insert a jump rule on the INPUT chain
	$cmd = IPTABLES . ' --insert INPUT --jump TEMP_OPENINGS';
	$rtn = exec($cmd, $out, $rtnCode);

	// since the chain wasn't there, we can just exit
	exit();
}

$delRules = array();
foreach ($out as $line){
	if (preg_match('/^(\d+).*\/\*\s+tempRuleExpires:(\d+)\s+.*$/', $line, $matches)){
		if ((int)$matches[2] < time()){
			$delRules[] = $matches[1];
		}

	}
}
// delete rules from highest to lowest so that rule numbers don't change when a lower number rule is deleted
rsort($delRules);
foreach ($delRules as $ruleNum){
	$cmd2 = IPTABLES . ' --delete TEMP_OPENINGS ' .  $ruleNum;
	// echo $cmd2 . PHP_EOL;
	$rtn2 = exec($cmd2, $out2, $rtnCode2);
}
