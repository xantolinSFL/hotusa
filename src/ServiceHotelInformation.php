<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelInformation
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelInformation
{
	const HOTUSA_SERVICE = 15;

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
	 * @var integer
	 */
	private $language;

	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $hotel_code, $language)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->hotel_code      = $hotel_code;
		$this->language        = $language;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');
			$params->addChild('codigo', $this->hotel_code);
			$params->addChild('idioma', $this->language);

			$response = $this->service_request->send($request_xml);

			if ($response && isset($response->parametros->hotel)) {
				return (array)$response->parametros->hotel;
			} else {
				throw new ServiceHotelInformationException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelInformationException($e->getMessage());
		}
	}
}

class ServiceHotelInformationException extends \ErrorException
{
}