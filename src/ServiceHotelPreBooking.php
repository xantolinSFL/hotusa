<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelPreBooking
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelPreBooking
{
	/**
	 *
	 */
	const HOTUSA_SERVICE = 202;

	/**
	 * Prepay 44, Credit 25, Direct pay 12.
	 */
	const DEFAULT_PAYMENT_TYPE = 44;

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
	 * @var string
	 */
	private $client_name;

	/**
	 * @var string
	 */
	private $requests;

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param $hotel_code
	 * @param array $rate_keys
	 * @param string $client_name
	 * @param string $requests
	 */
	public function __construct(
		ServiceRequest $request,
		HotusaXML $hotusa_xml,
		$hotel_code,
		array $rate_keys,
		$client_name,
		$requests
	) {
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->hotel_code      = $hotel_code;
		$this->rate_keys       = $rate_keys;
		$this->client_name     = $client_name;
		$this->requests        = $requests;
	}

	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');
			$params->addChild('comprimido', '2');
			$params->addChild('codigo_hotel', $this->hotel_code);
			$params->addChild('nombre_cliente', $this->client_name);
			$params->addChild('observaciones', $this->requests);
			$params->addChild('num_mensaje', '');
			$params->addChild('forma_pago', self::DEFAULT_PAYMENT_TYPE);

			$param_line = $params->addChild('res');
			foreach ($this->rate_keys as $line) {
				$param_line->addChild('lin', $line);
			}

			$response = $this->service_request->send($request_xml);
			if (
				$response
				&& isset($response->parametros->n_localizador)
			) {
				$locator =(array)$response->parametros->n_localizador;
				return ['locator' => $locator[0]];
			} else {
				throw new ServiceHotelPreBookingException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelPreBookingException($e->getMessage());
		}
	}
}

class ServiceHotelPreBookingException extends \ErrorException
{
}
