<?php

declare( strict_types=1 );

error_reporting( E_ALL );

use Classes\Amount;
use Enums\Currency;

spl_autoload_register( function ( $className ) {
	$namespace = str_replace( "\\", "/", __NAMESPACE__ );
	$className = str_replace( "\\", "/", $className );
	$class     = __DIR__ . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR . "{$className}.php";
	include_once( $class );
} );

// step 1
$bank1 = new \Classes\Bank();
$bank1->setRate( Currency::EUR, Currency::RUB, 80 );
$bank1->setRate( Currency::USD, Currency::RUB, 70 );
$bank1->setRate( Currency::EUR, Currency::USD, 1 );
$account1 = $bank1->openAccount();
$account1->addCurrency( Currency::RUB );
$account1->addCurrency( Currency::EUR );
$account1->addCurrency( Currency::USD );
$account1->setBaseCurrency( Currency::RUB );
print_r( $account1->getCurrencies() );
$account1->increaseBalance( new Amount( 1000, Currency::RUB ) );
$account1->increaseBalance( new Amount( 50, Currency::EUR ) );
$account1->increaseBalance( new Amount( 50, Currency::EUR ) );

// step 2
print_r( $account1->getBalance() );
print_r( $account1->getBalance( Currency::USD ) );
print_r( $account1->getBalance( Currency::EUR ) );

// step 3
$account1->increaseBalance( new Amount( 1000, Currency::RUB ) );
$account1->increaseBalance( new Amount( 50, Currency::EUR ) );
$account1->decreaseBalance( new Amount( 10, Currency::USD ) );

// step 4
$account1->bank->setRate( Currency::EUR, Currency::RUB, 150 );
$account1->bank->setRate( Currency::USD, Currency::RUB, 100 );

// step 5
print_r( $account1->getBalance() );

// step 6
$account1->setBaseCurrency( Currency::EUR );
print_r( $account1->getBalance() );

// step 7
$amountRUB = $account1->decreaseBalance( new Amount( 1000, Currency::RUB ) );
$amountEUR = $account1->bank->convert( $amountRUB, Currency::EUR );
$account1->increaseBalance( $amountEUR );
print_r( $account1->getBalance() );

// step 8
$account1->bank->setRate( Currency::EUR, Currency::RUB, 120 );

// step 9
print_r( $account1->getBalance() );

// step 10
$account1->setBaseCurrency( Currency::RUB );
$account1->removeCurrency( Currency::EUR );
$account1->removeCurrency( Currency::USD );
print_r( $account1->getCurrencies() );
print_r( $account1->getBalance() );