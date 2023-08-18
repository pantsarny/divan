<?php

declare( strict_types=1 );

namespace Classes;

use Enums\Currency;
use Exceptions\RateNotExistsException;

/**
 *
 */
class Bank {
	/**
	 * Примитив с рейтами
	 * @var array
	 */
	private array $rates = [];

	public function openAccount(): Account {
		return new Account( $this );
	}

	/**
	 * @param Currency $from
	 * @param Currency $to
	 * @param int $value
	 *
	 * @return void
	 */
	public function setRate( Currency $from, Currency $to, int $value ): void {
		if ( $to === $from ) {
			throw new \InvalidArgumentException( 'Пара валют не может состоять из одинаковой валюты.' );
		}
		if ( $value <= 0 ) {
			throw new \InvalidArgumentException( 'Курс должен быть более нуля.' );
		}

		$this->rates[ $from->name ][ $to->name ] = $value;
		$this->rates[ $to->name ][ $from->name ] = (int) round( 1 / $value );
	}

	public function getRate( Currency $from, Currency $to ): int {
		if ( $to === $from ) {
			return 1;
		}

		$rate = $this->rates[ $from->name ][ $to->name ] ?? null;
		if ( null === $rate ) {
			throw new RateNotExistsException( sprintf( 'Курс обмена для пары %s-%s не установлен.', $from->name,
				$to->name ) );
		}

		return $rate;
	}

	/**
	 * Конвертация валюты.
	 *
	 * @param Amount $amount
	 * @param Currency $currency
	 *
	 * @return Amount
	 * @throws RateNotExistsException
	 */
	public function convert( Amount $amount, Currency $currency ): Amount {
		if ( $amount->currency === $currency ) {
			return $amount;
		}

		$rate  = $this->getRate( $amount->currency, $currency );
		$value = $amount->value * $rate;

		return new Amount( $value, $currency );
	}
}