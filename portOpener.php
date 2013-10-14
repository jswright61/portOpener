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
$vars['rules'] = getExistingRules();
getVars();
if (! empty($_POST)) validate();
$vars['rules'] = getExistingRules();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Manage Firewall</title>
	<link rel="stylesheet" href="portOpener.css" type="text/css" />
</head>
<body>
<?php
	if (! empty($vars['message'])){
		echo '<p id="errMsg"> ' . $vars['message'] . '</p>' . PHP_EOL;
	}
	if (! empty($vars['rules'])){
?>
<h3>Existing Rules</h3>
<form name="existingRules" method="post" target="<?php echo $_SERVER['PHP_SELF']; ?>">
<table width="50%">
	<tr><th>Rule</th><th>Source IP</th><th>Port</th><th>Expires</th><th>Request IP</th></tr>
<?php
	foreach ($vars['rules'] as $ruleNum => $rule){
		echo '<tr><td>' . '<input type="checkbox" name=rule[' . $ruleNum . ']" /> ' . $ruleNum . '</td><td>' . $rule['ruleSourceIP'] . '</td><td>' . $rule['port'] 
			. '</td><td>' . date('D, H:i:s', $rule['expires']) . '</td><td>' . $rule['requestIP'] . '</td></tr>' . PHP_EOL;
	}
?>
</table>
	<br /><input type="submit" name="submit" value="Delete Selected Rules" /><br />
</form>
<?php 
	} else {
		echo '<h3>No Temporary Rules</h3>' . PHP_EOL;
	}
?>
<h3>Add New Rule</h3>
<form name="addRule" method="post" target="<?php echo $_SERVER['PHP_SELF']; ?>">
	<label for"ruleSourceIP">IP To Authorize: </label><input name="ruleSourceIP" type="text" class="<?php echo $vars['ruleSourceIP']['class']; ?>" value="<?php echo $vars['ruleSourceIP']['value']; ?>" /><br />
	<label for"port">Port to Open: </label><input name="port" id="port" type="text" class="<?php echo $vars['port']['class']; ?>" value="<?php echo $vars['port']['value']; ?>" /><br />
	<label for"duration">Duration: </label><input name="duration" id="duration" type="text" class="<?php echo $vars['duration']['class']; ?>" value="<?php echo $vars['duration']['value']; ?>" /> hours (1 - 24)<br />
	<br /><input type="submit" name="submit" value="Open Port" /><br />
