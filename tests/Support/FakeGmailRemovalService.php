<?php

namespace Tests\Support;

use App\Services\GmailRemovalService;

/**
 * Test double for GmailRemovalService. Configure users and per-email trash results.
 */
class FakeGmailRemovalService extends GmailRemovalService
{
    public function __construct(
        private array $users = [],
        private array $trashReturns = []
    ) {
        // Do not call parent - avoids config/credentials in tests.
    }

    public function listDomainUsers(string $domain): array
    {
        return $this->users;
    }

    public function trashMessageByRfc822MessageId(string $userEmail, string $rfc822MessageId): array
    {
        return $this->trashReturns[$userEmail] ?? ['ok' => true];
    }
}
