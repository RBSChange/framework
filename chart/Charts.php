<?php
interface f_chart_DataTableProducer
{
	/**
	 * @param array<String, mixed> $params
	 * @return f_chart_DataTable
	 */
	function getDataTable($params = null);
}

class f_chart_Axis
{
	/**
	 * @var f_chart_Range
	 */
	private $range;
	/**
	 * @var f_chart_AxisStyle
	 */
	private $style;

	/**
	 * @param f_chart_Range $range
	 * @param f_chart_AxisStyle $style
	 * @return unknown_type
	 */
	function __construct($range, $style = null)
	{
		$this->range = $range;
		$this->style = $style;
	}

	/**
	 * @return f_chart_Range
	 */
	function getRange()
	{
		return $this->range;
	}

	/**
	 * @return f_chart_AxisStyle
	 */
	function getStyle()
	{
		return $this->style;
	}


}

class f_chart_AxisStyle
{
	const ALIGN_LEFT = -1;
	const ALIGN_CENTERED = 0;
	const ALIGN_RIGHT = 1;

	const DRAW_LINES = "l";
	const DRAW_TICK_MARKS = "t";
	const DRAW_LINES_AND_TICK_MARKS = "lt";

	private $color;
	private $fontSize;
	private $alignement;
	private $drawControl;
	private $tickMarkColor;

	/**
	 * @param String $color
	 * @param Integer $size
	 * @param Integer $alignement {ALIGN_LEFT, ALIGN_CENTERED, ALIGN_RIGHT}
	 * @param String $drawControl {DRAW_LINES, DRAW_TICK_MARKS, DRAW_LINES_AND_TICK_MARKS}
	 * @param String $tickMarkColor
	 */
	function __construct($color, $size = null, $alignement = null, $drawControl = null, $tickMarkColor = null)
	{
		$this->color = $color;
		$this->size = $size;
		$this->alignement = $alignement;
		$this->drawControl = $drawControl;
		$this->tickMarkColor = $tickMarkColor;
	}

	/**
	 * @return String
	 */
	function getColor()
	{
		return $this->color;
	}
	/**
	 * @return Integer
	 */
	function getSize()
	{
		return $this->size;
	}
	/**
	 * @return Integer {ALIGN_LEFT, ALIGN_CENTERED, ALIGN_RIGHT}
	 */
	function getAlignement()
	{
		return $this->alignement;
	}
	/**
	 * @return String {DRAW_LINES, DRAW_TICK_MARKS, DRAW_LINES_AND_TICK_MARKS}
	 */
	function getDrawControl()
	{
		return $this->drawControl;
	}
	/**
	 * @return String
	 */
	function getTickMarkColor()
	{
		return $this->tickMarkColor;
	}
}

class f_chart_Range
{
	/**
	 * @var Float
	 */
	private $start;
	/**
	 * @var Float
	 */
	private $end;
	/**
	 * @var Float
	 */
	private $interval;

	/**
	 * @param Float $start
	 * @param Float $end
	 * @param Float $interval
	 */
	function __construct($start, $end, $interval = null)
	{
		$this->start = $start;
		$this->end = $end;
		$this->interval = $interval;
	}

	/**
	 * @return Float
	 */
	function getStart()
	{
		return $this->start;
	}
	/**
	 * @return Float
	 */
	function getEnd()
	{
		return $this->end;
	}
	/**
	 * @return Float
	 */
	function getInterval()
	{
		return $this->interval;
	}

	function getQueryString($index)
	{
		$q =  $index.",".$this->start.",".$this->end;
		if ($this->interval !== null)
		{
			$q .= ",".$this->interval;
		}
		return $q;
	}
}

class f_chart_DataTable
{
	const STRING_TYPE = 0;
	const NUMBER_TYPE = 1;

	/**
	 * @var array<String, Integer>
	 */
	private $columns;

	private $values;

	/**
	 * @param Integer $type f_chart_DataTable::[STRING|NUMBER]_TYPE
	 * @param String $label
	 * @return void
	 */
	function addColumn($label, $type = self::NUMBER_TYPE, $color = null)
	{
		$this->columns[] = array($label, $type, $color);
	}

