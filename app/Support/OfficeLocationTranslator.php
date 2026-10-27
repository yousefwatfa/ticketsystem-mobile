<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class OfficeLocationTranslator
{
    private const TRANSLATION_LOCALE = 'ar';

    public static function toArabic(?string $officeLocation): string
    {
        if (!$officeLocation) {
            return 'غير محدد';
        }

        $raw = trim((string) $officeLocation);
        if ($raw === '') {
            return 'غير محدد';
        }

        $candidates = self::buildCandidates($raw);

        foreach ($candidates as $candidate) {
            if (Lang::has("deptUser.{$candidate}", self::TRANSLATION_LOCALE)) {
                return Lang::get("deptUser.{$candidate}", [], self::TRANSLATION_LOCALE);
            }
        }

        $translations = Lang::get('deptUser', [], self::TRANSLATION_LOCALE);
        if (is_array($translations)) {
            foreach ($candidates as $candidate) {
                $normalizedCandidate = self::normalize($candidate);

                foreach ($translations as $key => $label) {
                    if (self::normalize((string) $key) === $normalizedCandidate) {
                        return (string) $label;
                    }
                }
            }
        }

        return $raw;
    }

    private static function buildCandidates(string $raw): array
    {
        $candidates = [$raw];

        $slashNormalized = str_replace('\\', '/', $raw);
        $baseName = basename($slashNormalized);
        if ($baseName !== '' && !in_array($baseName, $candidates, true)) {
            $candidates[] = $baseName;
        }

        if (preg_match('/OU=([^,]+)/i', $raw, $matches) && !empty($matches[1])) {
            $ou = trim($matches[1]);
            if ($ou !== '' && !in_array($ou, $candidates, true)) {
                $candidates[] = $ou;
            }
        }

        return $candidates;
    }

    private static function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }
}
