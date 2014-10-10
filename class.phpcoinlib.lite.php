<?php
/*
 *	PHPcoinlibLite	-	A class for convenient interfacing with Bitcoin and other cryptocurrency daemons.
 *
 *	@author		Eli Lahr <hifieli2@gmail.com>
 *	@version	1.1 public
 *	@created	Dec 11 2013
 *
 */

class PHPcoinlib {
	
	public $conf	=	array(
		// Essentials
		'rpchost'		=> '127.0.0.1',		// IP or hostname where your bitcoind is installed and listening. use 127.0.0.1 or localhost if applicable. See 'rpcallowip=' in bitcoind.conf
		'rpcport'		=> 8332,			// port number that is listening for connections. See 'rpcport=' in bitcoind.conf.
		'rpcuser'		=> '',				// See 'rpcuser=' in bitcoind.conf.
		'rpcpass'		=> '',				// See 'rpcpassword=' in bitcoind.conf.
		
		
		// Options
		'rcptimeout'		=> 6,			// How long to wait for a response.
		'rpcssl'			=> false, 		// See the following in bitcoind.conf:	-rpcssl -rpcsslcertificatechainfile=<file.cert> -rpcsslprivatekeyfile=<file.pem>
		'stop_on_http_err'	=> false,		// Wether or not to stop on HTTP statuses other than 200. Not really recommended, but can be useful for some debugging situations.
		'throw_exceptions'	=> true,		// true = Throw Exceptions for errors; false = return strings on errors
		'curreny_name'		=> 'Bitcoin'	// The name of the cryptocurrency network we are connecting to. This is esoteric (a label), and has no impact on functionality.
		'curreny_symbol'	=> 'BTC'		// The symbol of the cryptocurrency network we are connecting to. This is esoteric (a label), and has no impact on functionality.
		
		
		// Security
		'rpcbannedcommands'	=>	array(		// Commands that are to be ignored!
				'sendfrom',
				'sendmany',
				'sendtoaddress',
				'setgenerate',
				'stop',
		)
		
	);
	
	// Class Variables
	public $rpcid				= 1;
	public $response			= null;
	public $response_json		= '';
	public $response_raw		= null;
	private $project_name		= 'PHPcoinlib';
	private $project_version	= '0.0.1a';

	public function __construct($user = null, $password = null, $host = null, $port = null, $rpcssl = false, $tout = null) {
		$this->setConnection($user, $password, $host, $port, $rpcssl, $tout);
	}

	public function setConnection($user = '', $password = '', $host = '127.0.0.1', $port = 8332, $rpcssl = false, $tout = 6) {
		$this->setConf('rpcuser',  $user);
		$this->setConf('rpcpass',  $password);
		$this->setConf('rpchost',  $host);
		$this->setConf('rpcport',  $port);
		$this->setConf('rpcssl',    $rpcssl);
		$this->setConf('rcptimeout', $tout);
	}
	
	public function setCurrency($curreny_name = 'Bitcoin', $curreny_symbol = 'BTC') {
		$this->setConf('curreny_name',		$curreny_name);
		$this->setConf('curreny_symbol',	$curreny_symbol);
	}
	
	public function setConf($key, $value = null) {
		if (isset($this->conf[$key])) {
			$this->conf[$key]	= $value;
			return true;
		} else {
			return false;
		}
	}

	public function getConf($key) {
		if (isset($this->conf[$key])) {
			return $this->conf[$key];
		} else {
			return null;
		}
	}	
	

	///////////////////////////////////////////////////////////////////////////
	// EZCOIN Request methods
	///////////////////////////////////////////////////////////////////////////


	/**
	 *	Allows you bypass $this->RawRequest('UndocumentedMethod') and instead do $this->UndocumentedMethod().
	 *  This method essentially is the 'lite' version of all the methods above :)
	 *  
	 *  @param <type> $name Parameter_Description
	 *  @param <type> $arguments Parameter_Description
	 *  
	 *  @return <mixed>		A valid PHP variable reprentation of the JSON-RPC response.
	 */
	public function __call($name, $arguments) {
		$this->_request(strtolower($name), $arguments);
		return $this->response;	
	}
	
	
	/**
	 *  This method can be used to pass any request to a bitcoin daemon. Particularly useful for altcoins, such as primecoin, which uses 'getprimespersec' rather than 'gethashespersec'
	 *  
	 *  @param <string>		$method	The JSON-RPC method
	 *  @param <array>		$params	The JSON-RPC parameter array 
	 *  
	 *  @return <mixed>		A valid PHP variable reprentation of the JSON-RPC response.
	 */
	public function RawRequest($method = 'getinfo', $params = array()) {
		$this->_request($method, $params);
	}
	
