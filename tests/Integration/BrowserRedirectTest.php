<?php
namespace Aqayepardakht\PhpSdk\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class BrowserRedirectTest extends TestCase
{
    private function runRedirect(string $accept,string $tracking): string
    {
        if (!function_exists('proc_open')) { $this->markTestSkipped('proc_open is required for exit isolation.'); }
        $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Fixtures/browser_redirect.php',$accept,$tracking],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if (!is_resource($process)) { self::fail('Cannot start isolated PHP process.'); }
        fclose($pipes[0]); $stdout=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); $status=proc_close($process);
        self::assertSame(0,$status,$stderr); self::assertSame('',$stderr); self::assertStringNotContainsString('AFTER_START',$stdout);
        return $stdout;
    }
    public function testJsonStartReturnsUrlAndTerminatesTheChildProcess(): void
    {
        self::assertSame(['url'=>'https://api.aqayepardakht.ir/startpay/T-1'],json_decode($this->runRedirect('application/json','T-1'),true,512,JSON_THROW_ON_ERROR));
    }
    public function testHtmlStartEncodesTrackingAndTerminatesTheChildProcess(): void
    {
        $html=$this->runRedirect('text/html',"T'</script>");
        self::assertSame(1,preg_match('~^<script>window\.location\.href=(.+);</script>$~',$html,$matches));
        self::assertSame('https://api.aqayepardakht.ir/startpay/T%27%3C%2Fscript%3E',json_decode($matches[1],true,512,JSON_THROW_ON_ERROR));
        self::assertSame(1,substr_count($html,'</script>'));
    }
}
