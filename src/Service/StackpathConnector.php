<?php

namespace BiffBangPow\Stackpath\Service;

use GuzzleHttp\Client;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\SiteConfig\SiteConfig;

class StackpathConnector
{
    use Configurable;

    /**
     * Maximum time a bearer token stays valid (in seconds)
     */
    const DEFAULT_TOKEN_EXPIRY = 3600;
    /**
     * How much time to leave ourselves as a buffer when checking the token expiry (in seconds)
     */
    const EXPIRY_BUFFER = 600;
    /**
     * @config
     * @var string $api_endpoint
     */
    private static $api_endpoint = 'https://gateway.stackpath.com';
    /**
     * @config
     * @var $api_id
     */
    private static $api_id;
    /**
     * @config
     * @var $api_secret
     */
    private static $api_secret;
    /**
     * @config
     * @var $stack_id
     */
    private static $stack_id;
    /**
     * @var $bearertoken
     * Local storage of bearer token
     */
    private $bearertoken;

    /**
     * StackpathConnector constructor.
     * Checks to make sure we have everything we need to be able to proceed
     * @throws \Exception
     */
    public function __construct()
    {
        if (($this->config()->get('api_id') == '') || ($this->config()->get('api_secret') == '') || ($this->config()->get('stack_id') == '')) {
            throw new \Exception('Please configure the required Stackpath API information');
        }
    }


    public function purgeCache()
    {
        $token = $this->getBearerToken();
        $url = Controller::join_links([
            $this->getAPIEndPoint(),
            'cdn',
            'v1',
            'stacks',
            $this->config()->get('stack_id'),
            'purge'
        ]);

        $content = [
            'items' => [
                'recursive' => true,
                'url' => '/'
            ]
        ];

        $requestData = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'json' => $content
        ];
    }

    private function getBearerToken()
    {
        if ($this->bearertoken != '') {
            return $this->bearertoken;
        }

        $config = SiteConfig::current_site_config();
        $storedToken = $config->StackpathBearer;
        $tokenExpiry = $config->StackpathTokenExpires;

        if (($storedToken != '') && (!$this->checkTokenExpired($tokenExpiry))) {
            $this->bearertoken = $storedToken;
            return $storedToken;
        }

        $apiToken = $this->getTokenFromAPI();
        if ($apiToken !== false) {
            $this->bearertoken = $apiToken;
            return $apiToken;
        }

        return $this->getBearerToken();
    }

    /**
     * @param $tokenTime
     * @return bool
     */
    private function checkTokenExpired($tokenTime)
    {
        return ($tokenTime > time());
    }

    /**
     * Gets an access token from the API
     * @return string | false
     * @throws \GuzzleHttp\Exception\GuzzleException|\SilverStripe\ORM\ValidationException
     * @todo implement logging of everything
     */
    private function getTokenFromAPI()
    {
        $url = Controller::join_links([
            $this->getAPIEndPoint(),
            'identity',
            'v1',
            'oauth2',
            'token'
        ]);
        $content = [
            'client_id' => $this->config()->get('api_id'),
            'client_secret' => $this->config()->get('api_secret'),
            'grant_type' => 'client_credentials'
        ];

        $requestData = [
            'json' => $content
        ];

        $client = new Client();
        $response = $client->post($url, $requestData);

        if ($response->getStatusCode() >= 400) {
            die('Error getting token');
        }

        $spResponse = json_decode($response->getBody(), true);
        if (!$spResponse) {
            die('Error getting token');
        }

        if ((!isset($spResponse['access_token'])) || ($spResponse['access_token'] == "")) {
            return false;
        }

        if (isset($spResponse['expires_in']) && ((int)$spResponse['expires_in'] > 0)) {
            $tokenExpiry = (int)$spResponse['expires_in'] - self::EXPIRY_BUFFER;
        } else {
            $tokenExpiry = $this->getDefaultTokenExpiry();
        }

        $config = SiteConfig::current_site_config();
        $config->update([
            'StackpathBearer' => $spResponse['access_token'],
            'StackpathTokenExpires' => $tokenExpiry
        ]);
        $config->write();

        return $spResponse['access_token'];
    }

    /**
     * Returns the configured API endpoint
     * @return string
     */
    private function getAPIEndPoint()
    {
        return rtrim($this->config()->get('api_endpoint'), '/',);
    }

    /**
     * Returns the time after which the token becomes invalid
     * @return int
     */
    private function getDefaultTokenExpiry()
    {
        return time() + (self::DEFAULT_TOKEN_EXPIRY - self::EXPIRY_BUFFER);
    }

    /**
     * Clears out the token from the class and config
     * @throws \SilverStripe\ORM\ValidationException
     */
    private function purgeBearerToken()
    {
        $this->bearertoken = '';
        $config = SiteConfig::current_site_config();
        $config->update([
            'StackpathBearer' => '',
            'StackpathTokenExpires' => 0
        ]);
        $config->write();
    }

}
