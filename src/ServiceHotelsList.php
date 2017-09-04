<?php
/**
 * Created by PhpStorm.
 * User: raul
 * Date: 15/9/15
 * Time: 11:44
 */

namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelsList.
 *
 * Services 17 of Hotusa/Restel XML Api.
 */
final class ServiceHotelsList
{
	const HOTUSA_SERVICE = 17;

	/**
	 * @object HotusaXml
	 */
	private $hotusa_xml;

	const HOTUSA_CATALOGUE_CACHE_TIME = 48 * 3600;

	/**
	 * @var string
	 */
	private $path_xml_hotusa = "/tmp/hotusa_hotel_catalogue_cache.xml";

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
	}



	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);
			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');

			if ($this->cacheFileExists() &&
				$this->cacheFileNotStale()
			) {
				$response = file_get_contents($this->path_xml_hotusa);
				$response = $this->hotusa_xml->transformToXML($response);
			} else {
				echo "WARNING: Downloading the full Hotusa Hotel Catalogue, it make take a while (minutes)" . PHP_EOL;
				flush();
				$response = $this->service_request->send($request_xml);
				$response->saveXML($this->path_xml_hotusa);
			}


			$hotels = $response->parametros->hoteles->hotel;
			if (count($hotels) <= 0) {
				throw new ServiceHotelsListException("Hotusa sucks. List of its full catalogue returns no hotels. Try again and it may work... " . $response->parametros->error->descripcion);
			}

			return $hotels;
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelsListException($e->getMessage());
		}
	}

	/**
	 * @return bool
	 */
	private function cacheFileExists()
	{
		return file_exists($this->path_xml_hotusa);
	}

	/**
	 * @return bool
	 */
	private function cacheFileNotStale()
	{
		return time() - filemtime($this->path_xml_hotusa) <= static::HOTUSA_CATALOGUE_CACHE_TIME;
	}
}

class ServiceHotelsListException extends \ErrorException
{
}
