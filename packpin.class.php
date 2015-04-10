<?php

/**
 * Class PackpinREST
 * @author Packpin
 * @url	http://github.com/packpin/api-example
 */

class PackpinREST {
	/**
	 * CURL Object
	 * @var resource
	 */
	protected $_curlObj;

	/**
	 * API Resource URL
	 * @var
	 */
	protected $_httpURL;

	/**
	 * Storing response
	 * @var
	 */
	protected $_response;

	/**
	 * Storing HTTP response code for error reporting
	 * @var integer
	 */
	protected $_lastStatusCode;

	/**
	 * API Endpoint URL
	 * @var string
	 */
	protected $_endpoint = "https://api.packpin.com/v2";

	/**
	 * Predefined HTTP Headers for the Call.
	 * Everything API dependant is Initiated later.
	 * @var array
	 */
	protected $_httpHeaders = array(
		"Cache-Control: max-age=0",
		"Connection: keep-alive",
		"Keep-Alive: 300",
		"Content-Type: application/json",
	);

	/**
	 * Possible HTTP Response code message
	 * @var array
	 */
	protected $_responseHeaderCodes = array(
		'200' => 'OK - The request was successful (some API calls may return 201 instead)',
		'201' => 'Created - The request was successful and a resource was created',
		'204' => 'No Content - The request was successful but there is no representation to return (that is, the response is empty).',
		'400' => 'Bad Request - The request could not be understood or was missing required parameters.',
		'401' => 'Unauthorized - Authentication failed or user does not have permissions for the requested operation.',
		'402' => 'Payment Required - Payment required.',
		'403' => 'Forbidden - Access denied.',
		'404' => 'Not Found - Resource was not found.',
		'405' => 'Method Not Allowed - Requested method is not supported for the specified resource.',
		'429' => 'Too Many Requests - Exceeded API limits. Pause requests, wait one minute, and try again.',
		'500' => 'Server error - Server error. Contact packpin@packpin.com',
		'503' => 'Service Unavailable - The service is temporary unavailable (e.g. scheduled Platform Maintenance). Try again later.',
	);

	/**
	 * Local code error messages
	 * @var array
	 */
	protected $_localErrors = array(
		'NO_API_KEY' => 'Please set the API Key!',
		'CURL_ERROR' => 'Curl failed with error #%d: %s',
		'REQUEST_TEMP_ERROR' => 'Could not write %s data to php://temp',
		'NO_DATA' => 'You must provide POST Data!',
		'API_ERROR'	=> 'An error occured "%s", resource URL - "%s"'
	);

	/**
	 * HTTP Codes considered as OK
	 * @var array
	 */
	protected $_okResponseHeaderCodes = array('200', '201', '204');

