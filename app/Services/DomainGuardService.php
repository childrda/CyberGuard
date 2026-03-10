<?php

namespace App\Services;

/**
 * Hard block external domains. Only allowed domains may receive simulation emails.
 */
class DomainGuardService
{
    public function isAllowed(string $email, array $allowedDomains): bool
    {
        $email = strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return false;
        }
        $domain = substr($email, strrpos($email, '@') + 1);
        if (empty($allowedDomains)) {
            return false;
        }
        $allowed = array_map('strtolower', $allowedDomains);
        return in_array($domain, $allowed, true);
    }
}
