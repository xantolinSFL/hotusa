<?php


namespace StayForLong\Hotusa\Transformer;


class CurrencyTransformer
{
	const CURRENCIES = [
		'EU' => 'EUR',
		'PA' => 'ARS',
		'DC' => 'CAD',
		'FS' => 'CHF',
		'YE' => 'JPY',
		'DO' => 'USD',
		'LB' => 'GBP',
		'PM' => 'MXN',
		'DA' => 'AUD',
		'RU' => 'RUB',
		'BR' => 'BRL',
	];

	private $hotusa_currency;

	/**
	 * CurrencyTransformer constructor.
	 * @param $hotusa_currency
	 */
	public function __construct($hotusa_currency)
	{
		$this->hotusa_currency = trim($hotusa_currency);
	}

	/**
	 * @return string
	 * @throws CurrencyTransformerException
	 */
	public function transform()
	{
		if (empty(self::CURRENCIES[$this->hotusa_currency])) {
			throw CurrencyTransformerException::ofCurrencyNotExists($this->hotusa_currency);
		}
		return self::CURRENCIES[$this->hotusa_currency];
	}
}

final class CurrencyTransformerException extends \Exception
{
	/**
	 * @param $currency
	 * @return CurrencyTransformerException
	 */
	public static function ofCurrencyDoesntExists($currency)
	{
		return new self(sprintf('Hotusa currency %s does not exists',
			$currency));
	}
}