	/**
	 *  Coordinates the communication between this script and the outside world via JSON-RPC.
	 *  
	 *  @param <string>		$method	The JSON-RPC method
	 *  @param <array>		$params	The JSON-RPC parameter array
	 *  
	 *  @return <null>
	 */
	private function _request($method = 'getinfo', $params = array()) {
		$RawReturn 				= $this->__json_rpc_call($method, $params);
		$this->response  		= $RawReturn;
		$this->response_json	= @json_encode($RawReturn);
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	// JSON-RPC methods
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 *  Coordinates the preperation, connection, sending and recieving, and processing of the JSON-RPC call.
	 *  
	 *  @param <string>		$method	The JSON-RPC method
	 *  @param <array>		$params	The JSON-RPC parameter array
	 *  
	 *  @return <mixed>		string, int, float, or most likely, an array. A valid PHP variable reprentation of the JSON-RPC response. 
	 */
	private function __json_rpc_call($method, $params = array()) {	
		try {
		
			// Make sure we are allowed to make this request before we do anything else.
			if (in_array(strtolower($method), $this->conf['rpcbannedcommands'])) {
				throw new Exception(strtolower($method), 5);
			}	
		
			// Set the rpcid to a new random-ish number. This helps significantly reduce the likelihood of recieving the wrong response in a high-traffic situation.
			$this->rpcid	= rand(1, 4096);
			
			// Initialize the response as null.
			$this->response_raw	= null;
			
			// Create a stream resource for communicating with bitcoind via JSON-RPC
			$stream			=	$this->__json_rpc_connect();		

			// Send the HTTP request and collect the HTTP response.
			$this->response_raw	=	$this->__json_rpc_request($stream, $method, $params);

			// Process the HTTP response.
			$response		=	$this->__json_rpc_process_response($this->response_raw);
			
			// Return the processed response.
			return $response;

		} catch (Exception $ex) {
			//echo $ex->getMessage();
			
			if ($this->conf['throw_exceptions']) {
				throw new Exception($this->__json_rpc_err2str($ex->getCode()) . ' ' . $ex->getMessage(), $ex->getCode());
			}
			
			return $ex->getMessage();
		}
	}
	
	/**
	 *  Creates a valid stream resource representing a connection to a bitcoind daemon.
	 *  
	 *  
	 *  @return <resource> 
	 */
	private function __json_rpc_connect() {

		// Build the $remote_socket string, and $context
		$transport		= 'tcp';
		$context		= stream_context_create();
		if ($this->conf['rpcssl']) {
			$result		= stream_context_set_option($context, 'ssl', 'verify_host', true);
			$result		= stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
			$transport	= 'ssl';
		}
		$remote_socket	=	"{$transport}://{$this->conf['rpchost']}:{$this->conf['rpcport']}";
		
		// Initialize to -1
		$errno			=	-1;
		
		// Initialize to empty string.
		$errstr			=	'';
		
		// Build the socket stream resource.
		$stream			=	@stream_socket_client($remote_socket, $errno, $errstr, $this->conf['rcptimeout'], STREAM_CLIENT_CONNECT, $context);
		
	//	// Handle SSL (HTTPS)
	//	$crypto			=	@stream_socket_enable_crypto($stream, $this->conf['rpcssl'], STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
					
		// Handle Exceptions and issues.
		if (($errno > 0) || ($stream === false)) {
			//throw new Exception($errno  . ' ' . $errstr, 1);
			throw new Exception($errstr, 1);
		}
		
		// Return our happy and healthy stream.
		return $stream;
	}
	
	/**
	 *  Performs a JSON-RPC request via HTTP transport (optionally using HTTPS)
	 *  
	 *  @param <resource>	$stream	A valid stream resource, assumedly, built via __json_rpc_connect(), though a stream_socket_client() or other similar stream resource connected to a bitcoin daemon might be used.
	 *  @param <string>		$method	The JSON-RPC method
	 *  @param <array>		$params	The JSON-RPC parameter array
	 *  
	 *  @return <string> 	Raw JSON-RPC response
	 */
	private function __json_rpc_request($stream, $method, $params = array()) {
	
		// Just in case we passed an associative array, grab the values without the keys.
		$params 		=	array_values($params);
	
		// Initialize the response as null.
		$responseraw	=	null;
		
		// Initialize the HTTP response status code as 0.
		$http_code		=	0;
		
		// Initialize the HTTP response status text as empty.
		$http_resp		=	'';

		// Build the JSON-RPC request object
		$request 		=	array(
			'method'	=>	$method,
			'params'	=>	$params,
			'id'		=>	$this->rpcid
		);
		// Build the JSON-RPC object as a string. includes the \r\n because we need those in there for strlen  :)
		$request_enc	= json_encode($request) . "\r\n";
		
		// Build the full HTTP request which contains our JSON-RPC request.
		$request_full	=
			"POST / HTTP/1.1\r\n" . 
			"User-Agent: {$this->project_name}/v{$this->project_version}\r\n" . 
			"Host: 127.0.0.1\r\n" . 
			"Content-type: application/json\r\n" . 
			"Content-Length: " . strlen($request_enc) . "\r\n" . 
			"Connection: close\r\n" . 
			"Accept: application/json\r\n" . 
			"Authorization: Basic " . base64_encode($this->conf['rpcuser'] . ':' . $this->conf['rpcpass']) . "\r\n" . 
			"\r\n" . 
			$request_enc;
		
		// Poke the bear with the stick
		fwrite($stream, $request_full);
		
		// Collect the response
		while (!feof($stream)) {
			$buffer			=	fgets($stream, 32000);	// Torn on the buffer size: too large is inefficient, too small is potential problems with long responses.
			if (substr($buffer, 0, 8) == 'HTTP/1.1') {	// This should always be the first line of a valid response.
				$parts		=	explode(' ', $buffer);	// Bust it into an array.
				$http_proto	=	array_shift($parts);	// 'HTTP/1.1' usually.
				$http_code	=	array_shift($parts);	// 200 on a good day, anything else is bad news.
				$http_resp	=	implode(' ', $parts);	// 'OK' on a good day, 'Not Found' if you misspelled something, 'Internal Server Error' if something bad happened, etc.
			}
		}
		$responseraw	=	(isset($buffer)) ? $buffer : null;		// $buffer should be the last line of the response.
		fclose($stream);

		// Notable Exceptions
		if ($http_code != 200) {
			if ($this->conf['stop_on_http_err']) {
				//throw new Exception($http_code . ' ' . $http_resp, 2);
				throw new Exception($http_resp, 2);
			}
		}
		
		return $responseraw;
	}

    /**
     * Processes a JSON-RPC response and either excepts, or returns a php variable representing the valid result
     * 
     * @param <string> $responseraw		Specifically, a JSON-RPC string
     * 
     * @return <mixed>					string, int, float, or most likely, an array
     */
	private function __json_rpc_process_response($responseraw = null) {
	
		// Null in, null out.
		if (empty($responseraw)) {
			throw new Exception("Empty response from {$this->conf['currency_name']}.", 102);
		}
		
		// Assign and attempt to decode
		$response = $responseraw;
		$response = @json_decode(trim($response), true);

		// Notable Exceptions
		if (empty($response)) {
			throw new Exception($responseraw, 3);
		}
		if (!is_null($response['error'])) {
			//throw new Exception($response['error']['code']  . ' ' . $response['error']['message'], 4);
			throw new Exception($response['error']['message'], 4);
		}
		if ($response['id'] != $this->rpcid) {
			throw new Exception('Rq: ' . $this->rpcid . ' != Rx:' . $response['id'], 101);
		}
		
		// Return the response as either a string, int, float, or most likely, an array. (instead of a JSON array)
		if (!empty($response['result'])) {
			return $response['result'];
		}
		
		// Still here? not likely, but, just in case, make sure we 
		return null;
	}
	
    /**
     * Returns the associated error message for the given error code.
     * 
     * @param <int> $code	The error code
     * 
     * @return <string>		The associated error message
     */
	private function __json_rpc_err2str($code = 0){
		$ex_codes = array(
		//	0	=>	'reserved',
			1	=>	'Unable to establish connection.',
			2	=>	'HTTP request returned an error.',
			3	=>	'Unable to decode response.',
			4	=>	'RPC request returned an error.',
			5	=>	'Method restricted.',
			6	=>	'',
			7	=>	'',
			8	=>	'',
			9	=>	'',
			10	=>	'',
			101	=>	'Incorrect response id.',
			102	=>	"Empty response from {$this->conf['currency_name']}.",
		);
		
		return (isset($ex_codes[$code])) ? $ex_codes[$code] : '';
	}

	
	///////////////////////////////////////////////////////////////////////////
	// base58 Convenience methods
	///////////////////////////////////////////////////////////////////////////
		
	
	public function base58_encode($number) {
		$alphabet	= '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
		$alphalen	= strlen($alphabet);
		$encoded	= '';
	
		while ($number >= $alphalen) {
			$div		= intval($number / $alphalen);
			$place		= ($number - ($alphalen * $div));
			$encoded	= $alphabet[$place] . $encoded;
			$number		= $div;
		}
	
		if ($number > 0) {
			$encoded	= $alphabet[$number] . $encoded;
		}
	 
		return $encoded;
	}
	 
	public function base58_decode($encoded) {
		$alphabet	= '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
		$alphalen	= strlen($alphabet);
		$enclen		= strlen($encoded) - 1;
		$decoded	= 0;
		$multi		= 1;
	
		for ($i=$enclen; $i>=0; $i--) {
			$decoded	+= $multi * strpos($alphabet, $encoded[$i]);
			$multi		=  $multi * $alphalen;
		}
	
		return $decoded;
	}
	
	
}