	/**
	 * @param Integer $rowCount
	 * @return void
	 */
	function addRows($rowCount)
	{
		$valuesSize = count($this->values);
		for ($i = 0; $i < $rowCount; $i++)
		{
			$this->values[$valuesSize+$i] = array();
		}
	}

	/**
	 * @param Integer $row
	 * @param Integer $col
	 * @param mixed $value String or Float depending on the column type
	 * @return void
	 */
	function setValue($row, $col, $value)
	{
		$this->values[$row][$col] = $value;
	}

	/**
	 * @param $row
	 * @param $values
	 * @return void
	 */
	function setRowValues($row, $values)
	{
		$this->values[$row] = $values;
	}

	/**
	 * @param $row
	 * @param $values
	 * @return unknown_type
	 */
	function setColValues($col, $values, $type = self::NUMBER_TYPE, $label = null, $color = null)
	{
		foreach ($values as $row => $value)
		{
			$this->columns[$col] = array($label, $type, $color);
			$this->values[$col][$row+1] = $values;
		}
	}

	function getValues()
	{
		return $this->values;
	}

	function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @return Integer
	 */
	function getRowCount()
	{
		return count($this->values);
	}

	/**
	 * @return Integer
	 */
	function getColCount()
	{
		return count($this->columns);
	}

	function asString()
	{
		return serialize($this->columns).serialize($this->values);
	}
}

abstract class f_chart_Visualization
{
	/**
	 * @var array<String, mixed>
	 */
	protected $options;

	/**
	 * @var f_chart_DataTable
	 */
	protected $data;

	/**
	 * @param f_chart_DataTable $data
	 * @param array<String, mixed> $options
	 */
	function __construct($data, $options = null)
	{
		$this->data = $data;
		if ($options !== null)
		{
			$this->options = array_merge(self::getDefaultOptions(), $options);
		}
		else
		{
			$this->options = self::getDefaultOptions();
		}
	}

	// protected methods
	abstract static protected function getDefaultOptions();

	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
	}

	public function getOption($name, $defaultValue = null)
	{
		if (isset($this->options[$name]))
		{
			return $this->options[$name];
		}
		return $defaultValue;
	}

	/**
	 * @param String $name
	 * @return Boolean
	 */
	public function hasOption($name)
	{
		return isset($this->options[$name]);
	}

	/**
	 * @return f_chart_DataTable
	 */
	function getDataTable()
	{
		return $this->data;
	}
}

class f_chart_Table extends f_chart_Visualization
{
	private static $defaultOptions;

	/**
	 * @param f_chart_DataTable $data
	 * @param array<String, mixed> $options
	 */
	function __construct($data, $options = null)
	{
		$this->data = $data;
		if ($options !== null)
		{
			$this->options = array_merge(self::getDefaultOptions(), $options);
		}
		else
		{
			$this->options = self::getDefaultOptions();
		}
	}

	function setTitle($title)
	{
		$this->setOption("title", $title);
	}

	function getTitle()
	{
		return $this->getOption("title");
	}

	function getHTML()
	{
		$columns = $this->data->getColumns();
		echo "<table class=\"".$this->options["class"]."\"";
		if (isset($this->options["style"]))
		{
			echo " style=\"".$this->options["style"]."\"";
		}
		echo ">";
		$title = $this->getTitle();
		if ($title !== null)
		{
			echo "<caption>";
			echo nl2br($title);
			echo "</caption>";
		}
		echo "<thead><tr>";
		foreach ($columns as $column)
		{
			echo "<th scope=\"col\" class=\"col\">".$column[0]."</th>";
		}
		echo "</tr></thead>";
		$values = $this->data->getValues();
		$rowCount = count($values);
		$colCount = count($columns);
		echo "<tbody>";
		for ($row = 0; $row < $rowCount; $row++)
		{
			echo "<tr class=\"row-".($row%2)."\">";
			for ($col = 0; $col < $colCount; $col++)
			{
				$colType = $columns[$col][1];
				if ($colType == f_chart_DataTable::NUMBER_TYPE)
				{
					echo "<td>";
					echo $values[$row][$col];
					echo "</td>";
				}
				elseif ($colType == f_chart_DataTable::STRING_TYPE)
				{
					echo "<th scope=\"row\" class=\"row\">";
					echo $values[$row][$col];
					echo "</th>";
				}
			}
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}

	// protected methods
	protected static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			self::$defaultOptions = array("class" => "normal chart");
		}
		return self::$defaultOptions;
	}
}

