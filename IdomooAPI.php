<?php

class IdomooAPIResponse {

    private $status;
    private $errorDescription;
    private $videoURL;
    private $thumbnail;
    private $landingPageUrl;

    /**
     * IdomooAPIResponse constructor.
     * @param $response_json
     */
    public function __construct($response_json) {
        $response = json_decode($response_json);
        $this->status = $response->status;
        if (isset($response->errorDescription)) {
            $this->errorDescription = $response->errorDescription;
        } elseif (isset($response->statusDescription)) {
            $this->errorDescription = $response->statusDescription;
        }
        foreach ($response->video->output_formats as $output_format) {
            if (substr($output_format->format, 0, 6) == 'VIDEO_') {
                $this->videoURL = $output_format->links[0]->url;
                if (isset($output_format->links[0]->landing_page_url)) {
                    $this->landingPageUrl = $output_format->links[0]->landing_page_url;
                }
            } elseif (substr($output_format->format, 0, 6) == 'IMAGE_') {
                $this->thumbnail = $output_format->links[0]->url;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getErrorDescription() {
        return $this->errorDescription;
    }

    /**
     * @param mixed $errorDescription
     */
    public function setErrorDescription($errorDescription) {
        $this->errorDescription = $errorDescription;
    }

    /**
     * @return mixed
     */
    public function getVideoURL() {
        return $this->videoURL;
    }

    /**
     * @param mixed $videoURL
     */
    public function setVideoURL($videoURL) {
        $this->videoURL = $videoURL;
    }

    /**
     * @return mixed
     */
    public function getThumbnail() {
        return $this->thumbnail;
    }

    /**
     * @param mixed $thumbnail
     */
    public function setThumbnail($thumbnail) {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return mixed
     */
    public function getLandingPageUrl() {
        return $this->landingPageUrl;
    }

    /**
     * @param mixed $landingPageUrl
     */
    public function setLandingPageUrl($landingPageUrl) {
        $this->landingPageUrl = $landingPageUrl;
    }


}

class IdomooAPIRequest {

    const END_POINT_US = "https://us-api.idomoo.com/ext/cg";
    const END_POINT_EU = "https://eu-api.idomoo.com/ext/cg";
    const VIDEO_SD = "VIDEO_MP4_V_X264_640X360_1600_A_AAC_128";
    const VIDEO_HD = "VIDEO_MP4_V_X264_1280X720_800_A_AAC_128";
    const AUTHENTICATION_LEVEL_NORMAL = "AUTHENTICATION_LEVEL_NORMAL";
    const AUTHENTICATION_LEVEL_HIGH = "AUTHENTICATION_LEVEL_HIGH";

    private $accountId;
    private $endpointURL;
    private $storyboardId;
    private $landingPageId;
    private $storageId;
    private $secretKey;
    private $securityLevel = self::AUTHENTICATION_LEVEL_NORMAL;
    private $parameters = array();
    private $quality = self::VIDEO_SD;
    private $debugOutput = [];

    /**
     * IdomooAPIRequest constructor.
     * @param $accountId
     * @param $endpointURL
     */
    public function __construct($endpointURL, $accountId) {
        $this->accountId = $accountId;
        $this->endpointURL = $endpointURL;
    }

    public function send() {
        $request_json = $this->createJson();
        $this->debugOutput[] = "URL: " . $this->getEndpointURL();
        $this->debugOutput[] = "REQUEST: " . $request_json;

        $ch = curl_init($this->getEndpointURL());
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $request_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($request_json)
            )
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response_json = curl_exec($ch);
        curl_close($ch);

        $this->debugOutput[] = "RESPONSE: " . $response_json;

        return new IdomooAPIResponse($response_json);
    }

    private function createJson() {
        $json = [];
        $json['response_format'] = 'json';
        $json['video'] = array(
            "storyboard_id" => $this->getStoryboardId(),
            "account_id" => $this->getAccountId(),
            "data" => $this->getParameters()
        );

        if ($this->getSecurityLevel() == self::AUTHENTICATION_LEVEL_NORMAL) {
            $json['video']['authentication_token'] = $this->getSecretKey(); 
        } else {
            $json['video']['authentication_token'] = $this->getDynamicToken();
        }
        if ($this->getStorageId()) {
            $json['video']['storageID'] = $this->getStorageId();
        }
        if ($this->getLandingPageId()) {
            $json['video']['landing_page_id'] = $this->getLandingPageId();
        }

        $json['video']['output_formats'] = array(
            array(
                "format" => $this->getQuality()
            )
        );

        return json_encode($json,JSON_UNESCAPED_SLASHES);
    }

    private function getDynamicToken() {
        $authentication_string = '';
        foreach ($this->parameters as $parameter) {
            $authentication_string .= $parameter["key"] . $parameter["val"];
        }
        $authentication_string .= $this->getSecretKey();

        return $this->hashRequest($authentication_string);
    }

    public function hashRequest($str) {
        $md5	=	md5($str,true);
        $hash	=	base64_encode($md5)	;
        $hash	=	str_replace('+','.',$hash);
        $hash	=	str_replace('/','_',$hash);
        $hash	=	str_replace('=','-',$hash);
        return	$hash;
    }

    /**
     * @return mixed
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @param mixed $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @return mixed
     */
    public function getEndpointURL() {
        return $this->endpointURL;
    }

    /**
     * @param mixed $endpointURL
     */
    public function setEndpointURL($endpointURL) {
        $this->endpointURL = $endpointURL;
    }

    /**
     * @return mixed
     */
    public function getStoryboardId() {
        return $this->storyboardId;
    }

    /**
     * @param mixed $storyboardId
     */
    public function setStoryboardId($storyboardId) {
        $this->storyboardId = $storyboardId;
    }

    /**
     * @return mixed
     */
    public function getLandingPageId() {
        return $this->landingPageId;
    }

    /**
     * @param mixed $landingPageId
     */
    public function setLandingPageId($landingPageId) {
        $this->landingPageId = $landingPageId;
    }

    /**
     * @return mixed
     */
    public function getStorageId() {
        return $this->storageId;
    }

    /**
     * @param mixed $storageId
     */
    public function setStorageId($storageId) {
        $this->storageId = $storageId;
    }

    /**
     * @return mixed
     */
    public function getSecretKey() {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     */
    public function setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
    }

    /**
     * @return string
     */
    public function getSecurityLevel() {
        return $this->securityLevel;
    }

    /**
     * @param string $securityLevel
     */
    public function setSecurityLevel($securityLevel) {
        $this->securityLevel = $securityLevel;
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    public function setParameter($key, $value) {
        $this->parameters[] = array(
            "key" => $key,
            "val" => $value
        );
    }

    public function clearData() {
        $this->parameters = array();
    }

    /**
     * @return string
     */
    public function getQuality() {
        return $this->quality;
    }

    /**
     * @param string $quality
     */
    public function setQuality($quality) {
        $this->quality = $quality;
    }

    /**
     * @return boolean
     */
    public function getDebugOutput() {
        return $this->debugOutput;
    }

    /**
     * @param boolean $debugOutput
     */
    public function setDebugOutput($debugOutput) {
        $this->debugOutput = $debugOutput;
    }




}