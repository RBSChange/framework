<?php 
/**
 * @deprecated
 */
require("Charts.php");
// **** DEMOS ****

$title = "My graph
New line";

switch ($_GET["chart"])
{
	case "1":
		$data = new f_chart_DataTable();
		$data->addColumn('Year', f_chart_DataTable::STRING_TYPE);
		$data->addColumn('Sales', f_chart_DataTable::NUMBER_TYPE, "ff0000");
		$data->addColumn('Expenses', f_chart_DataTable::NUMBER_TYPE, "0000ff");
		$data->addRows(4);
		$data->setValue(0, 0, '2004');
		$data->setValue(0, 1, 1000);
		$data->setValue(0, 2, 400);
		$data->setValue(1, 0, '2005');
		$data->setValue(1, 1, 1170);
		$data->setValue(1, 2, 460);
		$data->setValue(2, 0, '2006');
		$data->setValue(2, 1, 860);
		$data->setValue(2, 2, 580);
		$data->setValue(3, 0, '2007');
		$data->setValue(3, 1, 1030);
		$data->setValue(3, 2, 540);

		$chart = new f_chart_LineChart($data);
		$chart->setTitle($title);
		$chart->setLeftAxis(new f_chart_Axis(new f_chart_Range(200, 1200)));
		$chart->setGrid(new f_chart_Grid(33.333, 20));
		break;

	case "2":
		$data = new f_chart_DataTable();
		$data->addColumn('Name', f_chart_DataTable::STRING_TYPE);
		$data->addColumn('Height');
		$data->addRows(3);
		$data->setValue(0, 0, 'Tong Ning mu');
		$data->setValue(1, 0, 'Huang Ang fa');
		$data->setValue(2, 0, 'Teng nu');
		$data->setValue(0, 1, 174);
		$data->setValue(1, 1, 523);
		$data->setValue(2, 1, 86);

		$chart = new f_chart_LineChart($data);
		$chart->setTitle($title);
		$chart->setLeftAxis(new f_chart_Axis(new f_chart_Range(0, 600)));

		break;

	case "3":
		$data = new f_chart_DataTable();
		$data->addColumn('Task', f_chart_DataTable::STRING_TYPE);
		$data->addColumn('Hours per Day');
		$data->addRows(5);
		$data->setValue(0, 0, 'Work');
		$data->setValue(0, 1, 11);
		$data->setValue(1, 0, 'Eat');
		$data->setValue(1, 1, 2);
		$data->setValue(2, 0, 'Commute');
		$data->setValue(2, 1, 2);
		$data->setValue(3, 0, 'Watch TV');
		$data->setValue(3, 1, 2);
		$data->setValue(4, 0, 'Sleep');
		$data->setValue(4, 1, 7);

		$chart = new f_chart_PieChart($data);
		$chart->setTitle($title);
		break;

	case "4":
		$data = new f_chart_DataTable();
		$data->addColumn('Year', f_chart_DataTable::STRING_TYPE);
		$data->addColumn('Sales', f_chart_DataTable::NUMBER_TYPE, "ff0000");
		$data->addColumn('Expenses', f_chart_DataTable::NUMBER_TYPE, "0000ff");
		$data->addRows(4);
		$data->setValue(0, 0, '2004');
		$data->setValue(0, 1, 1000);
		$data->setValue(0, 2, 400);
		$data->setValue(1, 0, '2005');
		$data->setValue(1, 1, 1170);
		$data->setValue(1, 2, 460);
		$data->setValue(2, 0, '2006');
		$data->setValue(2, 1, 860);
		$data->setValue(2, 2, 580);
		$data->setValue(3, 0, '2007');
		$data->setValue(3, 1, 1030);
		$data->setValue(3, 2, 540);

		$chart = new f_chart_BarChart($data);
		$chart->setTitle($title);
		$chart->setStacked();
		$chart->setOrientation(f_chart_BarChart::ORIENTATION_HORIZONTAL);
		$chart->setLeftAxis(new f_chart_Axis(new f_chart_Range(0, 2000)));
		break;
}

if ($data !== null)
{
	$table = new f_chart_Table($data);
	$table->setTitle($title);

	$queryString = $chart->getQueryString();
	$url = "http://chart.apis.google.com/chart?".$queryString;
}
?>
<html>
<head></head>
<body>
<ul>
	<li><a href="?chart=1">line chart 1</a></li>
	<li><a href="?chart=2">line chart 2</a></li>
	<li><a href="?chart=3">pie chart</a></li>
	<li><a href="?chart=4">bar chart</a></li>
</ul>
<?php if ($data !== null) { ?>

<h2>Table view</h2>
<?php echo $table->getHTML(); ?>
<form><textarea name="googleCharURL" cols="80" rows="6"
	style="font-size: 12px"><?php echo $url; ?></textarea> <br />
<button
	onclick="document.getElementById('vizualisation').setAttribute('src', this.form.elements['googleCharURL'].value); return false;">View on google</button>
</form>
<h2>On eastwood</h2>

<p>
<img src="http://rd.devlinux:8066/eastwood/chart?<?php echo $queryString; ?>" />
</p>

<h2>On google</h2>
<iframe id="vizualisation" style="width: 600px; height: 400px"></iframe>
<?php } ?>
</body>
</html>