<?php
/**
 * Curl based HTTP Client
 *
 * Simple but effective OOP wrapper around Curl php lib.
 * Contains common methods needed
 * for getting data from url, setting referrer, credentials,
 * sending post data, managing cookies, etc.
 *
 * @package UTPC
 * @subpackage framework
 * @category lib
 * @version 1.2
 * @copyright &copy; 2008 Dinke.net
 * @author Dragan Dinic <dragan@dinke.net>
 * @author Boris Momcilovic <boris.momcilovic@gmail.com>
 * @version 1.3 - changed error reporting, now calls trigger_error ($this->get_error_msg(), E_USER_WARNING) instead of echo error
 * @version 1.4 - removed error reporting, error can be collected via $this->get_error_msg() on bool false response
 *
 * @example
 * $curl = &new Curl ();
 * $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
 * $curl->set_user_agent($useragent);
 * $curl->store_cookies("/tmp/cookies.txt");
 * $post_data = array('login' => 'pera', 'password' => 'joe');
 * $html_data = $curl->send_post_data(http://www.foo.com/login.php, $post_data);
 */

class Curl
{
/**
 * Curl handler
 *
 * @access private
 * @var resource
 */
	private $ch;

/**
 * Set debug to true in order to get usefull output
 *
 * @access private
 * @var string
 */
	private $debug = false;

/**
 * Constructor
 *
 * @param	boolean	$debug	[= false]
 * @access	public
 */
	public function __construct ($debug = false)
	{
		$this->debug = (bool) $debug;
		$this->init ();
	}

/**
 * Init Curl session
 * @access public
 */
	public function init()
	{
		// initialize curl handle
		$this->ch = curl_init();

		//set error in case http return code bigger than 300
		curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

		// allow redirects
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

		// use gzip if possible
		curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');

		// do not veryfy SSL
		// this is important for windows, as well for being able to access pages with non valid cert
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	}

/**
 * Set username/pass for basic http auth
 *
 * @param string user
 * @param string pass
 * @access public
 */
	public function set_credentials($username,$password)
	{
		curl_setopt ($this->ch, CURLOPT_USERPWD, "{$username}:{$password}");
	}

/**
 * Set referrer
 *
 * @param string referrer url
 * @access public
 */
	public function set_referrer($referrer_url)
	{
		curl_setopt($this->ch, CURLOPT_REFERER, $referrer_url);
	}

/**
 * Set client's useragent
 *
 * @param string user agent
 * @access public
 */
	public function set_user_agent($useragent)
	{
		curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
	}

/**
 * Set to receive output headers in all output functions
 *
 * @param boolean true to include all response headers with output, false otherwise
 * @access public
 */
	public function include_response_headers($value)
	{
		curl_setopt($this->ch, CURLOPT_HEADER, $value);
	}

/**
 * Set http header
 *
 * @param	array	$headers
 * @access	public
 */
	public function set_http_header($headers)
	{
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	}

/**
 * Set proxy to use for each curl request
 *
 * @param string proxy
 * @access public
 */
	public function set_proxy($proxy)
	{
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
	}

/**
 * Send post data to target URL
 * return data returned from url or false if error occured
 *
 * @param string url
 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
 * @param string ip address to bind (default null)
 * @param int timeout in sec for complete curl operation (default 15)
 * @return string data
 * @access public
 */
	public function send_post_data ($url, $postdata, $ip=null, $timeout=15)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		//generate post string
		$post_array = array();
		if(is_array($postdata))
		{
			$post_string = http_build_query($postdata);

			if($this->debug)
			{
				echo "Url: $url\nPost String: $post_string\n";
			}
		}
		else
		{
			$post_string = $postdata;
		}

		// set post string
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		//and finally send curl request
		$result = curl_exec($this->ch);

		return curl_errno ($this->ch) ? false : $result;
	}

/**
 * fetch data from target URL
 * return data returned from url or false if error occured
 * - raise user warning on CURL error only in debug mode
 *
 *
 * @param string url
 * @param string ip address to bind (default null)
 * @param int timeout in sec for complete curl operation (default 5)
 * @return string data
 * @access public
 */
	public function fetch_url($url, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET,true);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if ($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);

		return curl_errno($this->ch) ? false : $result;
	}

