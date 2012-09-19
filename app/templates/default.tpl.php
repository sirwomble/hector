<h2>IP's <?php if(isset($range)) echo "in $range";?></h2>
<?php
$class = (isset($_GET['classB'])) ? 'classC' : 'classB';

if (isset($hosts) && is_array($hosts)) {
	print "<table><th>Hostname</th><th>IP</th><th>Open Ports</th><th>OS</th></tr>";
	foreach ($hosts as $host) {
		print "<tr><td><a href='?action=details&object=Host&id=" . $host->get_id();
		print "'>".$host->get_name()."</a></td><td>".$host->get_ip()."</td>";
		print "<td>" . $host->get_open_ports() . "</td>";
		print "<td>" . $host->get_os() . "</td>";
		print "</tr>";
	}
	print "</table>";
}
else {
	foreach($result as $row) {
		print "<a href='?action=default&$class=". $row->ipclass ."'>";
		print $row->ipclass . "</a> (" . $row->thecount . " hosts)<br/>";
	}
}

?>