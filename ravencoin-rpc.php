<?php
/*

A basic PHP class for making calls to Ravencoin's Network.
https://github.com/HazeDevelopment/RavencoinRPC-PHP
*/
// How to initialize RavenCoin connection/object:
//$ravencoin = new Ravencoin('username','password');

// You can specify a host and port (Optional).
//$ravencoin = new Ravencoin('username','password','host','port');
// Default connection settings:
//	host = localhost
//	port = 8766
//	proto = http

// For an SSL connection you can set a CA certificate or leave blank
// This will set the protocol to HTTPS and some CURL flags
//$ravencoin->setSSL('/full/path/to/certificate.cert');

// Make calls to ravencoind as methods for your object. Response returns an array.
// Examples:
//$ravencoin->getinfo();
//$ravencoin->getrawtransaction('2b849538e4d43a20daf8b19a3bac762c7edad16386e3cd7205a18035aa6646b0',1);
//$ravencoin->getblock('000000000001f38aa42b905231c7a8a12e4508de126b683f8165f2589e844070');
// The full response is stored in $this->response, the raw JSON is stored in $this->raw_response
// If for any reason a call fails, it will return FALSE and store its error message in $this->error :
//echo $ravencoin->error;

// The HTTP status code is stored inside $this->status and is either an HTTP status code or will be 0 if cURL was not to connect.
// Example:
//echo $ravencoin->status;
class Ravencoin {
    // Config options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;
    // Info and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;
    private $id = 0;
    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    function __construct($username, $password, $host = 'localhost', $port = 8766, $url = null) {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;
        // Set defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
    }
    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null) {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }
    function __call($method, $params) {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;
        // If no parameters are passed, returns an empty array
        $params = array_values($params);
        // The ID should be unique for each call
        $this->id++;
        // Build the request, dont worry if params has an empty array
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));
        // Build cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $request
        );
        // Error Prevention
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }
        if ($this->proto == 'https') {
            // If CA Certificate was specified, change CURL so it looks for it
            if ($this->CACertificate != null) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            }
            else {
                // If not we need to assume SSL cannot be verified so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
            }
        }
        curl_setopt_array($curl, $options);
        // Execute request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, TRUE);
        // If status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);
        curl_close($curl);
        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }
        if ($this->response['error']) {
            // If ravencoind returned an error, store it inside $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
            // If error message wasnt right, we make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        if ($this->error) {
            return FALSE;
        }
        return $this->response['result'];
    }
}

