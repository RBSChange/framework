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
	 * @return String
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * Set the label of the paginator link
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
	 * @return String
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Set the url of the paginator link
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
	 * The page index parameter name
	 */
	const PAGEINDEX_PARAMETER_NAME = 'page';

	/**
	 * paginator_PaginatorItem[]
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
	 * @var string
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
	private $pageCount = null;

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

	/**
	 * @param string $moduleName
	 * @param integer $pageIndex
	 * @param mixed[] $items
	 * @param integer $nbItemPerPage
	 */
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
	 * @param Array<String, Mixed> $value
	 */
	public function setExtraParameters($value)
	{
		$this->extraParameters = $value;
	}

	/**
	 * Returns the request's extra parameters, besides page.
	 * @return Array<String, Mixed>
	 */
	public function getExtraParameters()
	{
		return $this->extraParameters;
	}
	
	/**
	 * @return paginator_PaginatorItem[]
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
	 * @param String $anchor the anchor to add to each paginator URL
	 */
	public function setAnchor($anchor)
	{
		$this->anchor = $anchor;
	}

	/**
	 * @var f_web_ParametrizedLink
	 */
	private $parametrizedLink;
	
	/**
	 * @param integer $pageIndex
	 * @return string
	 */
	private function getUrlForPage($pageIndex)
	{
		$key = $this->getModuleName().'Param';
		if ($this->parametrizedLink === null)
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
			$this->setBaseUrl($requestUri);
		}
		$this->parametrizedLink->setQueryParameter($key, array($this->pageIndexParamName => $pageIndex > 1 ? $pageIndex : null));
		$this->parametrizedLink->setFragment($this->anchor);
		return $this->parametrizedLink->getUrl();
	}
	
	/**
	 * @param string $baseUrl
	 */
	public function setBaseUrl($baseUrl)
	{
		$rq = RequestContext::getInstance();
		$parts = explode('?', $baseUrl);
		$this->parametrizedLink = new f_web_ParametrizedLink($rq->getProtocol(), $_SERVER['SERVER_NAME'], $parts[0]);
		if (isset($parts[1]) && $parts[1] != '')
		{
			parse_str($parts[1], $queryParameters);
			foreach ($queryParameters as $name => $value)
			{
				if (!in_array($name, $this->excludeParameters))
				{
					$this->parametrizedLink->setQueryParameter($name, $value);
				}
			}
		}		
		if (is_array($this->extraParameters) && count($this->extraParameters))
		{
			foreach ($this->extraParameters as $name => $value) 
			{
				if (!in_array($name, $this->excludeParameters))
				{
					$this->parametrizedLink->setQueryParameter($name, $value);
				}
			}
		}
	}
	
	/**
	 * @var string[]
	 */
	private $excludeParameters = array();
	
	/**
	 * @param string $baseUrl
	 */
	public function setExcludeParameters($paramNames)
	{
		$this->excludeParameters = $paramNames;
	}

	/**
	 * Returns the "Page 1 sur XX" text
	 * @return String
	 */
	public function getLocalizedPageCount()
	{
		return LocaleService::getInstance()->transFO('m.website.paginator.detail', array('ucf'), array('currentPage' => $this->getCurrentPageNumber(), 'pageCount' => $this->getPageCount(), 'listName' => $this->getListName()));
	}

	/**
	 * @return paginator_PaginatorItem
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
	 * @return paginator_PaginatorItem
	 */
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
	
	/**
	 * @return paginator_PaginatorItem
	 */
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

	/**
	 * @return paginator_PaginatorItem
	 */
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

	/**
	 * @param string $fileName
	 */
	public final function setTemplateFileName($fileName)
	{
		$this->html = null;
		$this->templateFileName = $fileName;
	}

	/**
	 * @param string $moduleName
	 */
	public final function setTemplateModuleName($moduleName)
	{
		$this->html = null;
		$this->templateModuleName = $moduleName;
	}

	/**
	 * @return TemplateObject
	 */
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
	
	/**
	 * @return string
	 */
	public function getListName()
	{
		return $this->listName;
	}
	
	/**
	 * @return string
	 */
	function setListName($listName)
	{
		$this->listName = $listName;
	}

	private function load()
	{
		if ($this->items === null)
		{
			$this->buildItems();
		}
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
}