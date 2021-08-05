<?php
/*
CREATE TABLE IF NOT EXISTS `fail2ban` (
  `actiontimestamp` datetime NOT NULL,
  `sourceipaddr` varchar(15) NOT NULL,
  `rule` varchar(30) NOT NULL,
  `attempts` int(11) NOT NULL,
  `whois` text NOT NULL,
  `log` text NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `actiontimestamp` (`actiontimestamp`),
  KEY `sourceipaddr` (`sourceipaddr`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
*/
include "framework/library.php";

if (getClientIPAddress()=='127.0.0.1') {

$sql = <<<EOD
Insert Into fail2ban(actiontimestamp, sourceipaddr, rule, attempts, whois, log) Values (now(), :1, :2, :3, :4, :5);
EOD;

	createRow(openConnection(), $sql, array("sourceipaddr", "rule", 1, "whois", "log"));
	//createRow(openConnection(), $sql, array(param("sourceipaddr"), param("rule"), param("attempts"), param("whois"), param("log")));

} else {
	logDebug("call from outsider");
}

//unit test
//curl -s -X POST http://localhost/fail2ban/logger.php -d sourceipaddr="<ip>" -d rule="<name>" -d attemps=<failures> -d whois="`/usr/bin/whois <ip>`" -d log="`/bin/grep '\<<ip>\>' <logpath> | tail -n <failures>`"
?>
