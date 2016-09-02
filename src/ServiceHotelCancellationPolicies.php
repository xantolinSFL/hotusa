<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelCancellationPolicies
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelCancellationPolicies
{
	const HOTUSA_SERVICE = 144;

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
	 * @var array
	 */
	private $rate_keys = [];

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $hotel_code, array $rate_keys)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->hotel_code      = $hotel_code;
		$this->rate_keys       = $rate_keys;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$request_xml->addChild('datos_reserva');
			$hotel = $request_xml->addChild('hotel', $this->hotel_code);

			foreach($this->rate_keys as $line){
				$hotel->addChild('lin', $line);
			}

			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');

			$response = $this->service_request->send($request_xml);
			if (
				$response
				&& isset($response->parametros->politicaCanc)
			) {
				return (array)$response->parametros->politicaCanc;
			} else {
				throw new ServiceHotelCancellationPoliciesException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelCancellationPoliciesException($e->getMessage());
		}
	}
}

class ServiceHotelCancellationPoliciesException extends \ErrorException
{
}
