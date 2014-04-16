<?php

namespace OroPro\Bundle\SecurityBundle\Licence;

use Symfony\Component\HttpFoundation\Response;

class Sender
{
    /**
     * @var string
     */
    protected $url = 'https://github.com/';

    /**
     * @var int
     */
    protected $timeout = 15;

    /**
     * @param $type
     * @param array $data
     * @return null|Response
     */
    public function sendGet($type, array $data = array())
    {
        $urlSeparator = strpos($this->url, '?') === false ? '?' : '&';
        $url = $this->url . $urlSeparator . $this->getUrlString($type, $data);

        $connection = $this->initConnection($url);

        curl_setopt($connection, CURLOPT_HTTPGET, true);

        return $this->getResponse($connection);
    }

    /**
     * @param string $type
     * @param array $data
     * @return null|Response
     */
    public function sendPost($type, array $data = array())
    {
        $connection = $this->initConnection();

        curl_setopt($connection, CURLOPT_POST, true);
        curl_setopt($connection, CURLOPT_POSTFIELDS, $this->getUrlString($type, $data));

        return $this->getResponse($connection);
    }

    /**
     * @param resource $connection
     * @return null|Response
     */
    protected function getResponse($connection)
    {
        try {
            $response = curl_exec($connection);
            if ($response) {
                return $this->parseResponse($response);
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return resource
     */

    /**
     * @param string|null $url
     * @param int|null $timeout
     * @return resource
     */
    protected function initConnection($url = null, $timeout = null)
    {
        if ($url === null) {
            $url = $this->url;
        }
        if ($timeout === null) {
            $timeout = $this->timeout;
        }

        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($connection, CURLOPT_HEADER, true);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);

        return $connection;
    }

    /**
     * @param string $type
     * @param array $data
     * @return string
     */
    protected function getUrlString($type, array $data = array())
    {
        return sprintf('type=%s&data=%s', urlencode($type), urlencode(json_encode($data)));
    }

    /**
     * @param string $response
     * @return Response
     */
    protected function parseResponse($response)
    {
        $content = '';
        $status = 200;
        $headers = array();

        $headerSeparator = "\r\n\r\n";
        if (strpos($response, $headerSeparator) !== false) {
            list($headersString, $content) = explode($headerSeparator, $response, 2);
            list($status, $headers) = $this->parseHeaders($headersString);
        }

        return new Response($content, $status, $headers);
    }

    /**
     * @param string $headersString
     * @return array
     */
    protected function parseHeaders($headersString)
    {
        $headers = array();
        $status = 200;

        if ($headersString) {
            $statusFound = false;
            foreach (explode("\r\n", $headersString) as $header) {
                // extract status code
                if (!$statusFound) {
                    preg_match('~HTTP/\d+\.\d+ (\d+)~', $header, $matches);
                    if (!empty($matches[1])) {
                        $status = (int)$matches[1];
                        $statusFound = true;
                        continue;
                    }
                }

                // parse
                if (strpos($header, ':') !== false) {
                    list($name, $value) = explode(':', $header);
                    $headers[$name] = trim($value);
                } else {
                    $headers[] = $header;
                }
            }
        }

        return array($status, $headers);
    }
}
