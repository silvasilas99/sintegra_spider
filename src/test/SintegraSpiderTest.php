<?php

namespace Test;

require "vendor/autoload.php";

use Src\App\SintegraSpider;

class SintegraSpiderTest {
    private $sintegraSpiderInstance;

    public function __construct() {
        $this->sintegraSpiderInstance = new SintegraSpider();
    }

    public function testGenerateAndDownloadCaptchaImage() 
    { 
        try {
            $this->sintegraSpiderInstance->generateAndDownloadCaptchaImage();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
    public function testFindByCNPJ() 
    { 
        try {
            $validCookie = readline("Insert a valid cookie: ");
            $verifyCode = readline("Insert the captcha code (of the same session of your cookie): ");
            $this->sintegraSpiderInstance->findByCNPJ("00.063.744/0001-55", $verifyCode, $validCookie);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

}

$sintegraSpiderTest = new SintegraSpiderTest();

$sintegraSpiderTest->testGenerateAndDownloadCaptchaImage();
$sintegraSpiderTest->testFindByCNPJ();