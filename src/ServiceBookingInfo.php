<?php


namespace StayForLong\Hotusa;

use DateTime;

/**
 * Class ServiceBookingInfo
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceBookingInfo
{
	const HOTUSA_SERVICE = 11;
	const DEFAULT_STATUS = 'cancelled';
	const STATUSES = [
		'B' => 'cancelled',
		'C' => 'confirmed',
		'F' => 'confirmed',
		'N' => 'pending',
	];
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
	private $locator;

	/**
	 * ServiceBookingInfo constructor.
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param $locator
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, $locator)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->locator         = $locator;
	}

	/**
	 * @return array
	 * @throws ServiceBookingInfoException
	 */
	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');

			$params->addChild('Localizador', $this->locator);
			$params->addChild('Afiliacion', 'RS');
			$response = $this->service_request->send($request_xml);

			if (empty($response->RESERVA)) {
				throw new ServiceBookingInfoException("Empty response from Hotusa");
			}

			if(!empty($response->RESERVA->error)){
				throw new ServiceBookingInfoException($response->RESERVA->error->descripcion);
			}

			return $this->transformBooking($response->RESERVA[0]);


		} catch (ServiceRequestException $e) {
			throw new ServiceBookingInfoException($e->getMessage());
		}
	}

	/**
	 * @param \SimpleXMLElement $booking
	 * @return array
	 */
	private function transformBooking(\SimpleXMLElement $booking)
	{
		$checkin  = $this->createCheckin((string)$booking->Fecha_Entrada);
		$checkout = $this->createCheckout((string)$booking->Fecha_Salida);

		return [
			'locator'                  => (string)$booking->Localizador,
			'client_name'              => (string)$booking->Cliente,
			'rate'                     => (string)$booking->tarifa,
			'price'                    => (string)$booking->PVP,
			'price_without_commission' => (string)$booking->PVPA,
			'commision'                => (string)$booking->CVA,
			'base_price'               => (string)$booking->BAS,
			'status'                   => $this->getStatus((string)$booking->Estado),
			'checkin'                  => $checkin->format('Y-m-j'),
			'checkout'                 => $checkout->format('Y-m-j'),
		];
	}

	/**
	 * @param $date_str
	 * @return DateTime
	 */
	private function createCheckin($date_str)
	{
		$date = DateTime::createFromFormat('Ymj', $date_str);

		if ($date === false || $date_str === '00000000') {
			return new DateTime('yesterday');
		}
		return $date;
	}

	/**
	 * @param $date_str
	 * @return DateTime
	 */
	private function createCheckout($date_str)
	{
		$date = DateTime::createFromFormat('Ymj', $date_str);

		if ($date === false || $date_str === '00000000') {
			return new DateTime('now');
		}
		return $date;
	}

	/**
	 * @param string $hotusa_status
	 * @return string
	 */
	private function getStatus($hotusa_status)
	{
		if (empty($hotusa_status)) {
			return self::DEFAULT_STATUS;
		}
		return self::STATUSES[$hotusa_status];
	}

}

final class ServiceBookingInfoException extends \ErrorException
{
	public static function ofNoBookings()
	{
		return new self('There are no bookings');
	}
}