<?php
include_once "library.php";

class QueryBasePatterned {
	private $con;
	private $sql;
	private $patterned;
	private $recCount;	

	public function __construct($con, $sql, $patterned) {
		$this->con = $con;
		$temp = explode(";",$sql); $this->sql = trim($temp[0]);		
		#clean pattern
		$temp = str_replace("\t","",str_replace("\r\n","\n", $patterned));
		$lines = explode("\n",$temp);
		for ($i=0;$i<sizeof($lines);$i++)
			$lines[$i] = trim($lines[$i]);
		$this->patterned = implode(" ", $lines);
	}

	public function getDataCount() {
		return $this->recCount;
	}

//User customization
	public function reconvert($row) {
		return $row;
	}
	
//Response
	private function generateJSON($stmt, $objSeperator="\n") {
		$retStr = "";
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$this->recCount = 0;
			do {
				
				if ($this->recCount < 1)
					$retStr .= "";
				else
					$retStr .= $objSeperator;
				
				$newRow = $this->reconvert($row);
				
				$newObj = $this->patterned;
				
				for ($i=0; $i<sizeof($newRow);$i++) {			
					if (strpos($newObj,"{" . $i  . "}")>-1) 
						$newObj = str_replace("{" . $i . "}",$newRow[$i], $newObj);
				}
							
				$retStr .= $newObj;
				
				$this->recCount++;
			} while ($row = $stmt->fetch(PDO::FETCH_NUM));		
		}
		$retStr = $retStr; 
		return $retStr; 
	}

//Query
   public function query($values, $single=false, $objSeperator="\n", $singleNullReplace="null") {
	   try {
         if (startsWith(strtolower($this->sql),"select")) {
             if (!($stmt = $this->con->prepare($this->sql))) {
                throw new Exception("0:(" . $con->errno . ") " . $con->error);
             } else {
				$paramValues = $values;
				
				foreach ($paramValues as $key=>$value)
					$stmt->bindValue(':'.$key,$value);
 
                $stmt->execute();
								
				if ($single) {
					$strJSON = $this->generateJSON($stmt, $objSeperator);
					if ($strJSON=="")
						$strJSON = $singleNullReplace;
				}
				else
					$strJSON = "[" . $this->generateJSON($stmt) . "]";
				
				return $strJSON;				
				
             }
         } else {
            throw new Exception("0:Exception!: Invalid Query " . $strSQL);
         }
      } catch (PDOException $e) {
         throw new Exception("0:Exception!: " . $e->getMessage());
      }
   }
}
//unit test
/*
class TrialSummary Extends QueryBasePatterned {
	
	public function reconvert($row) {
		return array(str_pad($row[0], 30),str_pad($row[1], 30), str_pad($row[2], 10, " ", STR_PAD_LEFT));
	}

}

$conn = openConnection();
$pattern = <<<EOD
{0}{1}{2}
EOD;
echo "<pre>";
$queryBase = new TrialSummary($conn, "select name, position, count(*) from employee group by name, position;", $pattern);
try {	
	$retJSON = $queryBase->query(array(), true, "\n");
	echo $retJSON;
}
catch (Exception $e) {
	echo $e->getMessage();	
}
echo "</pre>";
*/
?>