class f_chart_Grid
{
	private $xAxisStepSize;
	private $yAxisStepSize;
	private $lineSegmentLength = 3;
	private $blankSegmentLength = 2;
	private $xOffset = 0;
	private $yOffset = 0;

	/**
	 * @param Float $xAxisStepSize
	 * @param Float $yAxisStepSize
	 */
	function __construct($xAxisStepSize = 20, $yAxisStepSize = 20)
	{
		$this->xAxisStepSize= $xAxisStepSize;
		$this->yAxisStepSize= $yAxisStepSize;
	}

	/**
	 * @param Float $length
	 * @return f_chart_Grid
	 */
	function setLineSegmentLength($length)
	{
		$this->lineSegmentLength = $length;
		return $this;
	}

	/**
	 * @param Float $length
	 * @return f_chart_Grid
	 */
	function setBlankSegmentLength($length)
	{
		$this->blankSegmentLength = $length;
		return $this;
	}
	/**
	 * @param Float $offset
	 * @return f_chart_Grid
	 */
	function setXOffset($offset)
	{
		$this->xOffset = $offset;
		return $this;
	}
	/**
	 * @param Float $offset
	 * @return f_chart_Grid
	 */
	function setYOffset($offset)
	{
		$this->yOffset = $offset;
		return $this;
	}
	/**
	 * @return String
	 */
	function getQueryString()
	{
		return "&chg=".$this->xAxisStepSize.",".$this->yAxisStepSize.",".
		$this->lineSegmentLength.",".$this->blankSegmentLength.",".
		$this->xOffset.",".$this->yOffset;
	}
}

abstract class f_chart_Chart extends f_chart_Visualization
{
	const LEGEND_RIGHT = 'right';
	const LEGEND_LEFT = 'left';
	const LEGEND_TOP = 'top';
	const LEGEND_BOTTOM = 'bottom';
	const LEGEND_NONE = 'none';

	const LEGEND_ORIENT_VERTICAL = 'vertical';
	const LEGEND_ORIENT_HORIZONTAL = 'horizontal';

	private static $defaultOptions;

	private static $googleChartProvider;

	function setWidth($width)
	{
		$this->setOption("width", $width);
	}

	function getWidth()
	{
		return $this->getOption("width");
	}

	function setHeight($height)
	{
		$this->setOption("height", $height);
	}

	function getHeight()
	{
		return $this->getOptions("height");
	}

	function setLegendOrient($legendOrient)
	{
		$this->setOption("legendOrient", $legendOrient);
	}

	function getLegendOrient()
	{
		return $this->getOption("legendOrient");
	}

	function setLegendPosition($legendPosition)
	{
		$this->setOption("legendPosition", $legendPosition);
	}

	function getLegendPosition()
	{
		return $this->getOption("legendPosition");
	}

	function setTitle($title)
	{
		$this->setOption("title", $title);
	}

	function getTitle()
	{
		return $this->getOption("title");
	}

	function setTitleColor($color)
	{
		$this->setOption("titleColor", $color);
	}

	function getTitleColor()
	{
		return $this->getOption("titleColor");
	}

	function setTitleSize($size)
	{
		$this->setOption("titleSize", $size);
	}

	function getTitleSize()
	{
		$this->getOption("titleSize");
	}

	function setLeftMargin($margin)
	{
		$this->setOption("leftMargin", $margin);
	}

