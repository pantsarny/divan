<?php

declare( strict_types=1 );

namespace Classes;

use Enums\Currency;
use Exceptions\BaseException;
use Exceptions\CurrencyExistsException;
use Exceptions\CurrencyNotExistsException;
use Exceptions\NotEnoughBalanceException;

/**
 * Мультивалютный счет.
 */
class Account {

	/**
	 * Баланс счета
	 * Упрощенный вариант на массиве, где ключ - имя валюты, значение - сумма
	 * @var int[]
	 */
	protected array $balance = [];

	/** @var Currency|null Основная валюта счета */
	protected ?Currency $baseCurrency = null;

	public function __construct( public readonly Bank $bank ) {
	}

	/**
	 * Проверяет наличие валюты на счету.
	 *
	 * @param Currency $currency
	 *
	 * @return bool
	 */
	protected function hasCurrency( Currency $currency ) {
		return in_array( $currency->name, array_keys( $this->balance ), true );
	}

	/**
	 * Убедиться, что валюта существует на счету.
	 *
	 * @param Currency $currency
	 *
	 * @return void
	 * @throws CurrencyNotExistsException
	 */
	protected function ensureHasCurrency( Currency $currency ) {
		if ( ! $this->hasCurrency( $currency ) ) {
			throw new CurrencyNotExistsException( sprintf( 'Валюта %s не существует на счету.', $currency->name ) );
		}
	}

	/**
	 * Добавление валюты.
	 *
	 * @param $currency $code
	 *
	 * @return void
	 * @throws CurrencyExistsException
	 */
	public function addCurrency( Currency $currency ): void {
		if ( $this->hasCurrency( $currency ) ) {
			throw new CurrencyExistsException( sprintf( 'Валюта %s уже существует на счету.', $currency->name ) );
		}

		$this->balance[ $currency->name ] = 0;
	}

	/**
	 * Отключение валюты.
	 *
	 * @param Currency $currency
	 *
	 * @return void
	 * @throws BaseException
	 * @throws CurrencyNotExistsException
	 */
	public function removeCurrency( Currency $currency ): void {
		$baseCurrency = $this->getBaseCurrency( true );
		if ( $currency === $baseCurrency ) {
			throw new BaseException( 'Основная валюта не может быть отключена.' );
		}

		$amount = $this->getCurrencyBalance( $currency );
		if ( $amount->value ) {
			$this->decreaseBalance( $amount );
			$this->increaseBalance( $this->bank->convert( $amount, $baseCurrency ) );
		}

		unset( $this->balance[ $currency->name ] );
	}


	/**
	 * Получает основную валюту счета.
	 *
	 * @param bool $throwException выбрасывать исключение если валюта не установлена
	 *
	 * @return Currency|null
	 * @throws BaseException
	 */
	protected function getBaseCurrency( bool $throwException = false ): ?Currency {
		$currency = $this->baseCurrency;
		if ( $currency !== null ) {
			return $currency;
		} elseif ( $throwException ) {
			throw new BaseException( 'Основная валюта не установлена.' );
		} else {
			return null;
		}
	}

	/**
	 * Устаналивает основную валюту счета.
	 *
	 * @param Currency $currency
	 *
	 * @return void
	 * @throws CurrencyNotExistsException
	 */
	public function setBaseCurrency( Currency $currency ): void {
		$this->ensureHasCurrency( $currency );

		$this->baseCurrency = $currency;
	}

	/**
	 * Список поддерживаемых валют
	 *
	 * @return Currency[]
	 */
	public function getCurrencies(): array {
		return array_map( fn( string $name ) => Currency::fromName( $name ), array_keys( $this->balance ) );
	}

	/**
	 * Пополнить баланс
	 *
	 * @param Amount $amount
	 *
	 * @return void
	 * @throws CurrencyNotExistsException
	 */
	public function increaseBalance( Amount $amount ): void {
		$currency = $amount->currency;

		$this->ensureHasCurrency( $currency );

		$this->balance[ $currency->name ] += $amount->value;
	}

	/**
	 * Списывание с баланса.
	 *
	 * @param Amount $amount
	 *
	 * @return Amount списанная сумма
	 * @throws CurrencyNotExistsException
	 * @throws NotEnoughBalanceException
	 */
	public function decreaseBalance( Amount $amount ): Amount {
		$currency = $amount->currency;

		$this->ensureHasCurrency( $currency );

		$balanceAfter = $this->balance[ $currency->name ] - $amount->value;
		if ( $balanceAfter < 0 ) {
			throw new NotEnoughBalanceException( sprintf( 'Недостаточно средств на счету для выполнения списания %d %s.',
				$amount->value, $amount->currency->name ) );
		}

		$this->balance[ $currency->name ] = $balanceAfter;

		return $amount;
	}

	/**
	 * Получает суммарный баланс аккаунта в выбранной валюте.
	 *
	 * @param Currency|null $currency если не установлен - основная валюта счета
	 *
	 * @return Amount
	 * @throws CurrencyNotExistsException
	 */
	public function getBalance( ?Currency $currency = null ): Amount {
		$currency = $currency ?? $this->getBaseCurrency();
		if ( null === $currency ) {
			throw new CurrencyNotExistsException( 'Запрошенная валюта не существует на счету.' );
		}

		$value = 0;
		foreach ( $this->getCurrencies() as $accountCurrency ) {
			$rate  = $accountCurrency === $currency ? 1 : $this->bank->getRate( $accountCurrency, $currency );
			$value += ( $this->getCurrencyBalance( $accountCurrency )->value * $rate );
		}

		return new Amount( $value, $currency );
	}

	/**
	 * Получает баланс валюты аккаунта.
	 *
	 * @param Currency $currency
	 *
	 * @return Amount
	 * @throws CurrencyNotExistsException
	 */
	protected function getCurrencyBalance( Currency $currency ): Amount {
		$this->ensureHasCurrency( $currency );

		return new Amount( $this->balance[ $currency->name ], $currency );
	}
}