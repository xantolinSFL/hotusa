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

	/**
	 * @var string
	 */
	private $path_xml_hotusa = "/tmp";

	/**
	 * @param ServiceRequest $request
	 * @param \SimpleXMLElement $request_xml
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

			if (!file_exists($this->path_xml_hotusa)) {
				$response = $this->service_request->send($request_xml);
				file_put_contents($this->path_xml_hotusa, $response);
			} else {
				$response = file_get_contents($this->path_xml_hotusa);
			}

			$response = $this->hotusa_xml->transformToXML($response);

			$hotels = $response->parametros->hoteles->hotel;
			if (count($hotels) <= 0) {
				throw new ServiceHotelsListException("No hotels! " . $response->parametros->error->descripcion);
			}

			return $hotels;
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelsListException($e->getMessage());
		}
	}
}

class ServiceHotelsListException extends \ErrorException
{
}