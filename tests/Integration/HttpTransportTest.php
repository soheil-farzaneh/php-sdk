<?php
namespace Aqayepardakht\PhpSdk\Tests\Integration;

use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Local-loopback transport checks; never contacts an actual payment provider. */
final class HttpTransportTest extends TestCase
{
    private $server=null;
    private array $pipes=[];
    private string $baseUrl;

    protected function setUp(): void
    {
        if (!extension_loaded('curl') || !function_exists('proc_open')) { $this->markTestSkipped('cURL and proc_open are required.'); }
        $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
        if ($socket===false) { self::fail('Cannot reserve loopback port: '.$error); }
        $address=stream_socket_get_name($socket,false); fclose($socket);
        $this->baseUrl='http://'.$address;
        $this->server=proc_open([PHP_BINARY,'-S',$address,dirname(__DIR__).'/Fixtures/http_router.php'],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$this->pipes);
        if (!is_resource($this->server)) { self::fail('Cannot start local HTTP fixture.'); }
        fclose($this->pipes[0]); unset($this->pipes[0]);
        $deadline=microtime(true)+5;
        do {
            $probe=@stream_socket_client('tcp://'.$address,$errno,$error,0.1);
            if (is_resource($probe)) { fclose($probe); return; }
            usleep(20000);
        } while (microtime(true)<$deadline);
        self::fail('Local HTTP fixture did not become ready.');
    }

    protected function tearDown(): void
    {
        if (is_resource($this->server)) { proc_terminate($this->server); }
        foreach($this->pipes as $pipe) { if (is_resource($pipe)) fclose($pipe); }
        if (is_resource($this->server)) { proc_close($this->server); }
    }

    public function testCurlSendsFormEncodedPostAndDecodesJsonObjects(): void
    {
        $response=(new CurlHttpClient())->post($this->baseUrl.'/ok',['pin'=>'test-pin','amount'=>10000,'sms'=>false,'cards'=>['6037991234567890']]);
        self::assertSame('POST',$response->method);
        self::assertSame('application/x-www-form-urlencoded',$response->content_type);
        self::assertEquals((object) ['pin'=>'test-pin','amount'=>'10000','sms'=>'0','cards'=>['6037991234567890']],$response->parameters);
    }

    #[DataProvider('badResponses')]
    public function testInvalidResponsesAndRedirectsAreRejected(string $path): void
    {
        $this->expectException(TransportException::class);
        (new CurlHttpClient())->post($this->baseUrl.$path,[]);
    }
    public static function badResponses(): iterable
    {
        yield ['/invalid-json']; yield ['/array']; yield ['/http-error']; yield ['/redirect']; yield ['/empty'];
    }
    public function testTimeoutIsBounded(): void
    {
        $this->expectException(TransportException::class);
        (new CurlHttpClient(timeout:1,connectTimeout:1))->post($this->baseUrl.'/slow',[]);
    }
    public function testAuthorizationHeaderIsSentPerRequestAndNotReused(): void
    {
        $http = new CurlHttpClient();
        $authenticated = $http->post($this->baseUrl.'/ok',['pin'=>'pin'],['Authorization'=>'Bearer fixture-token']);
        self::assertSame('Bearer fixture-token',$authenticated->authorization);
        self::assertFalse(property_exists($authenticated->parameters,'access_token'));
        $anonymous = $http->post($this->baseUrl.'/ok',['pin'=>'pin']);
        self::assertNull($anonymous->authorization);
    }

}
