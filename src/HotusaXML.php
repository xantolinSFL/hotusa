<?php
/**
 * Created by PhpStorm.
 * User: raul
 * Date: 17/9/15
 * Time: 13:12
 */

namespace StayForLong\Hotusa;

class HotusaXML {
	/**
	 * Start the xml object to use in the request.
	 *
	 * @return \SimpleXMLElement
	 */
	public function init()
	{
		$first_tag = '<?xml version="1.0" encoding="UTF-8" ?><peticion></peticion>';

		$xml = new \SimpleXMLElement($first_tag);

		return $xml;
	}

	public function transformToXML($response)
	{
		return simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
	}

	public function xml2array($xml)
	{
	    $arr = array();

	    foreach ($xml as $element)
	    {
	        $tag = $element->getName();
	        $e = get_object_vars($element);
	        if (!empty($e))
	        {
	            $arr[$tag] = $element instanceof \SimpleXMLElement ? $this->xml2array($element) : $e;
	        }
	        else
	        {
	            $arr[$tag] = trim($element);
	        }
	    }

	    return $arr;
	}

}