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
$args = checkArgs($argv);
if (! $args['valid']){
	echo $args['message'] . PHP_EOL;
	exit();
}

// always list rules: if the TEMP OPENINGS chain does not exist, it will return a non zero code
$rules = listRules();

switch ($args['action']){
	case 'add':
		$cmd = IPTABLES . ' --append TEMP_OPENINGS --protocol tcp --source ' . $args['ruleSourceIP'] . ' --dport ' . $args['port'] . ' --jump ACCEPT '
			. '--match comment --comment "tempRuleExpires:' . $args['expires'] . ' ' . date('D H:i:s', $args['expires']) . ' requestIP:' . $args['requestIP'] . '"';
		$rtn = exec($cmd, $out, $rtnCode);
		if ($rtnCode == 0){
			echo 'Rule inserted' . PHP_EOL;
		} else {
			echo 'Error inserting rule' . PHP_EOL;
			echo 'cmd: ' . $cmd . PHP_EOL;
		}
		break;
	case 'dropAll':
		$cmd = IPTABLES . ' --flush TEMP_OPENINGS';
		$rtn = exec($cmd, $out, $rtnStatus);
		break;
	case 'dropIP':
		deleteIP($args['ruleSourceIP'], $rules);
		break;
	case 'dropRule':
		$cmd = IPTABLES . ' --delete TEMP_OPENINGS ' .  $args['ruleNum'];
		$rtn = exec($cmd, $out, $rtnStatus);
		break;
	case 'list':
		// the first two lines contain header info, so ignore
		for ($i=2; $i < count($rules); $i++){
			echo $rules[$i] . PHP_EOL;
		}
		break;
}
function deleteIP($ip, $rules){
	$ip = str_replace('.', '\.', $ip);
	// reverse order so deleting lower # rules does not affect higher number rules
	$rulesDesc = array_reverse($rules);
	foreach ($rulesDesc as $rule){
		if (preg_match('/^(\d+)\s+ACCEPT\s+tcp\s+\-\-\s+(' . $ip . ')\s.*?tempRuleExpires.*$/', $rule, $matches)){
			$cmd = '/sbin/iptables --delete TEMP_OPENINGS ' .  $matches[1];
			$rtn = exec($cmd, $out, $rtnStatus);
		}
	}

}
function listRules(){
	$cmd = IPTABLES . ' --list TEMP_OPENINGS --line-numbers --numeric';
	$rtn = exec($cmd, $rulesAsc, $rtnCode);
	if ($rtnCode !=0){
		// create the chain
		$cmd = IPTABLES . ' --new TEMP_OPENINGS';
		$rtn = exec($cmd, $out, $rtnCode);

		// insert a jump rule on the INPUT chain
		$cmd = IPTABLES . ' --insert INPUT -j TEMP_OPENINGS';
		$rtn = exec($cmd, $out, $rtnCode);
		return array();
	}
	return $rulesAsc;
}
function checkArgs($cliArgs){
	// get rid of the zero element which we don't need
	array_shift($cliArgs);
	$args = array('message' => '', 'valid' => FALSE);
	// get each argument into the args array, before = is key, after = is value
	foreach ($cliArgs as $val) {
		$pieces = explode('=', $val);
		if ($pieces[0] == 'valid' || $pieces[0] == 'message') continue;
		$args[$pieces[0]] = $pieces[1];
	}
	if (empty($args['action'])) {
		$args['message'] .= 'The action argument is required. The action keyword is case sensitive' . PHP_EOL;
		return $args;
	}
	switch ($args['action']){
		case 'add':
			if ((empty($args['ruleSourceIP']) || empty($args['requestIP']))){
				$args['message'] .= 'The add action requires additional parameters, ruleSourceIP and requestIP are both required for action add.' . PHP_EOL;
			return $args;
			}
			break;
		case 'dropRule':
			if (empty($args['ruleNum'])) {
				$args['message'] .= 'The dropRule action requires an additional parameter, ruleNum is required for action dropRule.' . PHP_EOL;
			return $args;
			}
			break;
		case 'dropIP':
			if (empty($args['ruleSourceIP'])) {
				$args['message'] .= 'The dropIP action requires an additional parameter, ruleSourceIP is required for action dropIP.' . PHP_EOL;
			return $args;
			}
			break;
		case 'dropAll':
		case 'list':
			// no further required arguments
			break;
		default:
			$args['message'] .= $args['action'] . ' is not a valid action. The action argument is case sensitive' . PHP_EOL
				. 'Valid actions: add, dropAll, dropIP, dropRule, and list' . PHP_EOL;
			return $args;
			break;
	}
	if (!empty($args['ruleSourceIP'])){
		$args['ruleSourceIP'] = filter_var($args['ruleSourceIP'], FILTER_VALIDATE_IP);
		if ($args['ruleSourceIP'] == ''){
			$args['message'] = 'The ruleSourceIP must be a valid IP address';
			return $args;
		}
	}
	if (!empty($args['requestIP'])){
		$args['requestIP'] = filter_var($args['requestIP'], FILTER_VALIDATE_IP);
		if ($args['requestIP'] == ''){
			$args['message'] = 'The requestIP must be a valid IP address';
			return $args;
		}
	}
	if (! isset($args['port'])){
		$args['port'] = 10022;
	} else {
		$args['port'] = filter_var($args['port'], FILTER_VALIDATE_INT);
		if ($args['port'] == ''){
			$args['message'] = 'The port, if supplied, must be an integer. If port argument is omitted, the port is defaulted to port 10022';
			return $args;
		}
	}
	if (! isset($args['delay'])){
		$args['delay'] = 4;
	} else {
		$args['delay'] = filter_var($args['delay'], FILTER_VALIDATE_INT);
		if ($args['port'] == ''){
			$args['message'] = 'The delay, if supplied, must be an integer. If delay argument is omitted, the delay is defaulted to port 4 hours';
			return $args;
		}
		if ($args['delay'] > 24) $args['delay'] = 24;
	}
	if (! empty($args['ruleNum'])){
		$args['ruleNum'] = filter_var($args['ruleNum'], FILTER_VALIDATE_INT);
		if ($args['port'] == ''){
			$args['message'] = 'The ruleNum, must be an integer.';
			return $args;
		}
	}
	$args['valid'] = TRUE;
	$args['expires'] = time() + $args['delay'] * 3600;
	return $args;
}
