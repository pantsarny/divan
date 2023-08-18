<?php

declare( strict_types=1 );

namespace Enums;

enum Currency {
	case RUB;
	case USD;
	case EUR;

	/**
	 * Получает валюты из ее имени.
	 *
	 * @param string $name
	 *
	 * @return static
	 */
	public static function fromName( string $name ): static {
		foreach ( static::cases() as $case ) {
			if ( $case->name === $name ) {
				return $case;
			}
		}

		throw new \ValueError( sprintf( 'Валюта %s не существует.', $name ) );
	}
}