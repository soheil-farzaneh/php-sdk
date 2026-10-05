<?php
namespace Aqayepardakht\PhpSdk\Infrastructure\Browser;

use Aqayepardakht\PhpSdk\Helper;

/** Retains the original JSON/HTML output and termination at the presentation boundary. */
final class PaymentRedirector
{
    public function redirect(string $url): never
    {
        Helper::validateUrl($url);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['url' => $url], JSON_THROW_ON_ERROR);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<script>window.location.href='.json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR).';</script>';
        }
        exit;
    }
}
