<?php
class paginator_PaginatorItem
{
	private $label;
	private $url;
	
	/**
	 * @var Boolean
	 */
	public $isCurrent = false;

	/**
	 * Get the label of the paginator link
	 *
	 * @return String
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * Set the label of the paginator link
	 *
	 * @param String
	 * @return paginator_PaginatorItem
	 */
	public function setLabel($value)
	{
		$this->label = $value;
		return $this;
	}

	/**
	 * Get the url of the paginator link
	 *
	 * @return String
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Set the url of the paginator link
	 *
	 * @param String
	 * @return paginator_PaginatorItem
	 */
	public function setUrl($value)
	{
		$this->url = $value;
		return $this;
	}
}

class paginator_Paginator extends ArrayObject
{
	/**
	 * @deprecated (will be removed in 4.0) use PAGEINDEX_PARAMETER_NAME
	 */
	const REQUEST_PARAMETER_NAME = 'page';
	
	/**
	 * The page index parameter name
	 */
	const PAGEINDEX_PARAMETER_NAME = 'page';

	private $items = null;
	private $currentItemIndex = 0;
	private $templateFileName = 'Website-Default-Paginator';
	private $templateModuleName = 'website';
	
	private $pageIndexParamName = self::PAGEINDEX_PARAMETER_NAME;
	/**
	 * Index of the current page of paginator
	 * @var integer
	 */
	private $currentPageNumber = null;

	/**
	 * Count of pages for the paginator
	 * @var integer
	 */
	private $pageCount = null;

	/**
	 * Name of the module that use the paginator
	 * @var string
	 */
	private $moduleName = null;

	private $currentUrl = null;

	private $extraParameters = array();
	/**
	 * @var Integer
	 */
	private $nbItemPerPage;
	
	/**
	 * @var String
	 */
	private $html = null;
	
	/**
	 * @var String
	 */
	private $listName;

	public function __construct($moduleName, $pageIndex, $items, $nbItemPerPage)
	{
		$this->setModuleName($moduleName);
		$this->setCurrentPageNumber($pageIndex);
		$this->nbItemPerPage = $nbItemPerPage;
		if ($items !== null)
		{
			$itemCount = count($items);
			$this->setItemCount($itemCount);
			if ($items instanceof ArrayObject)
			{
				$itemsArray = $items->getArrayCopy();
			}
			else
			{
				$itemsArray = $items;
			}
			if ($itemCount > $nbItemPerPage)
			{
				parent::__construct(array_slice($itemsArray, ($pageIndex - 1) * $nbItemPerPage, $nbItemPerPage));
			}	
			else
			{
				parent::__construct($itemsArray);
			}
		}
	}
	
	/**
	 * @param String $pageIndexParameterName
	 */
	public function setPageIndexParameterName($pageIndexParameterName)
	{
		$this->pageIndexParamName = $pageIndexParameterName;
	}
	
	/**
	 * @return String
	 */
	public function getPageIndexParameterName()
	{
		return $this->pageIndexParamName;
	}

	/**
	 * @param Integer $itemCount
	 */
	public function setItemCount($itemCount)
	{
		$this->setPageCount((int)ceil($itemCount / $this->nbItemPerPage));
	}

	/**
	 * @param integer $value
	 * @return paginator_Paginator
	 */
	public function setCurrentPageNumber($value)
	{
		$this->currentPageNumber = $value;
		return $this;
	}

	/**
	 * @param integer $value
	 * @return paginator_Paginator
	 */
	public function setPageCount($value)
	{
		$this->pageCount = $value;
		return $this;
	}

	/**
	 * @param string $value
	 * @return paginator_Paginator
	 */
	public function setModuleName($value)
	{
		$this->moduleName = $value;
		return $this;
	}

	/**
	 * @return mixed integer or null
	 */
	public function getCurrentPageNumber()
	{
		return $this->currentPageNumber;
	}

	/**
	 * @return mixed integer or null
	 */
	public function getPageCount()
	{
		return $this->pageCount;
	}

	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/**
	 * Set the request's extra parameters, besides page.
	 *
	 * @param Array<String, Mixed> $value
	 */
	public function setExtraParameters($value)
	{
		$this->extraParameters = $value;
	}