	function setRightMargin($margin)
	{
		$this->setOption("rightMargin", $margin);
	}

	function setTopMargin($margin)
	{
		$this->setOption("topMargin", $margin);
	}

	function setBottomMargin($margin)
	{
		$this->setOption("bottomMargin", $margin);
	}

	function setLegendWidth($width)
	{
		$this->setOption("legendWidth", $width);
	}

	function setLegendHeight($height)
	{
		$this->setOption("legendHeight", $height);
	}

	/**
	 * @param f_chart_Grid $grid
	 */
	function setGrid($grid)
	{
		$this->setOption("grid", $grid);
	}

	function getQueryString()
	{
		$q = "";

		// Size
		$q .= "chs=".$this->getOption("width")."x".$this->getOption("height");

		// Legend position
		if ($this->getLegendOrient() == self::LEGEND_ORIENT_VERTICAL)
		{
			switch ($this->getLegendPosition())
			{
				case self::LEGEND_BOTTOM: $q .= "&chdlp=bv"; break;
				case self::LEGEND_TOP: $q .= "&chdlp=tv"; break;
				case self::LEGEND_RIGHT: $q .= "&chdlp=r"; break;
				case self::LEGEND_LEFT: $q .= "&chdlp=l"; break;
			}
		}
		elseif ($this->getLegendOrient() == self::LEGEND_ORIENT_HORIZONTAL)
		{
			switch ($this->getLegendPosition())
			{
				case self::LEGEND_BOTTOM: $q .= "&chdlp=b"; break;
				case self::LEGEND_TOP: $q .= "&chdlp=t "; break;
			}
		}

		// Title
		$q .= "&chtt=".urlencode(str_replace(array("\r\n", "\n"), "|", $this->getTitle()));
		if ($this->getTitleColor() !== null || $this->getTitleSize() !== null)
		{
			$q .= "&chts=".$this->getTitleColor();
			if ($this->getTitleSize() !== null)
			{
				$q .= ",".$this->getTitleSize();
			}
		}

		// Data. http://code.google.com/apis/chart/formats.html
		$columns = $this->data->getColumns();
		$columnsCount = count($columns);
		$values = $this->data->getValues();
		$rowCount = count($values);

		$q .= "&chd=t:"; // TODO: encode for the better queryString length
		for ($col = 0; $col < $columnsCount; $col++)
		{
			$column = $columns[$col];
			if ($column[1] === f_chart_DataTable::NUMBER_TYPE)
			{
				$rowValues = array();
				for ($row = 0; $row < $rowCount; $row++)
				{
					// TODO: encode for the better queryString length
					$rowValues[] = $values[$row][$col];
				}
				$q .= join(",", $rowValues);
				if ($col+1 < $columnsCount)
				{
					$q .= "|";
				}
			}
		}

		// Margins
		$chma = "&chma=".$this->getOption("leftMargin").",".
		$this->getOption("rightMargin").",".
		$this->getOption("topMargin").",".
		$this->getOption("bottomMargin");
		$legendWidth = $this->getOption("legendWidth");
		$legendHeight = $this->getOption("legendHeight");
		if ($legendWidth !== null || $legendHeight !== null)
		{
			$chma .= "|".$legendWidth.",".$legendHeight;
		}
		$q .= $chma;

		// Grid
		$grid = $this->getOption("grid");
		if ($grid !== null)
		{
			$q .= $grid->getQueryString();
		}

		return $q;
	}

	private static function getGoogleChartProvider()
	{
		if (self::$googleChartProvider === null)
		{
			self::$googleChartProvider = Framework::getConfigurationValue("charts/googleChartProvider");
		}
		return self::$googleChartProvider;
	}

