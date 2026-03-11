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
    private const SCOPE_GROUP_READONLY = 'https://www.googleapis.com/auth/admin.directory.group.readonly';
    private const SCOPE_GROUP_MEMBER_READONLY = 'https://www.googleapis.com/auth/admin.directory.group.member.readonly';
    private const SCOPE_ORGUNIT_READONLY = 'https://www.googleapis.com/auth/admin.directory.orgunit.readonly';

    /**
     * List groups in the tenant's domain, optionally filtered by search query.
     *
     * @return array<int, array{email: string, name: string}>
     */
    public function listGroups(Tenant $tenant, ?string $query = null): array
    {
        $path = $tenant->google_credentials_path ?? config('phishing.google_credentials_path');
        $adminUser = $tenant->google_admin_user ?? config('phishing.google_admin_user');
        $domain = $tenant->domain ?? config('phishing.google_domain', '');

        if (! $path || ! is_file($path)) {
            Log::warning('GoogleGroupService: no credentials path or file not found', ['tenant_id' => $tenant->id]);
            return [];
        }
        if (! $adminUser) {
            Log::warning('GoogleGroupService: no admin user set for impersonation', ['tenant_id' => $tenant->id]);
            return [];
        }
        if ($domain === '') {
            Log::warning('GoogleGroupService: no domain set for list groups', ['tenant_id' => $tenant->id]);
            return [];
        }

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($path);
            $client->setScopes([Directory::ADMIN_DIRECTORY_USER_READONLY, self::SCOPE_GROUP_READONLY]);
            $client->setSubject($adminUser);

            $directory = new Directory($client);
            $out = [];
            $pageToken = null;

            do {
                $params = [
                    'domain' => $domain,
                    'maxResults' => 200,
                ];
                if ($query !== null && trim($query) !== '') {
                    $params['query'] = trim($query);
                }
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                $response = $directory->groups->listGroups($params);
                foreach ($response->getGroups() ?? [] as $group) {
                    $email = $group->getEmail();
                    if ($email) {
                        $out[] = [
                            'email' => strtolower($email),
                            'name' => trim((string) $group->getName()) ?: $email,
                        ];
                    }
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $out;
        } catch (\Throwable $e) {
            Log::warning('GoogleGroupService: failed to list groups', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * List organizational units for the tenant's customer (Google Workspace).
     *
     * @return array<int, array{path: string, name: string}>
     */
    public function listOus(Tenant $tenant): array
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
            $client->setScopes([Directory::ADMIN_DIRECTORY_USER_READONLY, self::SCOPE_ORGUNIT_READONLY]);
            $client->setSubject($adminUser);

            $directory = new Directory($client);
            $out = [];
            $pageToken = null;
            $customerId = 'my_customer';

            do {
                $params = ['type' => 'ALL'];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                $response = $directory->orgunits->listOrgunits($customerId, $params);
                foreach ($response->getOrganizationUnits() ?? [] as $ou) {
                    $ouPath = $ou->getOrgUnitPath();
                    $name = $ou->getName();
                    if ($ouPath !== null) {
                        $out[] = [
                            'path' => $ouPath,
                            'name' => trim((string) $name) ?: $ouPath,
                        ];
                    }
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $out;
        } catch (\Throwable $e) {
            Log::warning('GoogleGroupService: failed to list org units', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /** Max depth when expanding nested groups to avoid runaway recursion. */
    private const NESTED_GROUP_MAX_DEPTH = 20;

    /**
     * List all member email addresses for a group. Recursively expands nested groups
     * so that every user in the hierarchy is included (one entry per person). Cycles
     * are avoided by tracking visited groups; depth is limited by NESTED_GROUP_MAX_DEPTH.
     *
     * @return array<int, array{email: string, name: string|null}>
     */
    public function listGroupMemberEmails(Tenant $tenant, string $groupEmail): array
    {
        return $this->listGroupMemberEmailsRecursive($tenant, strtolower($groupEmail), [], 0);
    }

    /**
     * @param  array<string, true>  $visitedGroupEmails  Lowercase group emails already being expanded (cycle guard)
     * @return array<int, array{email: string, name: string|null}>
     */
    private function listGroupMemberEmailsRecursive(Tenant $tenant, string $groupEmail, array $visitedGroupEmails, int $depth): array
    {
        if ($depth >= self::NESTED_GROUP_MAX_DEPTH) {
            Log::warning('GoogleGroupService: nested group max depth reached', ['group' => $groupEmail, 'tenant_id' => $tenant->id]);
            return [];
        }
        if (isset($visitedGroupEmails[$groupEmail])) {
            return [];
        }
        $visitedGroupEmails[$groupEmail] = true;

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
            $seenEmails = [];
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
                    $emailLower = strtolower($email);
                    if (strtoupper((string) $type) === 'USER') {
                        if (! isset($seenEmails[$emailLower])) {
                            $seenEmails[$emailLower] = true;
                            $members[] = ['email' => $emailLower, 'name' => null];
                        }
                    } elseif (strtoupper((string) $type) === 'GROUP') {
                        $nested = $this->listGroupMemberEmailsRecursive($tenant, $emailLower, $visitedGroupEmails, $depth + 1);
                        foreach ($nested as $n) {
                            $e = $n['email'];
                            if (! isset($seenEmails[$e])) {
                                $seenEmails[$e] = true;
                                $members[] = $n;
                            }
                        }
                    }
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

    /**
     * List user email addresses in a Google Workspace organizational unit.
     * Uses Directory API users.list with query orgUnitPath. OU path should be
     * as in Admin console (e.g. "/Staff" or "/Students"). Matches that OU and descendants.
     *
     * @return array<int, array{email: string, name: string|null}>
     */
    public function listUserEmailsInOu(Tenant $tenant, string $orgUnitPath): array
    {
        $path = $tenant->google_credentials_path ?? config('phishing.google_credentials_path');
        $adminUser = $tenant->google_admin_user ?? config('phishing.google_admin_user');
        $domain = $tenant->domain ?? config('phishing.google_domain', '');

        if (! $path || ! is_file($path)) {
            Log::warning('GoogleGroupService: no credentials path or file not found', ['tenant_id' => $tenant->id]);
            return [];
        }
        if (! $adminUser) {
            Log::warning('GoogleGroupService: no admin user set for impersonation', ['tenant_id' => $tenant->id]);
            return [];
        }
        if ($domain === '') {
            Log::warning('GoogleGroupService: no domain set for OU listing', ['tenant_id' => $tenant->id]);
            return [];
        }

        $ouPath = trim($orgUnitPath);
        if ($ouPath !== '' && ! str_starts_with($ouPath, '/')) {
            $ouPath = '/'.$ouPath;
        }
        $query = $ouPath === '' ? null : 'orgUnitPath=\''.$ouPath.'\'';

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($path);
            $client->setScopes([Directory::ADMIN_DIRECTORY_USER_READONLY]);
            $client->setSubject($adminUser);

            $directory = new Directory($client);
            $users = [];
            $pageToken = null;

            do {
                $params = [
                    'domain' => $domain,
                    'maxResults' => 200,
                    'viewType' => 'admin_view',
                ];
                if ($query !== null) {
                    $params['query'] = $query;
                }
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                $response = $directory->users->listUsers($params);
                foreach ($response->getUsers() ?? [] as $user) {
                    $email = $user->getPrimaryEmail();
                    if ($email) {
                        $users[] = [
                            'email' => strtolower($email),
                            'name' => $user->getName() ? trim($user->getName()->getFullName() ?? '') : null,
                        ];
                    }
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $users;
        } catch (\Throwable $e) {
            Log::warning('GoogleGroupService: failed to list users in OU', [
                'org_unit_path' => $orgUnitPath,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
