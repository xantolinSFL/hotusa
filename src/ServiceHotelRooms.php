<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelRooms
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelRooms
{
	const HOTUSA_SERVICE = 23;

	/**
	 * @var HotusaXML
	 */
	private $hotusa_xml;

	/**
	 * @var ServiceRequest
	 */
	private $service_request;

	/**
	 * @var integer
	 */
	private $hotel_code;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param $hotel_code
	 * @param $language
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $hotel_code, $language)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->hotel_code      = $hotel_code;
		$this->language        = $language;
	}

	/**
	 * @return array
	 * @throws ServiceHotelRoomsException
	 */
	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);
			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');
			$params->addChild('codhot', $this->hotel_code);

			$response = $this->service_request->send($request_xml);

			$hotusa_rooms = [];
			if (!isset($response->parametros->habitaciones)) {
				return $hotusa_rooms;
			}

			$hotusa_rooms = (array)$response->parametros->habitaciones;

			return $hotusa_rooms;
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelRoomsException($e->getMessage());
		}
	}
}

class ServiceHotelRoomsException extends \ErrorException
{
}