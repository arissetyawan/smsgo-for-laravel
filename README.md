# Smsgo library for Laravel

Send SMS using [Smsgo](http://www.smsgo.co.kr) API


## Requirements

- Smsgo account & payment for API use
- Laravel 1.5.x
- cURL


## Installation

- clone this repo or copy all directories&files to your _application_ directory
- Register to Smsgo and get API key
- Edit _smsgo.php_ in your _config_ diretory


## Usage

    $sms = Smsgo::make()
            ->from('1004')
            ->to('010-1234-5677')
            ->message('Hello world')
            ->send();

    if ($sms->ok())
    {
        // message sent successfully
    }
    else
    {
        var_dump($sms->results);
    }


## Using template

Define your template in _smsgo.php_ in your _config_ directory.

	'templates' => array(
        'my_template' => array(
            'title' => 'My template',
            'body' => 'Hi, I am {MYNAME}!'
 		)
 	),
 	

Any keyword wrapped in { and } will be replaced with array passed in `template()`
 
    $sms = Smsgo::make()
            ->from('1004')
            ->to('010-1234-5677')
            ->template('my_template', array(
				'MYNAME' => 'Yunseok Kim'
            ))
            ->send();
            
## Bug report / Feedback

- [Github issues](https://github.com/mywizz/smsgo-for-laravel/issues)
