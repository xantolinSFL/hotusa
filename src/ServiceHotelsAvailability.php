<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceHotelsAvailability
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceHotelsAvailability
{
	const HOTUSA_SERVICE = 110;

	/**
	 * @var HotusaXML
	 */
	private $hotusa_xml;

	/**
	 * @var ServiceRequest
	 */
	private $service_request;

	/**
	 * @var array
	 */
	private $available_request_params = [
		'hotel',
		'pais',
		'provincia',
		'poblacion',
		'radio',
		'fechaentrada',
		'fechasalida',
		'numhab1',
		'paxes1',
		'numhab2',
		'paxes2',
		'numhab3',
		'paxes3',
		'idioma',
	];

	/**
	 * @var array
	 */
	private $request_params = [];

	private $request_configuration = [];

	/**
	 * @param ServiceRequest $request
	 * @param HotusaXML $hotusa_xml
	 * @param array $request_params
	 */
	public function __construct(
		ServiceRequest $request,
		HotusaXML $hotusa_xml,
		array $some_request_configuration,
		array $some_request_params
	) {
		$this->service_request       = $request;
		$this->hotusa_xml            = $hotusa_xml;
		$this->request_params        = $some_request_params;
		$this->request_configuration = $some_request_configuration;
	}

	/**
	 * @return mixed
	 * @throws ServiceHotelsAvailabilityException
	 */
	public function __invoke()
	{
		try {
			$request_xml = $this->hotusa_xml->init();
			$request_xml->addChild('tipo', self::HOTUSA_SERVICE);
			$request_xml->addChild('parametros');
			$request_xml->addChild('comprimido', '2');
			$request_xml->addChild('radio', '9');
			$request_xml->addChild('tarifas_reembolsables', '1');
			$request_xml->addChild('afiliacion', $this->request_configuration['afiliacio']);
			$request_xml->addChild('usuario', $this->request_configuration['codusu']);

			foreach ($this->request_params as $param_key => $param_value) {
				$param_value = $this->prepareHotelCodes($param_key, $param_value);

				if (in_array($param_key, $this->available_request_params)) {
					$request_xml->addChild($param_key, $param_value);
				} else {
					throw new ServiceHotelsAvailabilityException("Parameter '$param_key' isn't available for this service.");
				}
			}

			$response = $this->service_request->send($request_xml);
			if ($response && isset($response->param->hotls)) {
				$hotels = json_decode(json_encode($response->param->hotls), true);

				if (0 >= $hotels['@attributes']["num"]) {
					$hotels_codes = $this->request_params['hotel'];
					throw new ServiceHotelsAvailabilityException("No available rooms for the hotels $hotels_codes. Num: {$hotels['@attributes']["num"]}");
				} elseif (1 >= $hotels['@attributes']["num"]) {
					return [$hotels['hot']];
				}

				return $hotels['hot'];
			} elseif (isset($response->param->error->descripcion)) {
				throw new ServiceHotelsAvailabilityException($response->param->error->descripcion);
			} else {
				throw new ServiceHotelsAvailabilityException("Empty response from Hotusa");
			}
		} catch (ServiceRequestException $e) {
			throw new ServiceHotelsAvailabilityException($e->getMessage());
		}
	}

	/**
	 * @param $param_key
	 * @param $param_value
	 * @return string
	 */
	private function prepareHotelCodes($param_key, $param_value)
	{
		if (empty($param_value)) {
			$param_value = "";
		} elseif ("hotel" == $param_key) {
			$param_value = implode("#", explode(",", $param_value)) . "#";
		}

		return $param_value;
	}
}

class ServiceHotelsAvailabilityException extends \ErrorException
{
}