/**
 * Fetch data from target URL
 * and store it directly to file
 * @param string url
 * @param resource value stream resource(ie. fopen)
 * @param string ip address to bind (default null)
 * @param int timeout in sec for complete curl operation (default 5)
 * @return boolean true on success false othervise
 * @access public
 */
	public function fetch_into_file($url, $fp, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);

		// store data into file rather than displaying it
		curl_setopt($this->ch, CURLOPT_FILE, $fp);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		curl_exec($this->ch);

		return !curl_errno ($this->ch);
	}

/**
 * Send multipart post data to the target URL
 *
 * return data returned from url or false if error occured
 * (contribution by vule nikolic, vule@dinke.net)
 * @param string url
 * @param array assoc post data array ie. $foo['post_var_name'] = $value
 * @param array assoc $file_field_array, contains file_field name = value - path pairs
 * @param string ip address to bind (default null)
 * @param int timeout in sec for complete curl operation (default 30 sec)
 * @return string data
 * @access public
 */
	public function send_multipart_post_data($url, $postdata, $file_field_array=array(), $ip=null, $timeout=30)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL, $url);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		// disable Expect header, hack to make it working
		$headers = array("Expect: ");
		$this->set_http_header ($headers);

		// initialize result post array
		$result_post = array();

		//generate post string
		$post_array = array();
		$post_string_array = array();

		if (!is_array($postdata))
		{
			return false;
		}

		foreach($postdata as $key=>$value)
		{
			$post_array[$key] = $value;
			$post_string_array[] = urlencode($key)."=".urlencode($value);
		}

		$post_string = implode("&",$post_string_array);

		if ($this->debug)
		{
			echo "Post String: $post_string\n";
		}

		// set post string
		//curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);

		// set multipart form data - file array field-value pairs
		if(!empty($file_field_array))
		{
			foreach($file_field_array as $var_name => $var_value)
			{
				if(strpos(PHP_OS, "WIN") !== false) $var_value = str_replace("/", "\\", $var_value); // win hack
				$file_field_array[$var_name] = "@".$var_value;
			}
		}

		// set post data
		$result_post = array_merge($post_array, $file_field_array);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $result_post);

		//and finally send curl request
		$result = curl_exec($this->ch);

		return curl_errno ($this->ch) ? false : $result;
	}

/**
 * Set file location where cookie data will be stored and send on each new request
 *
 * @param string absolute path to cookie file (must be in writable dir)
 * @access public
 */
	public function store_cookies($cookie_file)
	{
		// use cookies on each request (cookies stored in $cookie_file)
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
	}

/**
 * Set custom cookie
 *
 * @param string cookie
 * @access public
 */
	public function set_cookie($cookie)
	{
		curl_setopt ($this->ch, CURLOPT_COOKIE, $cookie);
	}

/**
 * Get last URL info
 * usefull when original url was redirected to other location
 *
 * @access public
 * @return string url
 */
	public function get_effective_url()
	{
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

/**
 * Get http response code
 *
 * @access public
 * @return int
 */
	public function get_http_response_code()
	{
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

/**
 * Return last error message and error number
 *
 * @return string error msg
 * @access public
 */
	public function get_error_msg()
	{
		return "Curl error #" .curl_errno($this->ch) .": " .curl_error($this->ch);
	}

/**
 * Return true if we have an error
 *
 * @return bool
 * @access public
 */
	public function has_error()
	{
		return (curl_errno($this->ch) != 0) ? true : false;
	}

/**
 * Close curl session and free resource
 *
 * Usually no need to call this function directly
 * in case you do you have to call init() to recreate curl
 * @access public
 */
	public function close()
	{
		//close curl session and free up resources
		curl_close($this->ch);
	}


/**
 * Download remote file to specific $filepath on local file system
 * (basically a wrapper method for fetch_into_file, thus so meny params)
 *
 * @author	Zoran Mihailovic <perfectlounge@gmail.com>
 * @access	public
 * @param	string	$url						(url to fetch contetn from)
 * @param	string	$filepath				(local filesystem path, where the file should be downloaded to)
 * @param	string	$mode		[='w+']		(file fopen mode/flag)
 * @param	string	$ip			[=null]		(address to bind)
 * @param	int		$timeout		[=5]			(in sec for complete curl operation)
 * @return	bool
 */
	public function download ($url, $filepath, $mode = 'w+', $ip = null, $timeout = 5)
	{
		if (!is_scalar ($url) || empty ($url) || !is_scalar ($filepath) || empty ($filepath))		{ return false; }

		$fp = @fopen ($filepath, $mode);

		if (!is_resource ($fp))						{ return false; }

		$this->fetch_into_file ($url, $fp, $ip, $timeout);

		fclose ($fp);

		return true;
	}

} // end of class

