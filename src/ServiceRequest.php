<?php namespace StayForLong\Hotusa;

/**
 * Class ServiceRequest
 * @package StayForLong\Hotusa
 * @author Raúl Morón <raul@stayforlong.com>
 */
final class ServiceRequest
{
	private $request_parameters = [];

	/**
	 * @param array $request_parameters
	[
	 * 'url'    => "http://xml.hotelresb2b.com/xml/listen_xml.jsp",
	 * 'enconding' => '{iso-8859-1 | utf-8}',
	 * 'query'    => [
	 * 'codigousu' => '{codigousu}',
	 * 'codusu'    => '{codusu}',
	 * 'secacc'    => '{secacc}',
	 * 'clausu'    => '{clausu}',
	 * 'afiliacio' => '{afiliacio}',
	 * 'xml'        => SimpleXMLElement
	 * ]
	 * ]
	 *
	 */
	public function __construct(array $request_parameters)
	{
		$this->request_parameters = $request_parameters;
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param int $timeout
	 * @return \SimpleXMLElement
	 * @throws ServiceRequestException
	 */
	public function send(\SimpleXMLElement $xml, $timeout = 1)
	{
		try {
			$query = array(
				'codigousu' => $this->request_parameters['codigousu'],
				'clausu'    => $this->request_parameters['clausu'],
				'afiliacio' => $this->request_parameters['afiliacio'],
				'secacc'    => $this->request_parameters['secacc'],
				'xml'       => $xml->asXML(),
			);

			$ch = curl_init($this->request_parameters['url']);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_ENCODING, $this->request_parameters['enconding']);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_USERAGENT, "PHP XMLRPC 1.1");
			curl_setopt($ch, CURLOPT_ENCODING, "gzip");

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));

			$response = curl_exec($ch);
			curl_close($ch);

			$response_xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

			if (isset($response_xml->parametros->error->descripcion)) {
				throw new ServiceRequestException($response_xml->parametros->error->descripcion);
			}

			return $response_xml;
		} catch (Exception $e) {
			throw new ServiceRequestException("Response: $response");
		}
	}
}

class ServiceRequestException extends \ErrorException
{
}