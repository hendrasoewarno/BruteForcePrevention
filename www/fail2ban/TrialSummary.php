<?php
include_once "framework/library.php";
include_once "framework/QueryBasePatterned.php";

class TrialSummary Extends QueryBasePatterned {
	
	public function reconvert($row) {
		return array(substr(str_pad($row[0], 25),0,25), str_pad($row[1], 4, " ", STR_PAD_LEFT));
	}

}

function getTrialSummary($param, $by) {
	$param = strtolower($param);
	$range = explode("to",$param);
	if (sizeof($range)==1)
		$range[1]=$range[0];
	$conn = openConnection();
	$pattern = <<<EOD
{0}{1}
EOD;
	//echo "<pre>";
	if ($by=="rule")
                $queryBase = new TrialSummary($conn, "select rule, count(*) from fail2ban where actiontimestamp between :from and :to group by rule order by count(*) desc;", $pattern);
        else if ($by=="name")
                $queryBase = new TrialSummary($conn, "select name, count(*) from fail2ban where actiontimestamp between :from and :to group by name order by count(*) desc;", $pattern);
        else if ($by=="country")
                $queryBase = new TrialSummary($conn, "select case when b.country is null then a.country else concat(a.country,'-',b.description) end, count(*) from fail2ban a left join ccode b on a.country=b.country where a.actiontimestamp between :from and :to group by a.country order by count(*) desc;", $pattern);
        else if ($by=="month")
                $queryBase = new TrialSummary($conn, "select month(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by month(actiontimestamp);", $pattern);
        else if ($by=="date")
                $queryBase = new TrialSummary($conn, "select date(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by date(actiontimestamp);", $pattern);
        else if ($by=="hour")
                $queryBase = new TrialSummary($conn, "select hour(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by hour(actiontimestamp);", $pattern);
        else
                return "unknowned by.";

        try {
                $retJSON = $queryBase->query(array("from"=>$range[0] . " 00:00:00", "to" => $range[1] . " 23:59:59"), true, "\n", "not found.");
                //echo $retJSON;
                return substr($retJSON,0,4000);
        }
        catch (Exception $e) {
                //echo $e->getMessage();
                return $e->getMessage();
        }
        //echo "</pre>";
}
//unit test
//getTrialSummary("1990-01-01to2099-12-31", "rule");
?>