	/**
	 * Returns the request's extra parameters, besides page.
	 *
	 * @return Array<String, Mixed>
	 */
	public function getExtraParameters()
	{
		return $this->extraParameters;
	}

	public function getItems()
	{
		$this->load();
		return $this->items;
	}

	public function shouldRender()
	{
		return $this->getPageCount() > 1;
	}
	
	private $anchor;
	/**
	 * @param String $anchor the anchor to add to each paginator URL
	 */
	public function setAnchor($anchor)
	{
		$this->anchor = $anchor;
	}

	private function buildItems()
	{
		$pageCount = $this->getPageCount();
		if ($pageCount > 1)
		{
			$currentPageIndex = $this->getCurrentPageNumber();
			// Minimum page index to display
			$minPage = max(1, $currentPageIndex-2);
			// Maximum page index to display
			$maxPage = min($pageCount, $currentPageIndex+2);
			// If the Maximum page index to display is equal to the last page, try to display the last 5 pages
			if ($maxPage == $pageCount)
			{
				$minPage = max(1, $maxPage-4);
			}
			// If the Maximum page index to display is smaller than 5 to the last page, try to display the first 5 pages
			else if ($maxPage < 5)
			{
				$maxPage = min(5, $pageCount);
			}

			for ($p = $minPage  ; $p <= $maxPage ; $p++)
			{
				$newItem = new paginator_PaginatorItem();
				$newItem->setLabel($p)->setUrl($this->getUrlForPage($p));
				$newItem->isCurrent = $p == $currentPageIndex;
				if ($newItem->isCurrent)
				{
					$this->currentItemIndex = count($this->items);
				}
				$this->items[] = $newItem;
			}
		}
		else
		{
			$this->items = array();
		}
	}

	private function getUrlForPage($pageIndex)
	{
		$key = $this->getModuleName().'Param';
		if (is_null($this->currentUrl))
		{
			$this->currentUrl  = paginator_Url::getInstanceFromCurrentUrl();
			$this->currentUrl->setQueryParameter($key, $this->extraParameters);
		}
		if ($pageIndex > 1)
		{
			$this->currentUrl->setQueryParameter($key.'['.$this->pageIndexParamName.']', $pageIndex);
		}
		else 
		{
			$this->currentUrl->removeQueryParameter($key.'['.$this->pageIndexParamName.']');
		}
		return $this->currentUrl->getStringRepresentation() . (($this->anchor) ? '#'.$this->anchor : '');
	}

	/**
	 * Returns the "Page 1 sur XX" text
	 *
	 * @return String
	 */
	public function getLocalizedPageCount()
	{
		return LocaleService::getInstance()->transFO('m.website.paginator.detail', array('ucf'), array('currentPage' => $this->getCurrentPageNumber(), 'pageCount' => $this->getPageCount(), 'listName' => $this->getListName()));
	}

	public function getFirstPageItem()
	{
		$this->load();
		if ($this->currentItemIndex == 0)
		{
			return null;
		}
		$newItem = new paginator_PaginatorItem();
		$newItem->setLabel(1)->setUrl($this->getUrlForPage(1));
		$newItem->isCurrent = 0 == $this->getPageCount();
		return $newItem;
	}

	public function getPreviousPageItem()
	{
		$this->load();
		if ($this->currentItemIndex > 0)
		{
			$items = $this->getItems();
			return $items[$this->currentItemIndex-1];
		}
		return null;

	}

	public function getLastPageItem()
	{
		$this->load();
		$p = $this->getPageCount();
		if ( $this->getCurrentPageNumber() == $p || $p == 0)
		{
			return null;
		}
		$newItem = new paginator_PaginatorItem();
		$newItem->setLabel($p)->setUrl($this->getUrlForPage($p));
		$newItem->isCurrent = $p == $this->getPageCount();
		return $newItem;
	}

	public function getNextPageItem()
	{
		$this->load();
		if ($this->getCurrentPageNumber() < $this->getPageCount())
		{
			$items = $this->getItems();
			return $items[$this->currentItemIndex+1];
		}
		return null;
	}

