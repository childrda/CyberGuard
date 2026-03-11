<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\GoogleGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Workspace (Google) groups and OUs for campaign target selection.
 * Tenant-scoped; requires directory_sync_enabled.
 */
class WorkspaceController extends Controller
{
    public function __construct(
        protected GoogleGroupService $googleGroupService
    ) {}

    /**
     * GET /admin/workspace/groups?search=...
     */
    public function groups(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        if (! $tenant) {
            return response()->json(['error' => 'No tenant selected.'], 403);
        }
        if (! $tenant->directory_sync_enabled) {
            return response()->json(['error' => 'Directory sync is not enabled for this tenant.'], 403);
        }

        $search = $request->query('search');
        $groups = $this->googleGroupService->listGroups($tenant, $search === null ? null : (string) $search);

        return response()->json(['groups' => $groups]);
    }

    /**
     * GET /admin/workspace/ous
     */
    public function ous(): JsonResponse
    {
        $tenant = Tenant::current();
        if (! $tenant) {
            return response()->json(['error' => 'No tenant selected.'], 403);
        }
        if (! $tenant->directory_sync_enabled) {
            return response()->json(['error' => 'Directory sync is not enabled for this tenant.'], 403);
        }

        $ous = $this->googleGroupService->listOus($tenant);

        return response()->json(['ous' => $ous]);
    }

    /**
     * POST /admin/workspace/resolve
     * Body: { "group_emails": ["a@domain.com"], "ou_paths": ["/Staff"] }
     * Returns combined, deduplicated list of { email, name }.
     */
    public function resolve(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        if (! $tenant) {
            return response()->json(['error' => 'No tenant selected.'], 403);
        }
        if (! $tenant->directory_sync_enabled) {
            return response()->json(['error' => 'Directory sync is not enabled for this tenant.'], 403);
        }

        $validated = $request->validate([
            'group_emails' => ['nullable', 'array'],
            'group_emails.*' => ['string', 'email'],
            'ou_paths' => ['nullable', 'array'],
            'ou_paths.*' => ['string', 'max:500'],
        ]);

        $groupEmails = $validated['group_emails'] ?? [];
        $ouPaths = $validated['ou_paths'] ?? [];
        $emails = [];

        foreach ($groupEmails as $groupEmail) {
            $groupEmail = strtolower(trim($groupEmail));
            if ($groupEmail === '') {
                continue;
            }
            $members = $this->googleGroupService->listGroupMemberEmails($tenant, $groupEmail);
            foreach ($members as $m) {
                $emails[] = $m;
            }
        }

        foreach ($ouPaths as $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }
            $users = $this->googleGroupService->listUserEmailsInOu($tenant, $path);
            foreach ($users as $u) {
                $emails[] = $u;
            }
        }

        $seen = [];
        $out = [];
        foreach ($emails as $r) {
            $email = strtolower($r['email'] ?? '');
            if ($email && ! isset($seen[$email])) {
                $seen[$email] = true;
                $out[] = ['email' => $email, 'name' => $r['name'] ?? null];
            }
        }

        return response()->json(['emails' => $out]);
    }
}
