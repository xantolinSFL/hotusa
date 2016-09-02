<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelCancellation
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelCancellation
{
	/**
	 * Hotusa service to cancellation process.
	 */
	const HOTUSA_SERVICE = 401;

	/**
	 * Cancelled status.
	 */
	const CANCELLED_STATUS = "cancelled";

	/**
	 * Invalid status when exists any error from the provider.
	 */
	const INVALID_STATUS = "invalid";

	/**
	 * @var HotusaXML
	 */
	private $hotusa_xml;

	/**
	 * @var ServiceRequest
	 */
	private $service_request;

	/**
	 * @var string
	 */
	private $long_locator;

	/**
	 * @var string
	 */
	private $short_locator;

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param string $long_locator
	 * @param string $short_locator
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $long_locator, $short_locator)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->long_locator    = $long_locator;
		$this->short_locator   = $short_locator;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');
			$params->addChild('localizador_largo', $this->long_locator);
			$params->addChild('localizador_corto', $this->short_locator);

			$response = $this->service_request->send($request_xml);

			if (
				$response
				&& isset($response->parametros->localizador)
			) {
				$long_locator           = (array)$response->parametros->localizador;
				$cancellation_reference = (array)$response->parametros->localizador_baja;
				$status                 = (array)$response->parametros->estado;
				$raw_response           = json_encode($response);

				$cancelled_status = [
					"00", // Ok.
					"09", // Previously canceled or manual cancellation in the provider.
				];

				return [
					"long_locator"           => $long_locator[0],
					"cancellation_reference" => $cancellation_reference[0],
					"status"                 => in_array($status[0],
						$cancelled_status) ? static::CANCELLED_STATUS : static::INVALID_STATUS,
					"raw_response"           => $raw_response,
				];
			} else {
				throw new ServiceHotelCancellationException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelCancellationException($e->getMessage());
		}
	}
}

class ServiceHotelCancellationException extends \ErrorException
{
}
