<?php

namespace App\Services;

use App\Models\Tenant;
use Google\Client as GoogleClient;
use Google\Service\Directory;
use Illuminate\Support\Facades\Log;

/**
 * Resolve Google Workspace group membership via Admin SDK Directory API.
 * Uses the tenant's service account with domain-wide delegation.
 */
class GoogleGroupService
{
    /**
     * List all member email addresses for a group. Returns USER-type members only;
     * nested groups are not expanded. Each person receives one entry so campaigns
     * can send one email per person.
     *
     * @return array<int, array{email: string, name: string|null}>
     */
    public function listGroupMemberEmails(Tenant $tenant, string $groupEmail): array
    {
        $path = $tenant->google_credentials_path ?? config('phishing.google_credentials_path');
        $adminUser = $tenant->google_admin_user ?? config('phishing.google_admin_user');

        if (! $path || ! is_file($path)) {
            Log::warning('GoogleGroupService: no credentials path or file not found', ['tenant_id' => $tenant->id]);
            return [];
        }
        if (! $adminUser) {
            Log::warning('GoogleGroupService: no admin user set for impersonation', ['tenant_id' => $tenant->id]);
            return [];
        }

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($path);
            $client->setScopes([
                Directory::ADMIN_DIRECTORY_USER_READONLY,
                'https://www.googleapis.com/auth/admin.directory.group.member.readonly',
            ]);
            $client->setSubject($adminUser);

            $directory = new Directory($client);
            $members = [];
            $pageToken = null;

            do {
                $params = ['maxResults' => 200];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                $response = $directory->members->listMembers($groupEmail, $params);
                foreach ($response->getMembers() ?? [] as $member) {
                    $type = $member->getType();
                    $email = $member->getEmail();
                    if (! $email) {
                        continue;
                    }
                    if (strtoupper((string) $type) === 'USER') {
                        $members[] = ['email' => strtolower($email), 'name' => null];
                    }
                    // Skip GROUP type (nested groups) to avoid recursion; only direct USER members
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $members;
        } catch (\Throwable $e) {
            Log::warning('GoogleGroupService: failed to list group members', [
                'group' => $groupEmail,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
