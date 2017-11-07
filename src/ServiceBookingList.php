<?php


namespace StayForLong\Hotusa;

use DateTime;
use StayForLong\Hotusa\Transformer\CurrencyTransformer;

/**
 * Class ServiceBookingList
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceBookingList
{
	const HOTUSA_SERVICE = 8;

	/**
	 * @var HotusaXML
	 */
	private $hotusa_xml;

	/**
	 * @var ServiceRequest
	 */
	private $service_request;

	/**
	 * @var DateTime
	 */
	private $date_start;

	/**
	 * ServiceHotelLatestBookingsList constructor.
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param DateTime $date_start
	 * @param $language
	 */
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, DateTime $date_start)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->date_start      = $date_start;
	}

	/**
	 * @return array
	 * @throws ServiceHotelLatestBookingsException
	 */
	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);

			$params = $request_xml->addChild('parametros');

			$params->addChild('dia', $this->date_start->format('d'));
			$params->addChild('mes', $this->date_start->format('m'));
			$params->addChild('ano', $this->date_start->format('Y'));
			$params->addChild('selector', 4);
			$params->addChild('usuario', $this->service_request->getCodUsu());

			$response = $this->service_request->send($request_xml);

			if (empty($response->parametros->reservas)) {
				throw new ServiceHotelLatestBookingsException("Empty response from Hotusa");
			}

			foreach ($response->parametros->reservas->attributes() as $key => $value) {
				if ($key === 'num' && (int)$value === 0) {
					throw ServiceHotelLatestBookingsException::ofNoBookings();
				}
			}
			$bookings = [];
			foreach ($response->parametros->reservas->reserva as $booking) {
				$bookings[] = $this->transformBooking($booking);
			}
			return $bookings;

		} catch (ServiceRequestException $e) {
			throw new ServiceHotelLatestBookingsException($e->getMessage());
		}
	}

	/**
	 * @param \SimpleXMLElement $booking
	 * @return array
	 */
	private function transformBooking(\SimpleXMLElement $booking)
	{
		return [
			'creation_date'     => ((string)$booking->fecha_creacion),
			'cancellation_date' => ((string)$booking->fecha_cancelacion),
			'long_locator'      => ((string)$booking->localizador),
			'user'              => ((string)$booking->usuario),
			'hotel_name'        => ((string)$booking->hotel),
			'price'             => ((float)$booking->precio),
			'short_locator'     => ((string)$booking->localizador_corto),
			'client_name'       => ((string)$booking->clienteres),
			'client_email'      => ((string)$booking->emailres),
			'client_phone'      => ((string)$booking->telfres),
			'checkin'           => ((string)$booking->fecha_entrada),
			'currency'          => (new CurrencyTransformer((string)$booking->divisa))->transform(),
			'id'                => ((string)$booking->id),
		];
	}
}

final class ServiceHotelLatestBookingsException extends \ErrorException
{
	public static function ofNoBookings()
	{
		return new self('There are no bookings');
	}
}