	/**
	 * Initiate CURL Object and settings, prepare headers
	 *
	 * @param string $apiKey
	 * @throws Exception
	 */
	public function __construct($apiKey = '') {
		if (empty($apiKey)) {
			throw new Exception($this->_localErrors['NO_API_KEY'], 1);
		}

		$this->_curlObj = curl_init();

		$this->_httpHeaders[] = "packpin-api-key: " . $apiKey;

		curl_setopt($this->_curlObj, CURLOPT_HEADER, false);
		curl_setopt($this->_curlObj, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->_curlObj, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($this->_curlObj, CURLOPT_RETURNTRANSFER, true);
	}

	/**
	 * Every request goes through this method
	 *
	 * @param $httpURL
	 * @param $httpMethod
	 * @param string $httpData
	 * @return mixed
	 * @throws Exception
	 */
	public function execRequest($httpURL, $httpMethod, $httpData = '') {
		try {
			$this->_httpURL = $this->_endpoint . $httpURL;

			switch (strtolower($httpMethod)) {
				case 'get':
					$this->_get($httpData, $this->_httpURL);
					break;
				case 'post':
					if (empty($httpData))
						throw new Exception($this->_localErrors['NO_DATA'], 1);

					$httpData = $this->prepareJsonData($httpData);

					$this->_post($httpData);
					break;
				case 'put':
					if (empty($httpData))
						throw new Exception($this->_localErrors['NO_DATA'], 1);

					$httpData = $this->prepareJsonData($httpData);
					$this->_put($httpData);
					break;
				case 'delete':
					$this->_delete();
					break;
			}
			curl_setopt($this->_curlObj, CURLOPT_URL, $this->_httpURL);

			if ($httpData) {
				$this->_httpHeaders[] = 'Content-Length: ' . strlen($httpData);
			}

			curl_setopt($this->_curlObj, CURLOPT_HTTPHEADER, $this->_httpHeaders);

			$response = curl_exec($this->_curlObj);
			$this->_response = $response;
			$this->_lastStatusCode = curl_getinfo($this->_curlObj, CURLINFO_HTTP_CODE);

			if (FALSE === $response) {
				throw new Exception(curl_error($this->_curlObj), curl_errno($this->_curlObj));
			}
		} catch (Exception $e) {
			trigger_error(sprintf(
				$this->_localErrors["CURL_ERROR"],
				$e->getCode(), $e->getMessage()),
				E_USER_ERROR);
		}

		if (in_array($this->_lastStatusCode, $this->_okResponseHeaderCodes)) {
			return json_decode($response);
		} else {
			throw new Exception(sprintf($this->_localErrors['API_ERROR'], $this->_responseHeaderCodes[$this->_lastStatusCode], $this->_httpURL), 1);
			return false;
		}
	}

	/**
	 * Prepare for HTTP GET Method
	 *
	 * @param null $data
	 * @param $url
	 */
	protected function _get($data = null, &$url) {
		curl_setopt($this->_curlObj, CURLOPT_HTTPGET, true);
		if ($data != null) {
			$returnData = $this->prepareUrlData($data);
			$url .= "?" . $returnData;
		}
	}

	/**
	 * Prepare for HTTP POST Method
	 *
	 * @param string $data
	 * @throws Exception
	 */
	protected function _post($data = "") {
		curl_setopt($this->_curlObj, CURLOPT_POST, true);
		curl_setopt($this->_curlObj, CURLOPT_POSTFIELDS, $data);
	}

	/**
	 * Prepare for HTTP PUT Method
	 *
	 * @param null $data
	 * @throws Exception
	 */
	protected function _put($data = null) {
		curl_setopt($this->_curlObj, CURLOPT_PUT, true);
		$resource = fopen('php://temp', 'rw');
		$bytes = fwrite($resource, $data);
		rewind($resource);
		if ($bytes !== false) {
			curl_setopt($this->_curlObj, CURLOPT_INFILE, $resource);
			curl_setopt($this->_curlObj, CURLOPT_INFILESIZE, $bytes);
		} else {
			throw new Exception(sprintf($this->_localErrors['REQUEST_TEMP_ERROR'], "DELETE"));
		}
	}

	/**
	 * Prepare for HTTP DELETE Method
	 *
	 * @param null $data
	 * @throws Exception
	 */
	protected function _delete($data = null) {
		curl_setopt($this->_curlObj, CURLOPT_CUSTOMREQUEST, 'DELETE');
		if ($data != null) {
			$resource = fopen('php://temp', 'rw');
			$bytes = fwrite($resource, $data);
			rewind($resource);
			if ($bytes !== false) {
				curl_setopt($this->_curlObj, CURLOPT_INFILE, $resource);
				curl_setopt($this->_curlObj, CURLOPT_INFILESIZE, $bytes);
			} else {
				throw new Exception(sprintf($this->_localErrors['REQUEST_TEMP_ERROR'], "DELETE"));
			}
		}
	}

	/**
	 * Prepare URL Encoded data for HTTP GET Method
	 *
	 * @param $inData
	 * @return string
	 */
	protected function prepareUrlData($inData) {
		if (is_array($inData)) {
			$data = http_build_query($inData, 'arg');
		} else {
			parse_str($inData, $tmp);
			$data = "";
			$first = true;
			foreach ($tmp as $k => $v) {
				if (!$first) {
					$data .= "&";
				}
				$data .= $k . "=" . urlencode($v);
				$first = false;
			}
		}
		return $data;
	}

	/**
	 * Prepare JSON Encoded data for HTTP POST Method
	 *
	 * @param $inData
	 * @return string
	 */
	protected function prepareJsonData($inData) {
		return (is_array($inData) && count($inData) > 0) ? json_encode($inData) : '{}';
	}
}