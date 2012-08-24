<?php
class paginator_PaginatorItem
{
	/**
	 * @var string
	 */
	private $label;
	
	/**
	 * @var string
	 */
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
	
	/**
	 * @var array
	 */
	private $items = null;
	
	/**
	 * @var integer
	 */
	private $currentItemIndex = 0;
	
	/**
	 * @var string
	 */	
	private $templateFileName = 'Website-Default-Paginator';
	
	/**
	 * @var string
	 */	
	private $templateModuleName = 'website';
	
	/**
	 * @var integer
	 */
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
	private $pageCount = 1;
	
	/**
	 * Name of the module that use the paginator
	 * @var string
	 */
	private $moduleName = null;
	
	/**
	 * @var array
	 */
	private $extraParameters = array();
	
	/**
	 * @var integer
	 */
	private $nbItemPerPage;
	
	/**
	 * @var string
	 */
	private $html = null;
	
	/**
	 * @var string
	 */
	private $listName;
	
	/**
	 * @var array
	 */
	private $excludeParams = array();
	
	/**
	 * @param string $moduleName
	 * @param integer $pageIndex
	 * @param array $items
	 * @param integer $nbItemPerPage
	 * @param integer $itemCount
	 * @param array $excludeParams
	 * @throws BadInitializationException
	 */
	public function __construct($moduleName, $pageIndex, $items, $nbItemPerPage, $itemCount = null, $excludeParams = array())
	{
		$this->setModuleName($moduleName);
		$this->setCurrentPageNumber($pageIndex);
		$this->nbItemPerPage = $nbItemPerPage;
		
		if ($itemCount != null)
		{
			$this->setItemCount($itemCount);
		}
		
		$this->excludeParams = $excludeParams;
		
		if ($items !== null)
		{
			if ($items instanceof ArrayObject)
			{
				$itemsArray = $items->getArrayCopy();
			}
			else
			{
				$itemsArray = $items;
			}
			
			$count = count($items);
			
			if ($count > $nbItemPerPage)
			{
				Framework::warn(__METHOD__ . " - Only page items must be sent ($nbItemPerPage), don't send the full items array ($count)");
				
				if ($itemCount != null && $count != $itemCount)
				{
					throw new BadInitializationException("itemCount($itemCount) is different than calculate count($count) of items.");
				}
				
				$this->setItemCount($count);
				$itemsArray = array_slice($itemsArray, ($pageIndex - 1) * $nbItemPerPage, $nbItemPerPage);
			}
			
			parent::__construct($itemsArray);
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
		$this->setPageCount((int) ceil($itemCount / $this->nbItemPerPage));
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
	
	/**
	 * @return array
	 */
	public function getItems()
	{
		$this->load();
		return $this->items;
	}
	
	/**
	 * @return boolean
	 */
	public function shouldRender()
	{
		return $this->getPageCount() > 1;
	}
	
	/**
	 * @var string
	 */
	private $anchor;
	
	/**
	 * @param string $anchor the anchor to add to each paginator URL
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
			$minPage = max(1, $currentPageIndex - 2);
			// Maximum page index to display
			$maxPage = min($pageCount, $currentPageIndex + 2);
			// If the Maximum page index to display is equal to the last page, try to display the last 5 pages
			if ($maxPage == $pageCount)
			{
				$minPage = max(1, $maxPage - 4);
			}
			// If the Maximum page index to display is smaller than 5 to the last page, try to display the first 5 pages
			else if ($maxPage < 5)
			{
				$maxPage = min(5, $pageCount);
			}
			
			for ($p = $minPage; $p <= $maxPage; $p++)
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
	
	/**
	 * @var f_web_ParametrizedLink
	 */
	private $currentUrl;
	
	/**
	 * @param index $pageIndex
	 * @return string
	 */
	private function getUrlForPage($pageIndex)
	{
		$key = $this->getModuleName() . 'Param';
		if ($this->currentUrl === null)
		{
			$rq = RequestContext::getInstance();
			if ($rq->getAjaxMode())
			{
				$requestUri = $rq->getAjaxFromURI();
			}
			else
			{
				$requestUri = $rq->getPathURI();
			}
			$parts = explode('?', $requestUri);
			$this->currentUrl = new f_web_ParametrizedLink($rq->getProtocol(), $_SERVER['SERVER_NAME'], $parts[0]);
			
			$params = array();
			if (is_array($this->extraParameters) && count($this->extraParameters))
			{
				$params = $this->extraParameters;
			}
			
			if (isset($parts[1]) && $parts[1] != '')
			{
				parse_str($parts[1], $queryParameters);
				$params = array_merge($params, $queryParameters);
			}
			
			if (count($this->excludeParams) > 0)
			{
				parse_str(implode('&', $this->excludeParams), $excludeParameters);
				$this->doExcludeParams($params, $excludeParameters);
			}
			
			$this->currentUrl->setQueryParameters($params);
		}
		$this->currentUrl->setQueryParameter($key, array($this->pageIndexParamName => $pageIndex > 1 ? $pageIndex : null));
		$this->currentUrl->setFragment($this->anchor);
		return $this->currentUrl->getUrl();
	}
	
	/**
	 * @param array $params
	 * @param array $excludeParameters
	 */
	private function doExcludeParams(&$params, $excludeParameters)
	{
		foreach ($excludeParameters as $key => $value)
		{
			if (is_array($value))
			{
				$this->doExcludeParams($params[$key], $value);
			}
			else
			{
				unset($params[$key]);
			}
		}
	}
	
	/**
	 * Returns the "Page 1 sur XX" text
	 *
	 * @return string
	 */
	public function getLocalizedPageCount()
	{
		return LocaleService::getInstance()->transFO('m.website.paginator.detail', array('ucf'), array(
			'currentPage' => $this->getCurrentPageNumber(), 'pageCount' => $this->getPageCount(), 'listName' => $this->getListName()));
	}
	
	/**
	 * @return paginator_PaginatorItem|null
	 */
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
	
	/**
	 * @return paginator_PaginatorItem|null
	 */
	public function getPreviousPageItem()
	{
		$this->load();
		if ($this->currentItemIndex > 0)
		{
			$items = $this->getItems();
			return $items[$this->currentItemIndex - 1];
		}
		return null;
	
	}
	
	/**
	 * @return paginator_PaginatorItem|null
	 */
	public function getLastPageItem()
	{
		$this->load();
		$p = $this->getPageCount();
		if ($this->getCurrentPageNumber() == $p || $p == 0)
		{
			return null;
		}
		$newItem = new paginator_PaginatorItem();
		$newItem->setLabel($p)->setUrl($this->getUrlForPage($p));
		$newItem->isCurrent = $p == $this->getPageCount();
		return $newItem;
	}
	
	/**
	 * @return paginator_PaginatorItem|null
	 */
	public function getNextPageItem()
	{
		$this->load();
		if ($this->getCurrentPageNumber() < $this->getPageCount())
		{
			$items = $this->getItems();
			return $items[$this->currentItemIndex + 1];
		}
		return null;
	}
	
	/**
	 * @param string $string
	 */
	public final function setTemplateFileName($string)
	{
		$this->html = null;
		$this->templateFileName = $string;
	}
	
	/**
	 * @param string $string
	 */
	public final function setTemplateModuleName($string)
	{
		$this->html = null;
		$this->templateModuleName = $string;
	}
	
	/**
	 * @return string 
	 */
	public final function getTemplate()
	{
		$loader = TemplateLoader::getInstance()->setPackageName('modules_' . $this->templateModuleName)->setDirectory('templates')->setMimeContentType('html');
		$template = $loader->load($this->templateFileName);
		$template->setAttribute('paginator', $this);
		return $template;
	}
	
	/**
	 * @return string
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
	
	/**
	 * @return string 
	 */
	function getListName()
	{
		return $this->listName;
	}
	
	/**
	 * @param string $listName
	 */
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
/**
 * @deprecated
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
	
	/**
	 * @param string $name
	 */
	public function removeQueryParameter($name)
	{
		$key = urlencode($name);
		if (isset($this->urlRequestParts[$key]))
		{
			unset($this->urlRequestParts[$key]);
			$this->setNeedsUpdate();
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setQueryParameter($name, $value)
	{
		if (!is_array($value))
		{
			$key = urlencode($name);
			$this->urlRequestParts[$key] = $key . "=" . urlencode($value);
		}
		else
		{
			$this->buildRecursivelyWithKeyAndValue($name, $value);
		}
		$this->setNeedsUpdate();
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setRequestPart($key, $value)
	{
		$this->urlRequestParts[$key] = $value;
		$this->setNeedsUpdate();
	}
	
	/**
	 * @param array $array
	 */
	public function setQueryParameters($array)
	{
		foreach ($array as $key => $val)
		{
			$this->buildRecursivelyWithKeyAndValue($key, $val);
		}
		$this->setNeedsUpdate();
	}
	
	/**
	 * @param string $url
	 */
	public function setBaseUrl($url)
	{
		$this->baseUrl = $url;
		$this->setNeedsUpdate();
	}
	
	/**
	 * @return string
	 */
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
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getStringRepresentation();
	}
	
	private $currentPath = array();
	private $urlRequestParts = array();
	private $stringRepresentation = null;
	private $baseUrl = null;
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
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
				$this->urlRequestParts[$path] = $path . "=" . urlencode($value);
			}
		}
		array_pop($this->currentPath);
	}
	
	private function setNeedsUpdate()
	{
		$this->stringRepresentation = null;
	}
}