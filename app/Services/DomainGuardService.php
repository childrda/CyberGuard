<?php

namespace App\Services;

/**
 * Hard block external domains. Only allowed domains may receive simulation emails.
 * Block list is checked first (high-risk domains like Gmail, Yahoo, .gov never pass).
 */
class DomainGuardService
{
    /**
     * Whether the email's domain is on the high-risk block list (never send).
     */
    public function isBlocked(string $email): bool
    {
        $domain = $this->extractDomain($email);
        if ($domain === null) {
            return true;
        }
        $blocked = array_map('strtolower', config('phishing.blocked_target_domains', []));
        foreach ($blocked as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            if (str_starts_with($block, '.')) {
                if (str_ends_with($domain, $block) || $domain === ltrim($block, '.')) {
                    return true;
                }
            } elseif ($domain === $block) {
                return true;
            }
        }
        return false;
    }

    /**
     * Allowed only if not blocked and domain is in the allowed list.
     */
    public function isAllowed(string $email, array $allowedDomains): bool
    {
        if ($this->isBlocked($email)) {
            return false;
        }
        $domain = $this->extractDomain($email);
        if ($domain === null) {
            return false;
        }
        if (empty($allowedDomains)) {
            return false;
        }
        $allowed = array_map('strtolower', $allowedDomains);
        return in_array($domain, $allowed, true);
    }

    private function extractDomain(string $email): ?string
    {
        $email = strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return null;
        }
        return substr($email, strrpos($email, '@') + 1);
    }
}