</form>
<?php 
if (DEBUG && ! empty($vars['printArrays'])){
	echo '<pre>' . PHP_EOL;
	print_r($vars['printArrays']);
	echo '</pre>' . PHP_EOL;
}
?>
</body>
</html>
<?php
function getVars(){
	global $vars;
	$vars['message'] = '';
	$vars['printArrays'] = array();
	$vars['errsPresent'] = FALSE;
	$vars['ruleSourceIP']['value'] = filter_input(INPUT_POST, 'ruleSourceIP', FILTER_VALIDATE_IP);
	$vars['ruleSourceIP']['class'] = 'default';
	//$vars['port']['value'] = filter_input(INPUT_POST, 'port', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
	$vars['port']['value'] = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
	$vars['port']['class'] = 'default';
	$vars['duration']['value'] = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' =>24)));
	$vars['duration']['class'] = 'default';
	if (empty($_POST)){
		if (DEBUG){
			$defaultPort = $vars['maxPortNumber'];
		} else {
			$defaultPort = '22';
		}
		if (empty($vars['ruleSourceIP']['value'])) $vars['ruleSourceIP']['value'] = $_SERVER['REMOTE_ADDR'];
		if (empty($vars['port']['value'])) $vars['port']['value'] = $defaultPort;
		if (empty($vars['duration']['value'])) $vars['duration']['value'] = '6';
	}
}
function validate(){
	global $vars;
	// $vars['printArrays']['_POST'] = $_POST;
	if ($_POST['submit'] == 'Open Port'){
		if (empty($vars['ruleSourceIP']['value'])){
			$vars['message'] .= 'You must supply a valid IP address for Client IP<br />';
			$vars['errsPresent'] = TRUE;
			$vars['ruleSourceIP']['class'] = 'err';
		}

		if (empty($vars['port']['value'])){
			$vars['message'] .= 'You must supply a port number greater than zero (integers only)<br />';
			$vars['errsPresent'] = TRUE;
			$vars['port']['class'] = 'err';
		}
		if (empty($vars['duration']['value'])){
			$vars['message'] .= 'You must supply a duration betweeen 1 and 24 hours (integers only)<br />';
			$vars['errsPresent'] = TRUE;
			$vars['duration']['class'] = 'err';
		}
		if (! $vars['errsPresent']) openPort();
	} elseif ($_POST['submit'] == 'Delete Selected Rules'){
		$rulesToDelete = $_POST['rule'];

		// delete the rules in reverse order as rule numbers change as soon as a rule is deleted
		krsort($rulesToDelete);
		// $vars['printArrays']['rules_before_delete'] = $vars['rules'];
		foreach ($rulesToDelete as $ruleNum => $ruleToDelete){
			$cmd =COMMANDS . ' action=dropRule ruleNum=' . $ruleNum;
			$rtn = exec($cmd, $output, $rtnCode);
			$vars['printArrays']['deleteMessage'][] = $output;

			$vars['message'] .= 'Deleted Rule # ' . $ruleNum . ' Source IP: ' . $vars['rules'][$ruleNum]['ruleSourceIP'] . ',  Port: ' . $vars['rules'][$ruleNum]['port'] . '<br />';
		}
	}
}
function openPort(){
	global $vars;
	$cmd =COMMANDS . ' action=add requestIP=' . $_SERVER['REMOTE_ADDR'] . ' ruleSourceIP=' . $vars['ruleSourceIP']['value'] . ' port=' . $vars['port']['value']
		. ' delay=' . $vars['duration']['value'];
	//$vars['message'] .= 'Command: ' . $cmd . '<br />';
	//$vars['printArrays']['addCommand'][] = $cmd;
	$rtn = exec($cmd, $out, $rtnCode);
	$vars['message'] .= $out[count($out) -1];
	notifyRuleAdd($vars['ruleSourceIP']['value'], $vars['port']['value'], $vars['duration']['value']);
}
function getExistingRules(){
	global $vars;
	$cmd = COMMANDS . ' action=list';
	$rtn = exec ($cmd, $output, $rtnCode);
	$rules = array();
	$vars['maxPortNumber'] = '10001';
	foreach ($output as $line){
		$rtn = preg_match('/^(\d+)\s+ACCEPT\s+tcp\s+\-\-\s+(.*?)\s.*?dpt:(\d+)\s\/\*\stempRuleExpires:(\d+).*?requestIP:(.*?)\s\*\/$/', $line, $matches);
		$rule = array('ruleSourceIP' => $matches[2], 'port' => $matches[3], 'expires' => $matches[4], 'requestIP' => $matches[5]);

		if ((int)$matches[3] >= (int)$vars['maxPortNumber']) $vars['maxPortNumber'] = (string)((int)$matches[3] + 1);
		$rules[(string)$matches[1]] = $rule;
	}
	// $vars['printArrays']['rules'] = $rules;
	return $rules;
}
function notifyRuleAdd($ruleSourceIP, $port, $duration){
	global $vars;
	$hours = $duration . (($duration > 1)? ' hours' : ' hour');
	$message = 'Server IP: ' . $_SERVER['SERVER_ADDR'] . PHP_EOL . 'Request IP: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL . 'Rule Source IP: ' . $ruleSourceIP . PHP_EOL
	 	. 'Port: ' . $port . PHP_EOL . 'Duration: ' . $hours;
	sendMail($message);
}



function sendMail($message, $subject = NULL, $to = NULL){
	global $vars;
	if (empty($to)) $to = MAILTO;
	if (empty($subject)) $subject = 'New Temp Rule Added to '. $_SERVER['SERVER_NAME'];
	$headers = 'From: iptables@' . $_SERVER['SERVER_NAME'];
	if (mail($to, $subject, $message, $headers)){
		$vars['printArrays']['emailSend'] = 'Mail sent';
	} else {
		$vars['printArrays']['emailSend'] = 'Error sending mail';
	}

}