	function getUrl()
	{
		$md5 = md5(self::getGoogleChartProvider().$this->data->asString().serialize($this->options));
		$key = "";
		for ($i = 0; $i < strlen($md5); $i++)
		{
			$key .= $md5[$i];
			if ($i % 2)
			{
				$key .= "/";
			}
		}
		$title = f_util_StringUtils::isEmpty($this->getTitle()) ? "chart" : $this->getTitle();
		$key .= $title.".png";

		$path = f_util_FileUtils::buildWebappPath("www", "cache", "charts", $key);
		$cacheTime = $this->getOption("cacheTime", 0);
		if (!file_exists($path) || (filemtime($path)+$cacheTime) < time())
		{
			f_util_FileUtils::writeAndCreateContainer($path, file_get_contents($this->getDirectUrl()), f_util_FileUtils::OVERRIDE);
		}

		return LinkHelper::getRessourceLink("/cache/charts/".$key)->getUrl();
	}

	function getDirectUrl()
	{
		return self::getGoogleChartProvider()."?".$this->getQueryString();
	}

	// protected methods
	protected static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			self::$defaultOptions = array("width" => "400", "height" => "240",
				"legendPosition" => self::LEGEND_RIGHT, "legendOrient" => self::LEGEND_ORIENT_VERTICAL,
				"leftMargin" => 30, "rightMargin" => 30, "topMargin" => 30, "bottomMargin" => 30);
		}
		return self::$defaultOptions;
	}
}

/**
 * OK, this class has not really sense... just created for code mutualization
 */
abstract class f_chart_2AxisChart extends f_chart_Chart
{
	private static $defaultOptions;
	protected $rotated = false;

	/**
	 * @param f_chart_DataTable $data
	 * @param array<String, mixed> $options
	 */
	function __construct($data, $options = null)
	{
		$this->data = $data;
		if ($options !== null)
		{
			$this->options = array_merge(self::getDefaultOptions(), $options);
		}
		else
		{
			$this->options = self::getDefaultOptions();
		}
	}

	/**
	 * @param f_chart_Axis $axis
	 * @return unknown_type
	 */
	function setBottomAxis($axis)
	{
		$this->setOption("bottomAxis", $axis);
	}

	/**
	 * @return f_chart_Axis
	 */
	function getBottomAxis()
	{
		return $this->getOption("bottomAxis");
	}

	/**
	 * @param f_chart_Axis $axis
	 * @return unknown_type
	 */
	function setLeftAxis($axis)
	{
		$this->setOption("leftAxis", $axis);
	}

	/**
	 * @return f_chart_Axis
	 */
	function getLeftAxis()
	{
		$leftAxis = $this->getOption("leftAxis");
		if ($leftAxis === null)
		{
			list($min, $max) = $this->getMinMax();
			$min -= abs($min * 0.1);
			$max += abs($max * 0.1);
			$leftAxis = new f_chart_Axis(new f_chart_Range($min, $max));
			$this->setOption("leftAxis", $leftAxis);
		}
		return $leftAxis;
	}

	protected function getMinMax()
	{
		$values = $this->data->getValues();
		$columns = $this->data->getColumns();
		$rowCount = $this->data->getRowCount();
		$min = null;
		$max = null;
		for ($col = 0; $col < $this->data->getColCount(); $col++)
		{
			$column = $columns[$col];
			if ($column[1] === f_chart_DataTable::NUMBER_TYPE)
			{
				$this->getColMinMax($col, $values, $rowCount, $min, $max);
			}
		}
		return array($min, $max);
	}

	protected function getColMinMax($col, $values, $rowCount, &$min, &$max)
	{
		for ($row = 0; $row < $rowCount; $row++)
		{
			$value = $values[$row][$col];
			if ($min === null)
			{
				$min = $value;
				$max = $value;
			}
			else
			{
				if ($min > $value)
				{
					$min = $value;
				}
				if ($max < $value)
				{
					$max = $value;
				}
			}
		}
	}

