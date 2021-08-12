<?php
/*
CREATE TABLE IF NOT EXISTS `fail2ban` (
  `actiontimestamp` datetime NOT NULL,
  `sourceipaddr` varchar(15) NOT NULL,
  `inetnum` varchar(40),
  `name` varchar(100),
  `organization` varchar(100),
  `country` varchar(2),
  `rule` varchar(30) NOT NULL,
  `attempts` int(11) NOT NULL,
  `log` text NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `actiontimestamp` (`actiontimestamp`),
  KEY `sourceipaddr` (`sourceipaddr`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
*/
include "framework/library.php";
include "phpWhois.org-master/whois.main.php";

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
Insert Into fail2ban(actiontimestamp, sourceipaddr, rule, attempts, log) Values (now(), :1, :2, :3, :4);
EOD;

	//log to db
	$conn=openConnection();
	createRow($conn, $sql, array(param("sourceipaddr"), param("rule"), param("attempts"), param("log")));
	$id = $conn->lastInsertId();
	

$sql = <<<EOD
Update fail2ban Set inetnum=:1, name=:2, organization=:3, country=:4 Where id=:5;
EOD;
	
	$whois = new Whois();
	$result = $whois->Lookup(param("sourceipaddr"),false);
	
	if ($result['regrinfo']['registered'] == 'yes') {
		updateRow($conn, $sql, array(
			$result['regrinfo']['network']['inetnum'],
			$result['regrinfo']['network']['name'],
			$result['regrinfo']['owner']['organization'],
			$result['regrinfo']['network']['country'],
			$id)
		);
		//send telegram
		sendMessage(568577002, "The IP " . param("sourceipaddr") . "(" . $result['regrinfo']['network']['name'] . "[" . $result['regrinfo']['network']['country'] . "]) has just been banned by Fail2Ban after " . param("attempts") . "  attempts against " . param("rule") . "with logs " . param("log"));
	} else {
		sendMessage(568577002, "The IP " . param("sourceipaddr") . " has just been banned by Fail2Ban after " . param("attempts") . "  attempts against " . param("rule") . "with logs " . param("log"));
	}
} else {
	logDebug("call from outsider");
}

//unit test
//curl -X POST http://localhost/fail2ban/logger.php -d sourceipaddr="<ip>" -d rule="<name>" -d attempts=<failures> -d log="`/bin/grep '\<<ip>\>' <logpath> | tail -n <failures>`"
//curl -k -X POST https://localhost/fail2ban/logger.php -d sourceipaddr="<ip>" -d rule="<name>" -d attempts=<failures> -d log="`/bin/grep '\<<ip>\>' <logpath> | tail -n <failures>`"
?>
