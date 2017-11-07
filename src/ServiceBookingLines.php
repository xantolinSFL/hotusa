<?php


namespace StayForLong\Hotusa;

/**
 * Class ServiceBookingLines
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceBookingLines
{
	const HOTUSA_SERVICE = 9;

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
	private $short_locator;

	/**
	 * ServiceBookingLines constructor.
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param $short_locator
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $short_locator)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->short_locator   = $short_locator;
	}

	/**
	 * @return array
	 * @throws ServiceBookingLinesException
	 */
	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');

			$params->addChild('localizador', $this->short_locator);
			$response = $this->service_request->send($request_xml);

			if (empty($response->parametros->reservas)) {
				throw new ServiceBookingLinesException("Empty response from Hotusa");
			}

			foreach ($response->parametros->reservas->attributes() as $key => $value) {
				if ($key === 'num' && (int)$value === 0) {
					throw ServiceBookingLinesException::ofNoBookings();
				}
			}
			$lines = [];
			foreach ($response->parametros->reservas->reserva as $line) {
				$lines[] = $this->transformLine($line);
			}

			return $lines;

		} catch (ServiceRequestException $e) {
			throw new ServiceBookingLinesException($e->getMessage());
		}
	}

	/**
	 * @param \SimpleXMLElement $line
	 * @return array
	 */
	private function transformLine(\SimpleXMLElement $line)
	{
		return [
			'checkin'     => (string)$line->fecha_entrada,
			'checkout'    => (string)$line->fecha_salida,
			'client_name' => (string)$line->cliente,
			'room_type'   => (string)$line->tipo_hab,
			'rate'        => (string)$line->tarifa,
			'price'       => (string)$line->importe,
			"status"      => "00" == ((string)$line->estado) ? "confirmed" : "invalid",
			'hotel_code'  => (string)$line->codigo_hotel,
			'room_count'  => (string)$line->num_hab,
			'regime'      => (string)$line->regimen,
			'adults'      => (string)$line->num_adul,
			'children'    => (string)$line->num_nin,
		];
	}
}

final class ServiceBookingLinesException extends \ErrorException
{
	public static function ofNoBookings()
	{
		return new self('There are no bookings');
	}
}