	function getQueryString()
	{
		$q = parent::getQueryString();

		$columns = $this->data->getColumns();
		$columnsCount = count($columns);
		$values = $this->data->getValues();
		$rowCount = count($values);

		// Legend values = dataset labels
		// + Legend colors
		$labels = array();
		$colors = array();
		for ($col = 0; $col < $columnsCount; $col++)
		{
			$column = $columns[$col];
			if ($column[1] == f_chart_DataTable::NUMBER_TYPE)
			{
				$labels[] = urlencode($column[0]);
				if (isset($column[2]))
				{
					$colors[] = $column[2];
				}
			}
		}
		if (!empty($labels))
		{
			$q .= "&chdl=".join("|", $labels);
		}
		if (!empty($colors))
		{
			$q .= "&chco=".join(",", $colors);
		}

		// Axis
		if ($this->rotated)
		{
			$chxt = "&chxt=y,x";
		}
		else
		{
			$chxt = "&chxt=x,y";
		}

		// Ranges labels. http://code.google.com/apis/chart/labels.html#axis_range
		$chds = "&chds=";
		$chxr = "&chxr=";

		if ($columns[0][1] === f_chart_DataTable::STRING_TYPE)
		{
			$chxl = "&chxl=0:|";
			$labels = array();
			for ($row = 0; $row < $rowCount; $row++)
			{
				$labels[] = urlencode($values[$row][0]);
			}
			if ($this->rotated)
			{
				$labels = array_reverse($labels);
			}
			$chxl .= join("|", $labels);
			$q .= $chxl."|2:|".$columns[0][0];
			if ($this->rotated)
			{
				$chxt .= ",y";
			}
			else
			{
				$chxt .= ",x";
			}

			$q .= "&chxp=2,50";
		}
		else
		{
			$range = $this->getBottomAxis()->getRange();
			$chxr .= $range->getQueryString(0);
			$chxr .= "|";
		}

		$leftAxis = $this->getLeftAxis();
		if ($leftAxis !== null)
		{
			$range = $leftAxis->getRange();
			$chxr .= $range->getQueryString(1);
			$chds .= $range->getStart().",".$range->getEnd();
		}

		// Range scale. http://code.google.com/apis/chart/formats.html#scaled_values
		$q .= $chds;
		$q .= $chxr;
		$q .= $chxt;

		return $q;
	}

	// protected methods
	protected static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			self::$defaultOptions = array_merge(parent::getDefaultOptions(), array());
		}
		return self::$defaultOptions;
	}
}

class f_chart_LineChart extends f_chart_2AxisChart
{
	function getQueryString()
	{
		$q = parent::getQueryString();
		// Type
		$q .= "&cht=lc";
		return $q;
	}
}

class f_chart_PieChart extends f_chart_Chart
{
	private static $defaultOptions;

	/**
	 * @param f_chart_DataTable $data
	 * @param array<String, mixed> $options
	 */
	function __construct($data, $options = null)
	{
		$this->data = $data;
		if ($options !== null)
		{
			$this->options = array_merge(self::getDefaultOptions(), $options);
		}
		else
		{
			$this->options = self::getDefaultOptions();
		}
	}

	function set3d()
	{
		$this->setOption("3d", true);
	}

	function setOrientation($angleInRadian)
	{
		$this->setOption("orientation", $angleInRadion);
	}

	function setMasterColor($color)
	{
		$this->setOption("masterColor", $color);
	}

	function setColors($colors)
	{
		$this->setOption("colors", $color);
	}

	function getQueryString()
	{
		$q = parent::getQueryString();
		if ($this->hasOption("3d"))
		{
			$q .= "&cht=p3";
		}
		else
		{
			$q .= "&cht=p";
		}

		$orientation = $this->getOption("orientation");
		if ($orientation !== null)
		{
			$q .= "&chp=".$orientation;
		}

		$columns = $this->data->getColumns();
		$rowCount = $this->data->getRowCount();
		$values = $this->data->getValues();
		if ($columns[0][1] === f_chart_DataTable::STRING_TYPE)
		{
			$chl = "&chl=";
			$labels = array();
			for ($row = 0; $row < $rowCount; $row++)
			{
				$labels[] = urlencode($values[$row][0]);
			}
			$chl .= join("|", $labels);
			$q .= $chl;
		}

		$colors = $this->getOption("colors", $this->getOption("masterColor"));
		if ($colors !== null)
		{
			$q .= "&chco=".$colors;
		}

		return $q;
	}

