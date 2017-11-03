<?php


namespace StayForLong\Hotusa;

use DateTime;

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

			return (array)$response->parametros->reservas;

		} catch (ServiceRequestException $e) {
			throw new ServiceHotelLatestBookingsException($e->getMessage());
		}
	}
}

final class ServiceHotelLatestBookingsException extends \ErrorException
{
	public static function ofNoBookings()
	{
		return new self('There are no bookings');
	}
}