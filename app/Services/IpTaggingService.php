<?php

namespace App\Services;

class IpTaggingService
{

    protected static $rsphRanges = [
        '172.17.0.0/21', '172.18.0.0/21', '172.20.0.0/21', '172.21.0.0/21'
    ];

    protected static $opthRanges = [
        '172.26.0.0/21', '172.27.0.0/21'
    ];

    public static function getTagByIp($ip)
    {
        if (!$ip) return 'N/A';

        foreach (self::$rsphRanges as $range) {
            if (self::ipInRange($ip, $range)) return 'RSPH';
        }

        foreach (self::$opthRanges as $range) {
            if (self::ipInRange($ip, $range)) return 'OPTH';
        }

        return 'EXTERNAL';
    }

    private static function ipInRange($ip, $range)
    {
        list($subnet, $bits) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        return ($ip_long & $mask) == ($subnet_long & $mask);
    }
}
