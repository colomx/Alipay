Alipay
======

This is the SDK of Alipay in Laravel5/Lumen.

The extension pack will make it convenient to pay via Alipay in Laravel5/Lumen framework.

## Install

```
composer require latrell/alipay dev-master
```

Update your repo ```composer update``` or fresh install ```composer install```.


## How to use

To use Alipay SDK service provider，you need to register to Laravel/Lumen service provider list.
There are 2 ways to make it.

### Laravel
Open `config/app.php` and find a array which key is `providers`, then add the service provider to the array.

```php
    'providers' => [
        // ...
        'Latrell\Alipay\AlipayServiceProvider',
    ]
```

Excute command `php artisan vendor:publish` and publish the configuration to your project.

### Lumen
Register the service in`bootstrap/app.php`.

```php
//Register Service Providers
$app->register(Latrell\Alipay\AlipayServiceProvider::class);
```

Since `artisan` in Lumen does not support`vendor:publish`, you should copy confiugrations under `src/config`to the folder `config`of your project.
And rename`config.php` to `latrell-alipay.php`,
rename `mobile.php` to `latrell-alipay-mobile.php`,
rename `web.php` to `latrell-alipay-web.php`.

### Introduction
File `config/latrell-alipay.php` is public configuration, `config/latrell-alipay-web.php` is the SDK configuration for Web, `config/latrell-alipay-mobile.php` is the SDK configuration for Mobile.

## Example

### Payment Register

#### Web

```php
	// Create Payment
	$alipay = app('alipay.web');
	$alipay->setOutTradeNo('order_id');
	$alipay->setTotalFee('order_price');
	$alipay->setSubject('goods_name');
	$alipay->setBody('goods_description');
	
	$alipay->setQrPayMode('4'); //Optional, add it to support QR scan payment.

	// Redirect To Pay Link
	return redirect()->to($alipay->getPayLink());
```

#### Mobile

```php
	// Create Payment
	$alipay = app('alipay.mobile');
	$alipay->setOutTradeNo('order_id');
	$alipay->setTotalFee('order_price');
	$alipay->setSubject('goods_name');
	$alipay->setBody('goods_description');

	// Return the signed payment parameters to Alipay Mobile SDK
	return $alipay->getPayPara();
```

### Result Notification

#### Web

```php
	/**
	 * Asynchronous Notification
	 */
	public function webNotify()
	{
		//Verify Request
		if (! app('alipay.web')->verify()) {
			Log::notice('Alipay notify post data verification fail.', [
				'data' => Request::instance()->getContent()
			]);
			return 'fail';
		}

		// Judge the notification type
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: Successfully made payment, get trade number and process actions.
				Log::debug('Alipay notify post data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}
	
		return 'success';
	}

	/**
	 * Synchronization Notification
	 */
	public function webReturn()
	{
		// Verify Request
		if (! app('alipay.web')->verify()) {
			Log::notice('Alipay return query data verification fail.', [
				'data' => Request::getQueryString()
			]);
			return view('alipay.fail');
		}

		// Judge the notification type
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: Successfully made payment, get trade number and process actions.
				Log::debug('Alipay notify get data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}

		return view('alipay.success');
	}
```

#### Mobile

```php
	/**
	 * Alipay Asynchronous Notification
	 */
	public function alipayNotify()
	{
		// Verify Request
		if (! app('alipay.mobile')->verify()) {
			Log::notice('Alipay notify post data verification fail.', [
				'data' => Request::instance()->getContent()
			]);
			return 'fail';
		}

		// Judge the notification type
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: Successfully made payment, get trade number and process actions.
				Log::debug('Alipay notify get data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}

		return 'success';
	}
```
