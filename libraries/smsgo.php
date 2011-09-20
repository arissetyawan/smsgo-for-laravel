<?php
/**
 * Smsgo library
 *
 * Simple library for sending SMS using http://www.smsgo.co.kr API
 *
 * @version    1.0
 * @author     mywizz
 */

use \System\Config;

class Smsgo {
	
	/**
	 * Unknown error code
	 *
	 * @const  integer
	 */
	const UNKNOWN_ERROR_CODE = -1;
	
	/**
	 * Unknown error message
	 *
	 * @const  string
	 */
	const UNKNOWN_ERROR_MESSAGE = '알 수 없는 오류';
	
	/**
	 * The mobile phone numbers of recipients
	 *
	 * @var  array
	 */
	protected $to = array();
	
	/**
	 * The mobile number sending from
	 *
	 * @var  string
	 */
	protected $from = NULL;
	
	/**
	 * The message to be sent
	 *
	 * @var  string
	 */
	protected $msg = NULL;
	
	/**
	 * The result
	 *
	 * @var
	 */
	public $results = array();
	
	/**
	 * cURL options
	 *
	 * @var  array
	 */
	protected static $curl_option = array(
		CURLOPT_USERAGENT		=> '',
		CURLOPT_CONNECTTIMEOUT	=> 30,
		CURLOPT_TIMEOUT			=> 30,
		CURLOPT_RETURNTRANSFER	=> TRUE,
		CURLOPT_HTTPHEADER		=> array('Expect:'),
		CURLOPT_SSL_VERIFYPEER	=> FALSE,
		CURLOPT_HEADER			=> FALSE
	);
	
	/**
	 * Error codes returned by Smsgo API server
	 *
	 * @var  array
	 */
 	protected static $error_codes = array(
		'11' => '서비스 인증 코드 오류',
		'12' => '미 가입 계정',
		'13' => '미 승인 계정',
		'14' => '허용된 서버가 아님',
		'15' => '잔여 콜 부족',
		'16' => '메시지 발송 제한 계정',
		'17' => '서비스 유효기간 만료',
		'31' => '메시지 없음',
		'32' => '회신번호 없음',
		'33' => '수신번호 없음',
		'34' => '즉시, 예약 구분 값 없음',
		'35' => '회신번호 숫자 아님',
		'36' => '예약 날짜 오류',
		'38' => '메시지 길이제한 초과 (SMS : 80 bytes / LMS : 2000 bytes)',
		'39' => '수신 리스트의 항목이 모두 수신거부 리스트에 등록되어 있음',
		'42' => '데이터 고유 번호 없음',
		'43' => '존재하지 않는 데이터의 취소 요청',
		'44' => '고유번호 규칙 오류',
		'62' => '수신거부로 등록된 번호가 포함되어 있음'
	);

	// ---------------------------------------------------------------------
	
	/**
	 * Create Smsgo instance
	 *
	 * @return  void
	 */
	public static function make()
	{
		return new static();
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Sets mobile number sending from
	 *
	 * @param   string
	 * @return  mixed
	 */
	public function from($from)
	{
		$this->from = $from;
		return $this;
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Sets message to be sent
	 *
	 * @param   string
	 * @return  mixed
	 */
	public function message($msg = NULL)
	{
		if ( ! is_null($msg) and strlen(trim($msg)) > 0)
		{
			$this->msg = $msg;
		}
		
		return $this;
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Sets message from templates
	 *
	 * @param   string  $name  The name of template to be used
	 * @param   array   $data  Data for replacement
	 * @return  mixed
	 */
	public function template($name, $data = array())
	{
		if (is_null($tpls = Config::get('smsgo.templates')) or ! isset($tpls[$name]))
		{
			throw new SmsgoException('Template [' . $name . '] not found');
		}
		
		$tpl = $tpls[$name];
		$body = $tpl['body'];
		if ( ! empty($data))
		{
			foreach ($data as $search => $replacement)
			{
				$body = str_replace('{'.$search.'}', $replacement, $body);
			}
		}
		
		return $this->message($body);
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Add a mobile number
	 *
	 * @param   string
	 * @return  mixed
	 */
	
	public function to($to = NULL)
	{
		if ( ! is_null($to))
		{
			if (is_array($to))
			{
				foreach ($to as $number)
				{
					return $this->to($number);
				}
			}
			
			$to = trim(preg_replace('/[^0-9]/', '', $to));
			
			if ( ! in_array($to, $this->to) and strlen($to) > 0)
			{
				$this->to[] = $to;
			}
		}
		
		return $this;
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Send SMS
	 *
	 * @return  mixed
	 */
	public function send()
	{
		$this->results = array();
		foreach ($this->to as $to)
		{
			$this->results[$to] = $this->_send($to);
		}
		return $this;
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Returns TRUE on all the SMS sent successfully, otherwise returns FALSE
	 *
	 * @return  bool
	 */
	public function ok()
	{
		foreach ($this->results as $result)
		{
			if ($result['result'] === FALSE)
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	// ---------------------------------------------------------------------
	
	/**
	 * Send SMS
	 *
	 * @param   string  The mobile number message will be sent to
	 * @return  mixed
	 */
	protected function _send($to)
	{
		$params = array(
				'SUserID' => Config::get('smsgo.user_id'),
				'ServiceAC' => Config::get('smsgo.application_key'),
				'ReturnURL' => 'XML',
				'ReceiverList' => $to,
				'CallbackPhoneNo' => $this->from,
				'MessageType' => 'S',
				'SendMsg' => $this->msg,
				'Subject' => '',
				'ReservedChk' => '0',
				'ResDate' => '',
				'UserData1' => '',
				'UserData2' => '',
				'UserData3' => ''
			);
			
		$curl = curl_init();
		curl_setopt_array($curl, static::$curl_option);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Referer: ' . Config::get('application.url')));
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_URL, Config::get('smsgo.url'));
		$res = curl_exec($curl);
		curl_close ($curl);
	
		if (preg_match("/<ReturnCode>(.*?)<\/ReturnCode>/is", $res, $match))
		{
			$return_code = trim($match['1']);

			if ($return_code and $return_code == '00')
			{
				return array(
					'result' => TRUE
				);
			}
			else if (preg_match("/<ErrorCode>(.*?)<\/ErrorCode>/is", $res, $error))
			{
				$error_code = trim($error['1']);
				
				if ( ! empty(self::$error_codes[strval($error_code)]))
				{
					$error_message = self::$error_codes[strval($error_code)];
				}
				else
				{
					$error_code = static::UNKOWN_ERROR_CODE;
					$error_message = static::UNKOWN_ERROR_MESSAGE;
				}
				
				return array(
					'result' => FALSE,
					'error_code' => intval($error_code),
					'error_message' => $error_message
				);
			}
		}

		return array(
			'result' => FALSE,
			'error_code' => static::UNKOWN_ERROR_CODE,
			'error_message' => static::UNKOWN_ERROR_MESSAGE
		);
	}
}

class SmsgoException extends \Exception {}