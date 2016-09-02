<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceProvinces
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceProvinces {
	const HOTUSA_SERVICE = 6;

	/**
	 * @var HotusaXML
	 */
	private $hotusa_xml;

	/**
	 * @var ServiceRequest
	 */
	private $service_request;

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

			$response = $this->service_request->send($request_xml);
			if ($response && isset($response->parametros->provincias)) {
				return (array)$response->parametros->provincias;
			} else {
				throw new ServiceProvincesException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceProvincesException($e->getMessage());
		}
	}
}

class ServiceProvincesException extends \ErrorException
{
}
