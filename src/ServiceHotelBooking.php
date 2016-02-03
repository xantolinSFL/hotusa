<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelBooking
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelBooking
{
	/**
	 *
	 */
	const HOTUSA_SERVICE = 3;

	/**
	 * Booking confirmation (AE).
	 */
	const BOOKING_ACTION = 'AE';

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
	private $locator;

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 */
	public function __construct(
		ServiceRequest $request,
		HotusaXML $hotusa_xml,
		$locator
	) {
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->locator         = $locator;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('peticion');
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$request_xml->addChild('parametros');
			$request_xml->addChild('comprimido', '2');
			$request_xml->addChild('localizador', $this->locator);
			$request_xml->addChild('accion', self::BOOKING_ACTION);

			$response = $this->service_request->send($request_xml);

			if (
				$response
				&& isset($response->parametros->localizador)
			) {
				$long_locator =(array)$response->parametros->localizador;
				$short_locator =(array)$response->parametros->localizador_corto;

				return [
					"long_locator"  => $long_locator[0],
					"short_locator" => $short_locator[0],
				];
			} else {
				throw new ServiceHotelBookingException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelBookingException($e->getMessage());
		}
	}
}

class ServiceHotelBookingException extends \ErrorException
{
}
