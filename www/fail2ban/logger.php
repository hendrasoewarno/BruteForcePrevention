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

function sendMessage($chat_id, $text) {
  $botToken="<BOT_TOKEN_HERE>";
  $api_url="https://api.telegram.org/bot".$botToken;
  $params=array(
      'chat_id'=>$chat_id, 
      'text'=> $text,
  );
  $ch = curl_init($api_url . '/sendMessage');
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $result = curl_exec($ch);
  logDebug($result);
  curl_close($ch);
}

if (getClientIPAddress()=='127.0.0.1') {

$sql = <<<EOD
Insert Into fail2ban(actiontimestamp, sourceipaddr, rule, attempts, whois, log) Values (now(), :1, :2, :3, :4, :5);
EOD;

	//createRow(openConnection(), $sql, array("sourceipaddr", "rule", 1, "whois", "log"));
	//log to db
	createRow(openConnection(), $sql, array(param("sourceipaddr"), param("rule"), param("attempts"), param("whois"), param("log")));
	//send telegram
	//send telegram
	sendMessage(568577002, "The IP " . param("sourceipaddr") . " has just been banned by Fail2Ban after " . param("attempts") . "  attempts against" . param("rule"));
	sendMessage(568577002, substr(param("whois"),0,4096));
	sendMessage(568577002, param("log"));
} else {
	logDebug("call from outsider");
}

//unit test
//curl -X POST http://localhost/fail2ban/logger.php -d sourceipaddr="<ip>" -d rule="<name>" -d attempts=<failures> -d whois="`/usr/bin/whois -H <ip>`" -d log="`/bin/grep '\<<ip>\>' <logpath> | tail -n <failures>`"
//curl -k -X POST https://localhost/fail2ban/logger.php -d sourceipaddr="<ip>" -d rule="<name>" -d attempts=<failures> -d whois="`/usr/bin/whois -H <ip>`" -d log="`/bin/grep '\<<ip>\>' <logpath> | tail -n <failures>`"
?>
