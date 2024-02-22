<?php

namespace Src\App;

header("content-type: text/plain;charset=utf8");

require "vendor/autoload.php";

use DOMDocument;

class SintegraSpider {
    
    private const BASE_SINTEGRA_URL           = "http://www.sintegra.fazenda.pr.gov.br";
    private const DATA_CONSULT_URL            = "http://www.sintegra.fazenda.pr.gov.br/sintegra/sintegra1/consultar";
    private const ABSOLUT_SINTEGRA_URL        = "http://www.sintegra.fazenda.pr.gov.br/sintegra/";
    private const CAPTCHA_REMOTE_URL          = "http://www.sintegra.fazenda.pr.gov.br/sintegra/captcha";
    private const LOCAL_CAPTCHA_IMAGE_PATH    = "./vendor/storage/resources/images/captcha.jpeg";

    private const COOKIE_BYTES_AMOUNT = 16;

    private $resourceList = [];
    private $cakeCookie  = "";

    public function __construct(
        array $resourceList = []
    ) {
        $resourceList &&
            $this->resourceList = $resourceList;
        $this->createCookie();
    }

    public function findByCNPJ(string $cnpj, string $verifyCode, string $currentCookie = null)
    {
        try {
            $curlHandler = curl_init();

            $options = [
                CURLOPT_URL             => self::ABSOLUT_SINTEGRA_URL,
                CURLOPT_RETURNTRANSFER  => true,
                //CURLOPT_FOLLOWLOCATION  => 1,
                CURLOPT_HEADER          => true,
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => [
                    "_method"                           => "POST",
                    "data[Sintegra1][CodImage]"         => $verifyCode,
                    "data[Sintegra1][Cnpj]"	            => $cnpj,
                    "empresa"                           => "Consultar+Empresa",
                    "data[Sintegra1][Cadicms]"          => "",
                    "data[Sintegra1][CadicmsProdutor]"  => "",
                    "data[Sintegra1][CnpjCpfProdutor]"  => ""
                ],
                CURLOPT_HTTPHEADER      => [
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
                    "Connection: keep-alive",
                    "Content-Type: application/x-www-form-urlencoded",
                    "Upgrade-Insecure-Requests: 1",
                    "Cookie: CAKEPHP={$currentCookie}"
                ],
                CURLOPT_VERBOSE         => true,
                CURLOPT_USERAGENT       => "SilvaSilas99\Src\App\SintegraSpider: 0.0.1",
            ];

            curl_setopt_array($curlHandler, $options);

            $responseFromCurl = curl_exec($curlHandler);
            
            $domHandler = new DOMDocument("1.0", "UTF-8");
            @$domHandler->loadHTML($responseFromCurl);

            var_dump($responseFromCurl);

            curl_close($curlHandler);

            return 0;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function generateAndDownloadCaptchaImage ()
    {
        try {
            $this->getNewCakeCookieFromMainPage();

            $curlHandler = curl_init();

            $options = [
                CURLOPT_URL             => self::CAPTCHA_REMOTE_URL,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_VERBOSE         => 0,
                CURLINFO_HEADER_OUT     => 1,
                CURLOPT_USERAGENT       => "SilvaSilas99\Src\App\SintegraSpider: 0.0.1",
                CURLOPT_HTTPHEADER      => [
                    "Cookie: {$this->cakeCookie}",  // Here the value of $cakeCookie is "CAKEPHP=16byteshexdec"
                    "Content-Type: image/jpeg"
                ]
            ];
            curl_setopt_array($curlHandler, $options);

            $captchaImageRawData = curl_exec($curlHandler);     // First get image and save it to supress fails

            file_exists(self::LOCAL_CAPTCHA_IMAGE_PATH) &&
                unlink(self::LOCAL_CAPTCHA_IMAGE_PATH);

            $fp = fopen(self::LOCAL_CAPTCHA_IMAGE_PATH, "x");   
            fwrite($fp, $captchaImageRawData);
            fclose($fp);

            echo "SintegraSpider.generateAndDownloadCaptchaImage: Image downloaded with sucess.\n\n";

            $captchaHeadersRawData = curl_getinfo($curlHandler, CURLINFO_HEADER_OUT);   // After try to get the out headers data

            preg_match_all("/Cookie:\s*([^=]+)=(\S+)\s*/", $captchaHeadersRawData, $cookies);
            if (
                $cookies
            &&  $cookies[0]
            &&  $cookies[0][0]
            ) {
                echo "SintegraSpider.generateAndDownloadCaptchaImage: Are going to renew CAKEPHP COOKIE to value {$cookies[0][0]}.\n\n";
                
                $this->setCakeCookie($cookies[0][0]);
                
                return 0;
            }

            curl_close($curlHandler);

            echo "SintegraSpider.generateAndDownloadCaptchaImage.warning: The cookies weren't renewed.\n\n";
            return 0;
        } catch (\Throwable $th) {
            echo "SintegraSpider.generateAndDownloadCaptchaImage: Error {$th->getMessage()}\n\n";
            throw $th;
        }
    }

    public function getCakeCookie ()
    {
        return $this->cakeCookie;
    }

    private function setCakeCookie (string $value)
    {
        $this->cakeCookie = $value;
    }

    private function getNewCakeCookieFromMainPage ()
    {
        $curlHandler = curl_init();

        $options = [
            CURLOPT_URL             => self::ABSOLUT_SINTEGRA_URL,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_HEADER          => 1,
            CURLOPT_VERBOSE         => 0,
            CURLOPT_USERAGENT       => "SilvaSilas99\Src\App\SintegraSpider: 0.0.1",
            CURLOPT_HTTPHEADER      => [
                "Content-Type: text/html; charset=utf-8"
            ]
        ];
        curl_setopt_array($curlHandler, $options);

        $mainSiteRawData = curl_exec($curlHandler);

        preg_match_all("/Set-Cookie:\s?(.*?);/i", $mainSiteRawData, $cookies); // (/Cookie:\s*([^=]+)=(\S+)\s*/)
        if (
            $cookies
        &&  $cookies[1]
        &&  $cookies[1][0]
        ) {
            echo "SintegraSpider.getNewCakeCookieFromMainPage: Are going to renew CAKEPHP COOKIE to value {$cookies[1][0]}.\n\n";
            
            $this->setCakeCookie($cookies[1][0]);
            
            return 0;
        }

        return 1;
    }

    private function createCookie ()
    {        
        $cakeCookieValue = bin2hex(openssl_random_pseudo_bytes(self::COOKIE_BYTES_AMOUNT)); 
        $this->cakeCookie = "CAKEPHP={$cakeCookieValue}";

        echo "SintegraSpider.createCookie.debug: COOKIE {$this->cakeCookie} created.\n\n";
        return 0;
    }

}