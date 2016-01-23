<?php

/**
 * Class CurlRequest
 *
 * @author Samed Düzçay <samedduzcay@gmail.com>
 *
 * @description Makes cURL requests.
 *
 */

class CurlRequest
{
    const USERAGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0';

    private $url;
    private $postData;
    private $extra;
    private $cookieFile;

    /**
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return array $postData
     */
    public function getPostData()
    {
        return $this->postData;
    }

    /**
     * @param array $postData
     */
    public function setPostData($postData)
    {
        $this->postData = $postData;
    }

    /**
     * @return array $extra Extra CURLOPT_ constants
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param array $extra Extra CURLOPT_ constants
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * @return string $cookieFile
     */
    public function getCookieFile()
    {
        return $this->cookieFile;
    }

    /**
     * @param string $cookieFile
     */
    public function setCookieFile($cookieFile)
    {
        $this->cookieFile = $cookieFile;
    }

    /**
     * Makes cURL requests
     * @param string $url Request will be made to this URL
     * @param string|array $postData If set, this data will be posted to the URL
     * @param array $extra Extra cURL options array
     * @return mixed HTML response of request (entity decoded)
     */
    public function get($url = null, $postData = null, $extra = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, (is_null($url) ? $this->url : $url));
        curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Prevent endless redirects
        if (!empty($this->cookieFile)) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }
        if (!is_null($postData)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ((is_array($postData) || is_object($postData)) ? http_build_query($postData, '', '&') : $postData));
        } elseif (!empty($this->postData)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ((is_array($this->postData) || is_object($this->postData)) ? http_build_query($this->postData, '', '&') : $this->postData));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!empty($extra))
            curl_setopt_array($ch, $extra);
        elseif (!empty($this->extra))
            curl_setopt_array($ch, $this->extra);
        $run = curl_exec($ch);
        if ($run === false)
            return false;
        curl_close($ch);
        return $this->fixQuotes($run);
    }

    /**
     * Fixes curly quotes
     * @param string $data HTML data
     * @return mixed
     */
    public function fixQuotes($data)
    {
        // Change curly quotes to normal - no hipstery allowed
        $chr_map = array(
            // Windows codepage 1252
            "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
            "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
            "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
            "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
            "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
            "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
            "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
            "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

            // Regular Unicode     // U+0022 quotation mark (")
            // U+0027 apostrophe     (')
            "\xC2\xAB" => '"', // U+00AB left-pointing double angle quotation mark
            "\xC2\xBB" => '"', // U+00BB right-pointing double angle quotation mark
            "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
            "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
            "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
            "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
            "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
            "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
            "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
            "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
            "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
            "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
        );
        $chr = array_keys($chr_map);
        $rpl = array_values($chr_map);
        $data = str_replace($chr, $rpl, html_entity_decode($data, ENT_QUOTES, "UTF-8"));
        return $data;
    }

    /**
     * CurlRequest constructor.
     *
     * @param string $url URL to be requested
     *
     * @param array|null $args
     * 'cookie' or 'cookiefile' => cookie file path,
     * 'post' or 'postdata' => post data, if you want a post request
     * 'extra' => an array of curlopt_ options, if you want extra options or to override default options
     */
    public function __construct($url = null, $args = null)
    {
        $this->url = $url;
        if (!is_null($args)) {
            foreach ($args as $k => $arg) {
                if (preg_match('#^cookie(file)?$#si', $k)) {
                    $this->cookieFile = $arg;
                } elseif (preg_match('#^post(data)?$#si', $k)) {
                    $this->postData = $arg;
                } elseif (preg_match('#^extra$#si', $k)) {
                    $this->extra = $arg;
                }
            }
        }
    }

    /**
     * @return string HTML data of requested URL
     */
    public function __toString()
    {
        return $this->get();
    }

}