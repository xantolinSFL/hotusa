<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelBookingCancellation
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelBookingCancellation
{
	/**
	 *
	 */
	const HOTUSA_SERVICE = 401;

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
	private $long_locator;

	/**
	 * @var integer
	 */
	private $short_locator;

	/**
	 * @param ServiceRequest $a_request
	 * @param HotusaXML $a_hotusa_xml
	 * @param $a_long_locator
	 * @param $a_short_locator
	 */
	public function __construct(
		ServiceRequest $a_request,
		HotusaXML $a_hotusa_xml,
		$a_long_locator,
		$a_short_locator
	) {
		$this->service_request = $a_request;
		$this->hotusa_xml      = $a_hotusa_xml;
		$this->long_locator    = $a_long_locator;
		$this->short_locator   = $a_short_locator;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('peticion');
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$request_xml->addChild('parametros');
			$request_xml->addChild('comprimido', '2');
			$request_xml->addChild('localizador_largo', $this->long_locator);
			$request_xml->addChild('localizador_corto', $this->short_locator);

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
				ServiceHotelBookingCancellationException::throwBecauseIncorrectResponse($response);
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelBookingCancellationException($e->getMessage());
		}
	}
}

final class ServiceHotelBookingCancellationException extends \ErrorException
{
	public static function throwBecauseIncorrectResponse($response)
	{
		throw new self("Incorrect response from Hotusa". $response);
	}
}
