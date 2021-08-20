<?php
include_once "framework/library.php";
include_once "phplot-6.2.0/phplot.php";

function mycallback($str)
{
    list($percent, $label) = explode(' ', $str, 2);
    return sprintf('%s (%.1f%%)', $label, $percent);
}

try {
	$by = param("by");
	$param = strtolower(param("param"));
	$range = explode("to",$param);
	if (sizeof($range)==1)
		$range[1]=$range[0];
	$slat = param("slat");
	$signature = param("signature");
	$oursignature = md5($by . $param . $slat . "secret");

        $limit=10;

        if ($oursignature==$signature) {
                $conn = openConnection();

                if ($by=="rule")
                        $sql = "select rule, count(*) from fail2ban where actiontimestamp between :from and :to group by rule order by count(*) desc;";
                else if ($by=="name") {
                        $sql = "select name, count(*) from fail2ban where actiontimestamp between :from and :to group by name order by count(*) desc;";
                        $limit = 20;
                }
                else if ($by=="country") {
                        $sql = "select case when b.country is null then a.country else concat(a.country,'-',b.description) end, count(*) from fail2ban a left join ccode b on a.country=b.country where a.actiontimestamp  between :from and :to group by a.country order by count(*) desc;";
                        $limit = 20;
                }
                else if ($by=="month") {
                        $sql = "select month(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by month(actiontimestamp);";
                        $limit = 12;
                }
                else if ($by=="date") {
                        $sql = "select date(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by date(actiontimestamp);";
                        $limit = 31;
                }
                else if ($by=="hour") {
                        $sql = "select hour(actiontimestamp), count(*) from fail2ban where actiontimestamp between :from and :to group by hour(actiontimestamp);";
                        $limit = 24;
                }
                else
                        throw new Exception("unknowned by.");

                $rawdata = queryArrayRowsValues($conn, $sql, array("from"=>$range[0] . " 00:00:00", "to" => $range[1] . " 23:59:59"));
        } else
                throw new Exception("invalid signature." . $oursignature);

        $data=array();

        for ($i=0;$i<sizeof($rawdata);$i++) {
                if ($i==$limit-1) {
                        $data[$limit-1]=$rawdata[$i];
                } else if ($i>$limit-1) {
                        $data[$limit-1][0]='Others';
                        $data[$limit-1][1]+=$rawdata[$i][1];
                } else
                        $data[]=$rawdata[$i];
        }

	$plot = new PHPlot(600, 400);
	$plot->SetImageBorderType('plain'); // Improves presentation in the manual
	# Main plot title:
	$plot->SetTitle($by . "(" . $param . ")");
		
	#$plot->SetBackgroundColor('gray');
	#  Set a tiled background image:
	//$plot->SetPlotAreaBgImage('images/drop.png', 'centeredtile');
	#  Force the X axis range to start at 0:
	$plot->SetPlotAreaWorld(0);
	#  No ticks along Y axis, just bar labels:
	$plot->SetYTickPos('none');
	#  No ticks along X axis:
	$plot->SetXTickPos('none');
	#  No X axis labels. The data values labels are sufficient.
	$plot->SetXTickLabelPos('none');
	#  Turn on the data value labels:
	$plot->SetXDataLabelPos('plotin');
	#  No grid lines are needed:
	$plot->SetDrawXGrid(FALSE);
	#  Set the bar fill color:
	$plot->SetDataColors('red');
	#  Use less 3D shading on the bars:
	$plot->SetShading(2);
	$plot->SetDataValues($data);
	$plot->SetDataType('text-data-yx');
	$plot->SetPlotType('bars');
	#$plot->SetPlotType('thinbarline');
	#$plot->SetLineWidths(4);
	$plot->DrawGraph();
		
} catch (Exception $e) {
	echo $e->getMessage();
}
?>
