<?php

declare( strict_types=1 );

namespace Classes;

use Enums\Currency;

/**
 * Сумма денег.
 * Для упрощения работаем только с целочисленными суммами, например доллар.
 * Умышленно не завожу сюда логику работы с дробными суммами, так как не указано в ТЗ.
 * Если хотим работать с копейками, центами и тп - сумму стоит представлять в них, например: 1000 = 10 рублей = 1000 центов.
 *
 * @property int $value Сумма в числовом выражении
 * @property Currency $currency Валюта суммы
 */
readonly class Amount {
	public function __construct(
		public int $value,
		public Currency $currency,
	) {
		if ( $this->value < 0 ) {
			throw new \InvalidArgumentException( 'Сумма должна быть положительной.' );
		}
	}

	public function __toString(): string {
		return sprintf( '%d %s', $this->value, $this->currency->name );
	}
}