	// protected methods
	protected static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			self::$defaultOptions = array_merge(parent::getDefaultOptions(), array());
		}
		return self::$defaultOptions;
	}
}

class f_chart_BarChart extends f_chart_2AxisChart
{
	const ORIENTATION_VERTICAL = 1;
	const ORIENTATION_HORIZONTAL = 2;

	const BAR_WIDTH_AUTO = "a";
	const BAR_WIDTH_RELATIVE = "r";

	private static $defaultOptions;

	/**
	 * @param f_chart_DataTable $data
	 * @param array<String, mixed> $options
	 */
	function __construct($data, $options = null)
	{
		$this->data = $data;
		if ($options !== null)
		{
			$this->options = array_merge(self::getDefaultOptions(), $options);
		}
		else
		{
			$this->options = self::getDefaultOptions();
		}
	}

	function setStacked()
	{
		$this->setOption("stacked", true);
	}

	function setOrientation($orientation)
	{
		$this->setOption("orientation", $orientation);
		$this->rotated = $orientation === self::ORIENTATION_HORIZONTAL;
	}

	/**
	 * @param $width
	 * @return unknown_type
	 */
	function setBarWidth($width)
	{
		$this->setOption("barWidth", $width);
	}

	function setBarSpace($space)
	{
		$this->setOption("barSpace", $width);
	}

	function setGroupSpace($space)
	{
		$this->setOption("groupSpace", $width);
	}

	function getQueryString()
	{
		$q = parent::getQueryString();

		// Type
		$orientation = $this->getOption("orientation");
		$cht = "&cht=b";
		$cht .= ($orientation === self::ORIENTATION_HORIZONTAL) ? "h" : "v";
		$cht .= ($this->getOption("stacked")) ? "s" : "g";
		$q .= $cht;

		// Bar width & spacing
		$barWidth = $this->getOption("barWidth");
		if ($barWidth !== null)
		{
			$chbh = "&chbh=".$barWidth;
			$barSpace = $this->getOption("barSpace");
			$groupSpace = $this->getOption("groupSpace");
			if ($barSpace !== null || $groupSpace !== null)
			{
				$chbh .= ",".$barSpace.",".$groupSpace;
			}
			$q .= $chbh;
		}

		return $q;
	}

	/**
	 * @return f_chart_Axis
	 */
	function getLeftAxis()
	{
		$leftAxis = $this->getOption("leftAxis");
		if ($leftAxis === null)
		{
			list($min, $max) = $this->getMinMax();
			$min -= abs($min * 0.1);
			$max += abs($max * 0.1);
			$leftAxis = new f_chart_Axis(new f_chart_Range($min, $max));
			$this->setOption("leftAxis", $leftAxis);
		}
		return $leftAxis;
	}

	protected function getMinMax()
	{
		if (!$this->getOption("stacked"))
		{
			list($min, $max) = parent::getMinMax();
		}
		else
		{
			$values = $this->data->getValues();
			$columns = $this->data->getColumns();
			$rowCount = $this->data->getRowCount();
			$min = 0;
			$max = 0;
			for ($col = 0; $col < $this->data->getColCount(); $col++)
			{
				$column = $columns[$col];
				if ($column[1] === f_chart_DataTable::NUMBER_TYPE)
				{
					$colMin = $colMax = null;
					$this->getColMinMax($col, $values, $rowCount, $colMin, $colMax);
					$min += $colMin;
					$max += $colMax;
				}
			}

		}
		if ($min > 0) $min = 0;
		return array($min, $max);
	}

	// protected methods
	protected static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			self::$defaultOptions = array_merge(parent::getDefaultOptions(),
			array(
			 	"orientation" => self::ORIENTATION_VERTICAL,
			 	"stacked" => false,
			 	"barWidth" => self::BAR_WIDTH_AUTO, 
			 	"barSpace" => 4, "groupSpace" => 8));
		}
		return self::$defaultOptions;
	}
}