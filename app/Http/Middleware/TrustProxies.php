<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Trusted proxies come from TRUSTED_PROXIES ("*" or a comma-separated
     * list of IPs/CIDRs). Unset keeps the previous behaviour (none trusted).
     * Needed when the new domain sits behind a reverse proxy / CDN, so that
     * https is detected and generated URLs use the right scheme.
     */
    protected function proxies()
    {
        $proxies = env('TRUSTED_PROXIES');

        if ($proxies === null || $proxies === '') {
            return $this->proxies;
        }

        return $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
    }
}
