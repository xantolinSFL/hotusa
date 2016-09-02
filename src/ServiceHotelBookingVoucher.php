<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelBookingVoucher
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelBookingVoucher
{
	/**
	 *
	 */
	const HOTUSA_SERVICE = 12;

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
	 * @param string $locator
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
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');
			$params->addChild('localizador', $this->locator);

			$response = $this->service_request->send($request_xml);

			if (
				$response
				&& isset($response->parametros->reserva->localizador_largo)
			) {
				$book = (array)$response->parametros->reserva;

				$long_locator  = $book['localizador_largo'];
				$short_locator = $book['localizador_corto'];

				return [
					"long_locator"  => $long_locator,
					"short_locator" => $short_locator,
					"reference"     => $long_locator,
					"raw_response"  => json_encode((array)$response, true),
				];
			} else {
				throw new ServiceHotelBookingVoucherException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelBookingVoucherException($e->getMessage());
		}
	}
}

class ServiceHotelBookingVoucherException extends \ErrorException
{
}
