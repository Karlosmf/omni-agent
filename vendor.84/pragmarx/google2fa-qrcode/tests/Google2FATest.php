<?php

namespace PragmaRX\Google2FAQRCode\Tests;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use PHPUnit\Framework\TestCase;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;
use PragmaRX\Google2FAQRCode\QRCode\Chillerlan;
use Zxing\QrReader;

class Google2FATest extends TestCase
{
    const EMAIL = 'acr+pragmarx@antoniocarlosribeiro.com';

    const OTP_URL = 'otpauth://totp/PragmaRX:acr+pragmarx@antoniocarlosribeiro.com?secret=ADUMJO5634NPDEKW&issuer=PragmaRX&algorithm=SHA1&digits=6&period=30';

    protected function setUp(): void
    {
        $this->google2fa = new Google2FA;
    }

    public function readQRCode($data)
    {
        [, $data] = explode(';', $data);

        [, $data] = explode(',', $data);

        return rawurldecode(
            (new QrReader(
                base64_decode($data),
                QrReader::SOURCE_TYPE_BLOB
            ))->text()
        );
    }

    public function test_qrcode_service_missing()
    {
        $this->expectException(MissingQrCodeServiceException::class);

        $this->google2fa->setQrcodeService(null);

        $this->getQrCode();
    }

    public function test_qrcode_inline_bacon()
    {
        if (! (new Bacon)->imagickIsAvailable()) {
            $this->assertTrue(true);

            return;
        }

        $this->google2fa->setQrcodeService(new Bacon);

        $this->assertEquals(
            static::OTP_URL,
            $this->readQRCode($this->getQRCode())
        );

        $google2fa = new Google2FA(null, new Bacon(new SvgImageBackEnd));

        $this->assertEquals(
            static::OTP_URL,
            $this->readQRCode($this->getQRCode())
        );
    }

    public function test_qrcode_inline_chillerlan()
    {
        $this->google2fa->setQrcodeService(new Chillerlan);

        $this->assertStringStartsWith(
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMj',
            $this->getQRCode()
        );
    }

    public function getQrCode()
    {
        return $this->google2fa->getQRCodeInline(
            'PragmaRX',
            static::EMAIL,
            Constants::SECRET
        );
    }
}