	public final function setTemplateFileName($string)
	{
		$this->html = null;
		$this->templateFileName = $string;
	}

	public final function setTemplateModuleName($string)
	{
		$this->html = null;
		$this->templateModuleName = $string;
	}

	public final function getTemplate()
	{
		$loader = TemplateLoader::getInstance()->setPackageName('modules_' . $this->templateModuleName)->setDirectory('templates')->setMimeContentType('html');
		$template = $loader->load($this->templateFileName);
		$template->setAttribute('paginator', $this);
		return $template;
	}
	
	/**
	 * @return String
	 */
	public final function execute()
	{
		if ($this->html !== null)
		{
			return $this->html;
		}
		$this->html = $this->getTemplate()->execute();
		return $this->html;
	}
	
	function getListName()
	{
		return $this->listName;
	}
	
	function setListName($listName)
	{
		$this->listName = $listName;
	}

	private function load()
	{
		if (is_null($this->items))
		{
			$this->buildItems();
		}
	}
}

/**
 * Small url class utility
 */
class paginator_Url
{
	/**
	 * Returns an instance of paginator_Url initialized with the current Url. If the current
	 * url is not set and empty string is used.
	 *
	 * @return paginator_Url
	 */
	public static function getInstanceFromCurrentUrl()
	{
		$inst = new paginator_Url();
		$rq = RequestContext::getInstance();
		if ($rq->getAjaxMode())
		{
			$requestUri = $rq->getAjaxFromURI();
		}
		else
		{
			$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
		}
		$index = strpos($requestUri, '?');
		$baseUrl = $index ? substr($requestUri, 0, $index) : $requestUri;
		$inst->baseUrl = $baseUrl;
		$inst->setQueryParameters($_GET);
		return $inst;
	}

	public function removeQueryParameter($name)
	{
		$key = urlencode($name);
		if (isset($this->urlRequestParts[$key]))
		{
			unset($this->urlRequestParts[$key]);
			$this->setNeedsUpdate();
		}
	}

	public function setQueryParameter($name, $value)
	{
		if (!is_array($value))
		{
			$key = urlencode($name);
			$this->urlRequestParts[$key] =  $key. "=" . urlencode($value);
		}
		else
		{
			$this->buildRecursivelyWithKeyAndValue($name, $value);
		}
		$this->setNeedsUpdate();
	}

	public function setRequestPart($key, $value)
	{
		$this->urlRequestParts[$key] = $value;
		$this->setNeedsUpdate();
	}

	public function setQueryParameters($array)
	{
		foreach ($array as $key => $val)
		{
			$this->buildRecursivelyWithKeyAndValue($key, $val);
		}
		$this->setNeedsUpdate();
	}

	public function setBaseUrl($url)
	{
		$this->baseUrl = $url;
		$this->setNeedsUpdate();
	}

	public function getStringRepresentation()
	{
		if (is_null($this->stringRepresentation))
		{
			$this->stringRepresentation = $this->baseUrl;
			if (count($this->urlRequestParts) > 0)
			{
				$this->stringRepresentation .= '?' . implode('&', $this->urlRequestParts);
			}
		}
		return $this->stringRepresentation;
	}

	public function __toString()
	{
		return $this->getStringRepresentation();
	}

	private $currentPath = array();
	private $urlRequestParts = array();
	private $stringRepresentation = null;
	private $baseUrl = null;

	private function buildRecursivelyWithKeyAndValue($name, $value)
	{
		$this->currentPath[] = urlencode(count($this->currentPath) == 0 ? $name : "[$name]");
		if (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$this->buildRecursivelyWithKeyAndValue($k, $v);
			}
		}
		else
		{
			$path = implode($this->currentPath);
			if ($value === null)
			{
				if (isset($this->urlRequestParts[$path]))
				{
					unset($this->urlRequestParts[$path]);
				}
			}
			else
			{
				$this->urlRequestParts[$path] =  $path. "=" . urlencode($value);
			}
		}
		array_pop($this->currentPath);
	}

	private function setNeedsUpdate()
	{
		$this->stringRepresentation = null;
	}
}