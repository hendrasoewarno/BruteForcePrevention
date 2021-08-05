<?php
function openConnection() {
   try {
      $con = new PDO('mysql:host=localhost;port=3306;dbname=infosec', 'root', '');
      $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch (PDOException $e) {
      throw new Exception("0: " . $e->getMessage());
   }
   return $con;
}

function TimeStampMicro() {
        $t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);
        return date('Y-m-d H:i:s') . '.' . $micro;
}

function logDebug($text) {
	file_put_contents("log/log.txt", TimeStampMicro() . "[" . getClientIPAddress() . "] " . " -- " . $text . "\n\n", FILE_APPEND | LOCK_EX);
}

function getClientIPAddress() {
	$ipaddress="";
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ipaddress= $_SERVER['HTTP_CLIENT_IP'];
	else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ipaddress= $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_X_FORWARDED']))
		$ipaddress= $_SERVER['HTTP_X_FORWARDED'];	
	else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
		$ipaddress= $_SERVER['HTTP_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_FORWARDED']))
		$ipaddress= $_SERVER['HTTP_FORWARDED'];	
	else if (isset($_SERVER['REMOTE_ADDR']))
		$ipaddress= $_SERVER['REMOTE_ADDR'];	
	return $ipaddress;
}

function param($name, $mandatory=1, $default="") {
	$value = $default;
	if (isset($_POST[$name])) {
		$value = $_POST[$name];
	} else if (isset($_GET[$name])) {
		$value = $_GET[$name];
	} else if ($mandatory==1) {
		throw new Exception("ERROR:parameter $name tidak ditemukan!");
	}
	return $value;
}

//CRUD
function createRow($con, $sSql, $values) {
//return row Affected
	  $temp = explode(";",$sSql); $sSql0 = $temp[0];
      if (!startsWith(strtolower(trim($sSql0)),"insert"))
            throw new Exception("0: Invalid Insert statement!");

      try {
         if (!($stmt = $con->prepare($sSql0))) {
            throw new Exception("0:  (" . $con->errno . ") " . $con->error);
         } else {
            $paramValues = $values;
			if (strpos($sSql0, "?")) {
				for ($i=0; $i<sizeof($values);$i++) {   
					$paramCount = $i+1;
					$paramValues[$i] = descapeCSV($values[$i]);
					$stmt->bindParam($paramCount, $paramValues[$i]);
				}			
			} else if (strpos($sSql0, ":1")) {
				for ($i=0; $i<sizeof($values);$i++) {   
					$paramCount = $i+1;
					$paramValues[$i] = descapeCSV($values[$i]);
					$stmt->bindParam(":" . $paramCount, $paramValues[$i]);
				}				
			} else {
				foreach ($paramValues as $key=>$value)
					$stmt->bindValue(':'.$key,$value);				
			}
            $stmt->execute();
            return $stmt->rowCount();
         }
      } catch (PDOException $e) {
         throw new Exception("0: " . $e->getMessage());
      }
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function descapeCSV($str) {
//descape karakter CR, Lf, dan ;
   return str_replace("&colon",":",str_replace("&linefeed","\n",str_replace("&carriagereturn","\r",str_replace("&semicolon",";",$str))));
}
?>
