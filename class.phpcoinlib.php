<?php
/*
 *	PHPcoinlib	-	A class for convenient interfacing with Bitcoin and other cryptocurrency daemons.
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
	// Coin Methods
	///////////////////////////////////////////////////////////////////////////
	
	
	
	// addmultisigaddress <nrequired> <'["key","key"]'> [account]
	// Add a nrequired-to-sign multisignature address to the wallet"
	// each key is a Bitcoin address or hex-encoded public key
	// If [account] is specified, assign address to [account].
	public function AddMultiSigAddress($nrequired, $keys, $account = '') {
		$keys	=	(!is_string($keys))	? json_encode($keys) : $keys;
		$this->RawRequest('addmultisigaddress', array($nrequired, $keys, $account));
		return $this->response;
	}


	// addnode <node> <add|remove|onetry>
	// Attempts add or remove <node> from the addnode list or try a connection to <node> once.
	public function AddNode($node, $aro = 'add') {
		$this->RawRequest('addnode', array($node, $aro));
		return $this->response;
	}


	// backupwallet <destination>
	// Safely copies wallet.dat to destination, which can be a directory or a path with filename.
	public function BackupWallet($destination) {
		$this->RawRequest('backupwallet', array($destination));
		return $this->response;
	}


	// createmultisig <nrequired> <'["key","key"]'>
	// Creates a multi-signature address and returns a json object
	// with keys:
	// address : bitcoin address
	// redeemScript : hex-encoded redemption script
	public function CreateMultiSig($nrequired, $keys) {
		$keys	=	(!is_string($keys))	? json_encode($keys) : $keys;
		$this->RawRequest('createmultisig', array($nrequired, $keys));
		return $this->response;
	}


	// createrawtransaction [{"txid":txid,"vout":n},...] {address:amount,...}
	// Create a transaction spending given inputs
	// (array of objects containing transaction id and output number),
	// sending to given address(es).
	// Returns hex-encoded raw transaction.
	// Note that the transaction's inputs are not signed, and
	// it is not stored in the wallet or transmitted to the network.
	public function CreateRawTransaction($inputs, $addr_amount) {
		$inputs			=	(!is_string($inputs))		? json_encode($inputs)		: $inputs;
		$addr_amount	=	(!is_string($addr_amount))	? json_encode($addr_amount) : $addr_amount;
		$this->RawRequest('createrawtransaction', array($inputs, $addr_amount));
		return $this->response;
	}


	// decoderawtransaction <hex string>
	// Return a JSON object representing the serialized, hex-encoded transaction.
	public function DecodeRawTransaction($hex) {
		$this->RawRequest('decoderawtransaction', array($hex));
		return $this->response;
	}


	// decodescript <hex string>
	// Decode a hex-encoded script.
	public function DecodeScript($hex) {
		$this->RawRequest('decodescript', array($hex));
		return $this->response;
	}


	// dumpprivkey <bitcoinaddress>
	// Reveals the private key corresponding to <bitcoinaddress>.
	public function DumpPrivKey($bitcoinaddress) {
		$this->RawRequest('dumpprivkey', array($bitcoinaddress));
		return $this->response;
	}


	// dumpwallet <filename>
	// Dumps all wallet keys in a human-readable format.
	public function DumpWallet($filename) {
		$this->RawRequest('dumpwallet', array($filename));
		return $this->response;
	}


	// encryptwallet <passphrase>
	// Encrypts the wallet with <passphrase>.
	public function EncryptWallet($passphrase) {
		$this->RawRequest('encryptwallet', array($passphrase));
		return $this->response;
	}


	// getaccount <bitcoinaddress>
	// Returns the account associated with the given address.
	public function GetAccount($bitcoinaddress) {
		$this->RawRequest('getaccount', array($bitcoinaddress));
		return $this->response;
	}


	// getaccountaddress <account>
	// Returns the current Bitcoin address for receiving payments to this account.
	public function GetAccountAddress($account) {
		$this->RawRequest('getaccountaddress', array($account));
		return $this->response;
	}

	// getaddednodeinfo
	// Returns information about the given added node, or all added nodes
	// (note that onetry addnodes are not listed here)
	// If dns is false, only a list of added nodes will be provided,
	// otherwise connected information will also be available.
	public function GetAddedNodeInfo() {
		$this->RawRequest('getaddednodeinfo');
		return $this->response;
	}


	// getaddressesbyaccount <account>
	// Returns the list of addresses for the given account.
	public function GetAddressesByAccount($account) {
		$this->RawRequest('getaddressesbyaccount', array($account));
		return $this->response;
	}



	// getbalance [account] [minconf=1]
	// If [account] is not specified, returns the server's total available balance.
	// If [account] is specified, returns the balance in the account.
	public function GetBalance($account = null, $minconf = null) {
		if (empty($account) && empty($minconf)) {
			$this->RawRequest('getbalance');
		} else {
			if (!empty($account) && !empty($minconf)) {
				$this->RawRequest('getbalance', array($account, $minconf));
			} else {
				if (!empty($account) && empty($minconf)) {
					$this->RawRequest('getbalance', array($account, 1));
				} else {
					$this->RawRequest('getbalance', array('', $minconf));
				}
			}
		}
		return $this->response;
	}

	// getbestblockhash
	// Returns the hash of the best (tip) block in the longest block chain.
	public function GetBestBlockHash() {
		$this->RawRequest('getbestblockhash');
		return $this->response;
	}


	// getblock <hash> [verbose=true]
	// If verbose is false, returns a string that is serialized, hex-encoded data for block <hash>.
	// If verbose is true, returns an Object with information about block <hash>.
	public function GetBlock($hash, $verbose = true) {
		$this->RawRequest('getblock', array($hash, $verbose));
		return $this->response;
	}


	// getblockcount
	// Returns the number of blocks in the longest block chain.
	public function GetBlockCount() {
		$this->RawRequest('getblockcount');
		return $this->response;
	}


	// getblockhash <index>
	// Returns hash of block in best-block-chain at <index>.
	public function GetBlockHash($index) {
		$this->RawRequest('getblockhash', array($index));
		return $this->response;
	}


	// getblocktemplate [params]
	// Returns data needed to construct a block to work on:
	  // "version" : block version
	  // "previousblockhash" : hash of current highest block
	  // "transactions" : contents of non-coinbase transactions that should be included in the next block
	  // "coinbaseaux" : data that should be included in coinbase
	  // "coinbasevalue" : maximum allowable input to coinbase transaction, including the generation award and transaction fees
	  // "target" : hash target
	  // "mintime" : minimum timestamp appropriate for next block
	  // "curtime" : current timestamp
	  // "mutable" : list of ways the block template may be changed
	  // "noncerange" : range of valid nonces
	  // "sigoplimit" : limit of sigops in blocks
	  // "sizelimit" : limit of block size
	  // "bits" : compressed target of next block
	  // "height" : height of the next block
	// See https://en.bitcoin.it/wiki/BIP_0022 for full specification.
	public function GetBlockTemplate($params) {
		$params	=	(!is_string($params))	? json_encode($params) : $params;
		$this->RawRequest('getblocktemplate', array($params));
		return $this->response;
	}


	// getconnectioncount
	// Returns the number of connections to other nodes.
	public function GetConnectionCount() {
		$this->RawRequest('getconnectioncount');
		return $this->response;
	}



	// getdifficulty
	// Returns the proof-of-work difficulty as a multiple of the minimum difficulty.
	public function GetDifficulty() {
		$this->RawRequest('getdifficulty');
		return $this->response;
	}
	
	public function GetDiff() {
		//not to be confused with GetDifficulty
		$this->RawRequest('getdifficulty');
		//return $this->response;
		$MiningInfo	=	$this->response;
		if (is_array($MiningInfo)) {
			return $MiningInfo['proof-of-work'];
		} else {
			return $MiningInfo;
		}
	}


	// getgenerate
	// Returns true or false.
	public function GetGenerate() {
		$this->RawRequest('getgenerate');
		return $this->response;
	}


	// gethashespersec
	// Returns a recent hashes per second performance measurement while generating.
	/**
	 *  
	 *  
	 *  
	 *  @return <type> Return_Description
	 */
	public function GetHashesPerSec() {
		$this->RawRequest('gethashespersec');
		return $this->response;
	}


	// getinfo
	// Returns an object containing various state info.
	public function GetInfo() {
		$this->RawRequest('getinfo');
		return $this->response;
	}


	// getmininginfo
	// Returns an object containing mining-related information.
	public function GetMiningInfo() {
		$this->RawRequest('getmininginfo');
		return $this->response;
	}


	// getnewaddress [account]
	// Returns a new Bitcoin address for receiving payments.  If [account] is specified (recommended), it is added to the address book so payments received with the address will be credited to [account].
	public function GetNewAddress($account = '') {
		$this->RawRequest('getnewaddress', array($account));
		return $this->response;
	}	
	
	public function GetNewDepositAddress() {
		return $this->GetNewAddress("");
	}


	// getpeerinfo
	// Returns data about each connected network node.
	public function GetPeerInfo() {
		$this->RawRequest('getpeerinfo');
		return $this->response;
	}


	// getrawchangeaddress
	// Returns a new Bitcoin address, for receiving change.  This is for use with raw transactions, NOT normal use.
	public function GetRawChangeAddress() {
		$this->RawRequest('getrawchangeaddress');
		return $this->response;
	}


	// getrawmempool
	// Returns all transaction ids in memory pool.
	public function GetRawMemPool() {
		$this->RawRequest('getrawmempool');
		return $this->response;
	}


	// getrawtransaction <txid> [verbose=0]
	// If verbose=0, returns a string that is
	// serialized, hex-encoded data for <txid>.
	// If verbose is non-zero, returns an Object
	// with information about <txid>.
	public function GetRawTransaction($txid, $verbose = 0) {
		$this->RawRequest('getrawtransaction', array($txid, $verbose));
		return $this->response;
	}


	// getreceivedbyaccount <account> [minconf=1]
	// Returns the total amount received by addresses with <account> in transactions with at least [minconf] confirmations.
	public function GetReceivedByAccount($account, $minconf = 1) {
		$this->RawRequest('getreceivedbyaccount', array($account, $minconf));
		return $this->response;
	}


	// getreceivedbyaddress <bitcoinaddress> [minconf=1]
	// Returns the total amount received by <bitcoinaddress> in transactions with at least [minconf] confirmations.
	public function GetReceivedByAddress($bitcoinaddress, $minconf = 1) {
		$this->RawRequest('getreceivedbyaddress', array($bitcoinaddress, $minconf));
		return $this->response;
	}


	// gettransaction <txid>
	// Get detailed information about in-wallet transaction <txid>
	public function GetTransaction($txid) {
		$this->RawRequest('gettransaction', array($txid));
		return $this->response;
	}


	// gettxout <txid> <n> [includemempool=true]
	// Returns details about an unspent transaction output.
	public function GetTxOut($txid, $n, $includemempool = true) {
		$this->RawRequest('gettxout', array($txid, $verbose, $includemempool));
		return $this->response;
	}


	// gettxoutsetinfo
	// Returns statistics about the unspent transaction output set.
	public function gettxoutsetinfo() {
		$this->RawRequest('gettxoutsetinfo');
		return $this->response;
	}


	// getwork [data]
	// If [data] is not specified, returns formatted hash data to work on:
	  // "midstate" : precomputed hash state after hashing the first half of the data (DEPRECATED)
	  // "data" : block data
	  // "hash1" : formatted hash buffer for second hash (DEPRECATED)
	  // "target" : little endian hash target
	// If [data] is specified, tries to solve the block and returns true if it was successful.
	public function GetWork($data = '') {
		if (empty($data)) {
			$this->RawRequest('getwork');
		} else {
			$this->RawRequest('getwork', array($data));
		}
		return $this->response;
	}


	// help [command]
	// List commands, or get help for a command.
	public function Help($command = '') {
		if (empty($command)) {
			$this->RawRequest('help');
		} else {
			$this->RawRequest('help', array($command));
		}
		return $this->response;
	}


	// importprivkey <bitcoinprivkey> [label] [rescan=true]
	// Adds a private key (as returned by dumpprivkey) to your wallet.
	public function ImportPrivKey($bitcoinprivkey, $label = '', $rescan = true) {
		$this->RawRequest('importprivkey', array($bitcoinprivkey, $label, $rescan));
		return $this->response;
	}


	// importwallet <filename>
	// Imports keys from a wallet dump file (see dumpwallet).
	public function ImportWallet($filename) {
		$this->RawRequest('importwallet', array($filename));
		return $this->response;
	}


	// keypoolrefill [new-size]
	// Fills the keypool.
	public function KeypoolRefill($new_size = 0) {
		if (empty($new_size)) {
			$this->RawRequest('keypoolrefill');
		} else {
			$this->RawRequest('keypoolrefill', array($new_size));
		}
		return $this->response;
	}


	// listaccounts [minconf=1]
	// Returns Object that has account names as keys, account balances as values.
	public function ListAccounts($minconf = 1) {
		$this->RawRequest('listaccounts', array($minconf));
		return $this->response;
	}


	// listaddressgroupings
	// Lists groups of addresses which have had their common ownership
	// made public by common use as inputs or as the resulting change
	// in past transactions
	public function ListAddressGroupings() {
		$this->RawRequest('listaddressgroupings');
		return $this->response;
	}

	// listlockunspent
	// Returns list of temporarily unspendable outputs.
	
	public function ListLockUnspent() {
		$this->RawRequest('listlockunspent');
		return $this->response;
	}

    /**
     * Fetches a list of the total amount of coins received by account.
     * 
	 * listreceivedbyaccount [minconf=1] [includeempty=false]
	 * [minconf] is the minimum number of confirmations before payments are included.
	 * [includeempty] whether to include accounts that haven't received any payments.
	 * Returns an array of objects containing:
	   * "account" : the account of the receiving addresses
	   * "amount" : total amount received by addresses with this account
	   * "confirmations" : number of confirmations of the most recent transaction included

     * @param <type> $minconf  
     * @param <type> $includeempty  
     * 
     * @return <type>
     */
	public function ListReceivedByAccount($minconf = 1, $includeempty = false) {
		$this->RawRequest('listreceivedbyaccount', array($minconf, $includeempty));
		return $this->response;
	}
	  
	 
	/**
	 * Lists the total amount of coins received by address.
	 * listreceivedbyaddress [minconf=1] [includeempty=false]
	 * [minconf] is the minimum number of confirmations before payments are included.
	 * [includeempty] whether to include addresses that haven't received any payments.
	 * Returns an array of objects containing:
	   * "address" : receiving address
	   * "account" : the account of the receiving address
	   * "amount" : total amount received by the address
	   * "confirmations" : number of confirmations of the most recent transaction included
	   * "txids" : list of transactions with outputs to the address
     * 
     * @param <int> $minconf  
     * @param <bool> $includeempty  
     * 
     * @return <array>
     */
	public function ListReceivedByAddress($minconf = 1, $includeempty = false) {
		$this->RawRequest('listreceivedbyaddress', array($minconf, $includeempty));
		return $this->response;
	}


    /**
     * Get all wallet transactions in blocks since block [blockhash], or all wallet transactions if omitted
     * 
     * @param <string> $blockhash  
     * @param <int> $target_confirmations  
     * 
     * @return <array>
     */
	public function ListSinceBlock($blockhash = '', $target_confirmations = 6) {
	
		if (empty($blockhash)) {
			$this->RawRequest('listsinceblock', array());
		} else {
			$this->RawRequest('listsinceblock', array($blockhash, $target_confirmations));
		}
		return $this->response;
	}


    /**
     * Returns up to [count] most recent transactions skipping the first [from] transactions for account [account].
     * 
     * @param <string> $account  
     * @param <int> $count  
     * @param <int> $from  
     * 
     * @return <array>
     */
	public function ListTransactions($account = '', $count = 10, $from = 0) {
		$this->RawRequest('listtransactions', array($account, (int)$count, (int)$from));
		return $this->response;
	}



    /**
	 * listunspent [minconf=1] [maxconf=9999999]  ["address",...]
	 * Returns array of unspent transaction outputs
	 * with between minconf and maxconf (inclusive) confirmations.
	 * Optionally filtered to only include txouts paid to specified addresses.
	 * Results are an array of Objects, each of which has:
	 * {txid, vout, scriptPubKey, amount, confirmations}
     * 
     * @param <int> $minconf  
     * @param <int> $maxconf  
     * @param <string/array> $addrs  
     * 
     * @return <array>
     */
	public function listunspent($minconf = 1, $maxconf = 9999999, $addrs = null) {
		if (empty($addrs)) {
			$this->RawRequest('listunspent', array((int)$minconf, (int)$maxconf));
		} else {
			$addrs	=	(!is_string($addrs)) ? json_encode($addrs) : $addrs;
			$this->RawRequest('listunspent', array((int)$minconf, (int)$maxconf, $addrs));
		}
		return $this->response;
	}


    /**
     * Updates list of temporarily unspendable outputs.
     * 
     * @param <bool> $unlock  
     * @param <type> $objs		[array-of-Objects]
     * 
     * @return <type>
     */
	public function LockUnspent($unlock = true, $objs) {
		$objs	=	(!is_string($objs)) ? json_encode($objs) : $objs;
		$this->RawRequest('lockunspent', array((bool)$unlock, $objs));
		return $this->response;
	}


    /**
     * Move coins from one account in your wallet to another.
     * 
     * @param <string>	$fromaccount	
     * @param <string>	$toaccount		
     * @param <float>	$amount		
     * @param <int>		$minconf		
     * @param <string>	$comment		
     * 
     * @return <type>
     */
	public function Move($fromaccount, $toaccount, $amount, $minconf = 1, $comment = '') {
		$this->RawRequest('move', array($fromaccount, $toaccount, (float)$amount, (int)$minconf, $comment));
		return $this->response;
	}


    /**
     * Send coins to an address from a specified account.
     * 
     * @param <string>	$fromaccount 
     * @param <string>	$tobitcoinaddress 
     * @param <float>	$amount				real and is rounded to the nearest 0.00000001
     * @param <int>		$minconf  
     * @param <string>	$comment  
     * @param <string>	$comment_to  
     * 
     * @return <type>
     */
	public function SendFrom($fromaccount, $tobitcoinaddress, $amount, $minconf = 1, $comment = '', $comment_to = '') {
		$this->RawRequest('sendfrom', array($fromaccount, $tobitcoinaddress, (float)$amount, (int)$minconf, $comment, $comment_to));
		return $this->response;
	}


    /**
     * Send coins to many addresses in a single transaction.
     * 
     * @param <string>			$fromaccount	an account (not an address)
     * @param <string/array>	$addr_amount	if string, '{"address":amount,...}' 
     * @param <int>				$minconf		minimum confirmations
     * @param <string>			$comment		transaction comment
     * 
     * @return <type>
     */
	public function SendMany($fromaccount, $addr_amount, $minconf = 1, $comment = '') {
		$addr_amount	=	(!is_string($addr_amount)) ? json_encode($addr_amount) : $addr_amount;
		$this->RawRequest('sendmany', array($fromaccount, $addr_amount, (int)$minconf, $comment));
		return $this->response;
	}


    /**
     * Submits raw transaction (serialized, hex-encoded) to local node and network.
     * 
     * @param <string> $hex 
     * 
     * @return <type>
     */
	public function SendRawTransaction($hex) {
		$this->RawRequest('sendrawtransaction', array($hex));
		return $this->response;
	}



    /**
     * Send coins to an address. Coins are sent from the "" account.
     * 
     * @param <string> $bitcoinaddress	
     * @param <string> $amount			real and is rounded to the nearest 0.00000001
     * @param <string> $comment			
     * @param <string> $comment_to		
     * 
     * @return <type>
     */
	public function SendToAddress($bitcoinaddress, $amount, $comment = '', $comment_to = '') {
		$this->RawRequest('sendtoaddress', array($bitcoinaddress, (float)$amount, $comment, $comment_to));
		return $this->response;
	}



    /**
     * Sets the account associated with the given address.
     * 
     * @param <string> $bitcoinaddress
     * @param <string> $account
     * 
     * @return <type>
     */
	public function SetAccount($bitcoinaddress, $account) {
		$this->RawRequest('setaccount', array($bitcoinaddress, $account));
		return $this->response;
	}



    /**
     * Enable or disable CPU mining.
     * 
     * @param <bool>	$generate		true or false to turn generation on or off.
     * @param <int>		$genproclimit	Generation is limited to $genproclimit processors, -1 is unlimited
     * 
     * @return <type>
     */
	public function SetGenerate($generate = true, $genproclimit = -1) {
		$this->RawRequest('setgenerate', array((bool)$generate, (int)$genproclimit));
		return $this->response;
	}



    /**
     * Set the default transaction fee amount you will pay per transactions, in btc/kb
     * 
     * @param <float> $amount	a real and is rounded to the nearest 0.00000001 btc per kb
     * 
     * @return <type>
     */
	public function SetTxFee($amount) {
		$this->RawRequest('settxfee', array((float)$amount));
		return $this->response;
	}


 
    /**
     * Sign a message with the private key of an address
     * 
     * @param <string> $bitcoinaddress 
     * @param <string> $message 
     * 
     * @return <type>
     */
	public function SignMessage($bitcoinaddress, $message) {
		$this->RawRequest('signmessage', array($bitcoinaddress, $message));
		return $this->response;
	}
	

	
    /**
	 * signrawtransaction <hex string> [{"txid":txid,"vout":n,"scriptPubKey":hex,"redeemScript":hex},...] [<privatekey1>,...] [sighashtype="ALL"]
	 * Sign inputs for raw transaction (serialized, hex-encoded).
	 * Second optional argument (may be null) is an array of previous transaction outputs that
	 * this transaction depends on but may not yet be in the block chain.
	 * Third optional argument (may be null) is an array of base58-encoded private
	 * keys that, if given, will be the only keys used to sign the transaction.
	 * Fourth optional argument is a string that is one of six values; ALL, NONE, SINGLE or
	 * ALL|ANYONECANPAY, NONE|ANYONECANPAY, SINGLE|ANYONECANPAY.
	 * Returns json object with keys:
	 *   hex : raw transaction with signature(s) (hex-encoded string)
	 *   complete : 1 if transaction has a complete set of signature (0 if not)
     * 
     * @param <string/array>	$hex			hex string>
     * @param <string/array>	$previous		[{"txid":txid,"vout":n,"scriptPubKey":hex,"redeemScript":hex},...]
     * @param <string/array>	$privatekeys	[<privatekey1>,...]
     * @param <string>			$sighashtype	[sighashtype="ALL"]
     * 
     * @return <array>
     */
	public function SignRawTransaction($hex, $previous = null, $privatekeys = null, $sighashtype = null) {
		$params	=	array();
		
		if (!empty($previous)) {
			$previous		=	(is_array($previous)) ? json_encode($previous): $previous;
			array_push($params, $previous);
		}
		
		if (!empty($privatekeys)) {
			$privatekeys	=	(is_array($privatekeys)) ? json_encode($privatekeys): $privatekeys;
			array_push($params, $privatekeys);
		}
		
		if (!empty($sighashtype)) { //ALL, NONE, SINGLE, ALL|ANYONECANPAY, NONE|ANYONECANPAY, SINGLE|ANYONECANPAY
			array_push($params, $sighashtype);
		}
	
		$this->RawRequest('signrawtransaction', $params);
		return $this->response;
	}
	  


    /**
     * Stop Bitcoin server.
     * 
     * @return <type>
     */
	public function Stop() {
		$this->RawRequest('stop');
		return $this->response;
	}


    /**
     * Attempts to submit new block to network.
	 * [optional-params-obj] parameter is currently ignored.
	 * See https://en.bitcoin.it/wiki/BIP_0022 for full specification.
     * 
     * @param <string> $data 
     * @param <null> $optional  
     * 
     * @return <type>
     */
	public function SubmitBlock($data, $optional = null) {
		$this->RawRequest('submitblock', array($data));
		return $this->response;
	}
	


    /**
     * Return information about <bitcoinaddress>.
     * 
     * @param <string> $bitcoinaddress 
     * 
     * @return <type>
     */
	public function ValidateAddress($bitcoinaddress) {
		$this->RawRequest('validateaddress', array($bitcoinaddress));
		return $this->response['isvalid'];
	}



    /**
     * Verifies blockchain database.
     * 
     * @param <int> $level  
     * @param <int> $blocks  
     * 
     * @return <type>
     */
	public function VerifyChain($level = 1, $blocks = 1) {
		$this->RawRequest('verifychain', array((int)$level, (int)$blocks));
		return $this->response;
	}



    /**
     * Verify a signed message
     * 
     * @param <string> $bitcoinaddress 
     * @param <string> $signature 
     * @param <string> $message 
     * 
     * @return <type>
     */
	public function VerifyMessage($bitcoinaddress, $signature, $message) {
		$this->RawRequest('verifymessage', array($bitcoinaddress, $signature, $message));
		return $this->response;
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
/*

	Here is the quick list from `bitcoind help`
-------------------------------------------------------------------------------

addmultisigaddress <nrequired> <'["key","key"]'> [account]
addnode <node> <add|remove|onetry>
backupwallet <destination>
createmultisig <nrequired> <'["key","key"]'>
createrawtransaction [{"txid":txid,"vout":n},...] {address:amount,...}
decoderawtransaction <hex string>
decodescript <hex string>
dumpprivkey <bitcoinaddress>
dumpwallet <filename>
encryptwallet <passphrase>
getaccount <bitcoinaddress>
getaccountaddress <account>
getaddednodeinfo <dns> [node]
getaddressesbyaccount <account>
getbalance [account] [minconf=1]
getbestblockhash
getblock <hash> [verbose=true]
getblockcount
getblockhash <index>
getblocktemplate [params]
getconnectioncount
getdifficulty
getgenerate
gethashespersec
getinfo
getmininginfo
getnewaddress [account]
getpeerinfo
getrawchangeaddress
getrawmempool
getrawtransaction <txid> [verbose=0]
getreceivedbyaccount <account> [minconf=1]
getreceivedbyaddress <bitcoinaddress> [minconf=1]
gettransaction <txid>
gettxout <txid> <n> [includemempool=true]
gettxoutsetinfo
getwork [data]
help [command]
importprivkey <bitcoinprivkey> [label] [rescan=true]
importwallet <filename>
keypoolrefill [new-size]
listaccounts [minconf=1]
listaddressgroupings
listlockunspent
listreceivedbyaccount [minconf=1] [includeempty=false]
listreceivedbyaddress [minconf=1] [includeempty=false]
listsinceblock [blockhash] [target-confirmations]
listtransactions [account] [count=10] [from=0]
listunspent [minconf=1] [maxconf=9999999]  ["address",...]
lockunspent unlock? [array-of-Objects]
move <fromaccount> <toaccount> <amount> [minconf=1] [comment]
sendfrom <fromaccount> <tobitcoinaddress> <amount> [minconf=1] [comment] [comment-to]
sendmany <fromaccount> {address:amount,...} [minconf=1] [comment]
sendrawtransaction <hex string>
sendtoaddress <bitcoinaddress> <amount> [comment] [comment-to]
setaccount <bitcoinaddress> <account>
setgenerate <generate> [genproclimit]
settxfee <amount btc/kb>
signmessage <bitcoinaddress> <message>
signrawtransaction <hex string> [{"txid":txid,"vout":n,"scriptPubKey":hex,"redeemScript":hex},...] [<privatekey1>,...] [sighashtype="ALL"]
stop
submitblock <hex data> [optional-params-obj]
validateaddress <bitcoinaddress>
verifychain [check level] [num blocks]
verifymessage <bitcoinaddress> <signature> <message>

*/
