<?php
/**
 * @package framework.exception.renderers
 */
class exception_HtmlRenderer extends exception_Renderer
{
	
	public function __construct()
	{
		$this->contentType = 'text/html';
	}
	
	
	public final function getStackTraceContents(Exception $exception)
	{
		$traceData = $exception->getTrace();

		$code	= ($exception->getCode() > 0) ? $exception->getCode() : 'N/A';
		$file	= ($exception->getFile() != null) ? $exception->getFile() : 'N/A';
		$line	= ($exception->getLine() != null) ? $exception->getLine() : 'N/A';
		$message = ($exception->getMessage() != null) ? $exception->getMessage() : 'N/A';
		$class   = (isset($traceData[0]["class"])) ? $traceData[0]["class"] : 'N/A';

		$trace   = array();
		if (Framework::inDevelopmentMode())
		{
			// format the stack trace
			for ($i = 0, $z = count($traceData); $i < $z; $i++)
			{
				if (!isset($traceData[$i]['file']))
				{
					// no file key exists, skip this index
					continue;
				}
				
				$tClass   = (isset($traceData[$i]["class"])) ? $traceData[$i]["class"] : null;
				$tFile	  = $traceData[$i]['file'];
				$tFunction  = $traceData[$i]['function'];
				$tLine	  = $traceData[$i]['line'];

				if ($tClass != null)
				{
					$tFunction = $tClass . '::' . $tFunction . '()';
				}
				else
				{
					$tFunction = $tFunction . '()';
				}

				$data = 'at %s in [%s:%s]';
				$data = sprintf($data, $tFunction, $tFile, $tLine);

				$trace[] = $data;
			}
		}
		else if ($file !== 'N/A')
		{
			$file = basename($file);
		}
		
		$name = $message;

		$html = array();
		$html[] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	      <html xmlns="http://www.w3.org/1999/xhtml">
	      <head>
	      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	      <title>Exception : '.$message.'</title>
	      <style type="text/css">

	      #exception {
			  background-color: #EEEEEE;
			  border:           solid 1px #750000;
			  font-family:      verdana, helvetica, sans-serif;
			  font-size:        76%;
			  font-style:       normal;
			  font-weight:      normal;
			  margin:           10px;
	      }

	      #help {
			  color:     #750000;
			  font-size: 0.9em;
	      }

	      .message {
			  color:       #FF0000;
			  font-weight: bold;
	      }

	      .title {
			  font-size:   1.1em;
			  font-weight: bold;
	      }

	      td {
			  background-color: #EEEEEE;
			  padding:          5px;
	      }

	      th {
			  background-color: #AAAAAA;
			  color:            #FFFFFF;
			  font-size:        1.2em;
			  font-weight:      bold;
			  padding:          5px;
			  text-align:       left;
	      }

	      </style>
	      </head>
	      <body>

	      <table id="exception" cellpadding="0" cellspacing="0">
			  <tr>
			      <th colspan="2">' . $name . '</th>
			  </tr>
			  <tr>
			      <td class="title">Message:</td>
			      <td class="message">' . $message . '</td>
			  </tr>
			  <tr>
			      <td class="title">Type:</td>
			      <td>' . get_class($exception) . '</td>
			  </tr>
			  <tr>
			      <td class="title">Code:</td>
			      <td>' . $code . '</td>
			  </tr>
			  <tr>
			      <td class="title">Class:</td>
			      <td>' . $class . '</td>
			  </tr>
			  <tr>
			      <td class="title">File:</td>
			      <td>' . $file . '</td>
			  </tr>
			  <tr>
			      <td class="title">Line:</td>
			      <td>' . $line . '</td>
			  </tr>';

		if (count($trace) > 0)
		{
			$html[] = '<tr>
				      <th colspan="2">Stack Trace</th>
				  </tr>';
			foreach ($trace as $line)
			{
				$html[] = '<tr>
					  <td colspan="2">' . $line . '</td>
				      </tr>';
			}
		}

		$html[] = '
		      </table>
		      </body>
		      </html>';

		return join("\n", $html);
	}
}