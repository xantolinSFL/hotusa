<?php


namespace StayForLong\Hotusa;

use DateTime;
use StayForLong\Hotusa\Transformer\CurrencyTransformer;

/**
 * Class ServiceHotelCancellationPolicies
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
	 * @var integer
	 */
	private $language;
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
	public function __construct(ServiceRequest $request, HotusaXML $hotusa_xml, DateTime $date_start, $language)
	{
		$this->service_request = $request;
		$this->hotusa_xml      = $hotusa_xml;
		$this->language        = $language;
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
			$params->addChild('idioma', $this->language);

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
			foreach ($response->parametros->reservas as $reserva) {
				$bookings[] = $this->transformBooking($reserva);
			}
			return $bookings;

		} catch (ServiceRequestException $e) {
			throw new ServiceHotelLatestBookingsException($e->getMessage());
		}
	}

	/**
	 * @param \SimpleXMLElement $reserva
	 * @return array
	 */
	private function transformBooking(\SimpleXMLElement $reserva)
	{
		return [
			'creation_date'     => ((array)$reserva->fecha_creacion)[0],
			'cancellation_date' => ((array)$reserva->fecha_cancelacion)[0],
			'long_locator'      => ((array)$reserva->localizador)[0],
			'user'              => ((array)$reserva->usuario)[0],
			'hotel_name'        => ((array)$reserva->hotel)[0],
			'price'             => ((array)$reserva->precio)[0],
			'short_locator'     => ((array)$reserva->localizador_corto)[0],
			'client_name'       => ((array)$reserva->clienteres)[0],
			'client_email'      => ((array)$reserva->emailres)[0],
			'client_phone'      => ((array)$reserva->telfres)[0],
			'checkin'           => ((array)$reserva->fecha_entrada)[0],
			'currency'          => (new CurrencyTransformer(((array)$reserva->divisa)[0]))->transform(),
			'id'                => ((array)$reserva->id)[0],
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