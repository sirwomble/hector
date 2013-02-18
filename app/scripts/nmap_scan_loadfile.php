<?php
/*
 * This script is an atomic import of the results of an
 * NMAP scan run to produce an output XML file.  When the
 * contents of this script were combined with nmap_scan.php
 * there were often issues in the MySQL database that 
 * required this portion to be re-run.  To that end the
 * commands herein were split out to allow for easy CLI
 * control of this step in the process.
 * 
 * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
 * @package HECTOR
 * 
 * Last modified September 6, 2012
 */
 
/**
 * Defined vars and ensure we only execute under CLI includes
 */
if(php_sapi_name() == 'cli') {
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	$approot = realpath(substr($_SERVER['PATH_TRANSLATED'],0,strrpos($_SERVER['PATH_TRANSLATED'],'/')) . '/../') . '/';	

	/**
	 * Neccesary includes
	 */
	require_once($approot . 'lib/class.Config.php');
	require_once($approot . 'lib/class.Dblog.php');
	require_once($approot . 'lib/class.Alert.php');
	require_once($approot . 'lib/class.Host.php');
	require_once($approot . 'lib/class.Nmap_scan_result.php');
		
	// Set high mem limit to prevent resource exhaustion
	ini_set('memory_limit', '512M');
	
	syslog(LOG_INFO, 'Nmap_scan_loadfile.php starting.');
	
	// Check to make sure arguments are present
	if ($argc < 3) show_help("Too few arguments!  You tried:\n " . implode(' ', $argv));
	
	$xmloutput = $argv[1];
	
	
	
	/**
	 * Singletons
	 */
	new Config();
	$db = Db::get_instance();
	$dblog = Dblog::get_instance();
	$log = Log::get_instance();
		
	// Load up the XML and parse it 
	$nmaprun = simplexml_load_file($xmloutput);
	if (! $nmaprun) {
		loggit("nmap_scan.php process", "There was a problem parsing the XML file $xmloutput!");
	}
	// just grab all the hosts
	$allhosts = new Collection('Host');
	if (isset($allhosts->members) && is_array($allhosts->members)) {
		foreach ($allhosts->members as $newhost) {
			$hosts[$newhost->get_ip()] = $newhost;
		}
	}
			
	foreach($nmaprun->host as $nmaphost) {
		// Sometimes scans take more than 8 hours and the MySQL connection closes
		$db = Db::get_instance();
		// look up the host
		$host = $hosts[(string)$nmaphost->address['addr']]; 
		// Track new results via variables
		$nmap_scan_results = array();
	
		foreach ($nmaphost->ports->port as $port) {
			$result = new Nmap_scan_result();
			$result->set_host_id($host->get_id());
			$result->set_port_number($port['portid']);
			switch ($port->state['state']) {
				case 'open' : $result->set_state_id(1); break;
				case 'closed' : $result->set_state_id(2); break;
				case 'filtered' : $result->set_state_id(3); break;
			}
			$version_info = $port->service['product'] . " " . $port->service['version'];
			if (isset($port->service['devicetype']))  $version_info .= ' ' . $port->service['devicetype'];
			if (isset($port->service['extrainfo']))  $version_info .= ' ' . $port->service['extrainfo'];
			if (isset($port->service['servicefp']))  $version_info .= ' ' . $port->service['servicefp'];
			if ($version_info != ' ') $result->set_service_version($version_info);
			$result->set_service_name($port->service['name']);
			$nmap_scan_results[] = $result;
		}			
	
		foreach($nmap_scan_results as $scan) {
			$old_scan_result = new Nmap_scan_result();
			$old_scan_result->lookup_scan($scan->get_host_id(), $scan->get_port_number());
			if ($old_scan_result->get_id() > 0) {
				if ($scan->get_state_id() == 1 && $old_scan_result->get_state_id() > 1) {
					require_once($approot . 'lib/class.Alert.php');
					$alert = new Alert();
					$string = "Port " . $scan->get_port_number() . " changed from " .
										$old_scan_result->get_state() . " to open on " . $host->get_name();
					$alert->set_host_id($host->get_id());
					$alert->set_string($string);
					$alert->save(); 
				}
				$old_scan_result->delete();
			}
			
			// record the new results
			if ($scan->save() === FALSE) echo "There was an error saving scan for " . 
			"port " . $scan->get_port_number() . " on host " . $scan->get_host_id() . "\n";
		}
	}	
}
?>