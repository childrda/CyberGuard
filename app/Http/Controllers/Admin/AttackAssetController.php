<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttackAsset;
use App\Models\PhishingAttack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttackAssetController extends Controller
{
    /**
     * List assets for the current tenant, optionally scoped to an attack.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('create', PhishingAttack::class);

        $query = AttackAsset::query()
            ->orderByDesc('created_at');

        if ($request->has('attack_id') && $request->attack_id) {
            $query->where('attack_id', $request->attack_id);
        }

        $assets = $query->paginate(24);

        $items = $assets->map(fn (AttackAsset $a) => [
            'id' => $a->id,
            'original_name' => $a->original_name,
            'url' => $a->url,
            'mime_type' => $a->mime_type,
            'width' => $a->width,
            'height' => $a->height,
            'created_at' => $a->created_at->toIso8601String(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    /**
     * Upload a new asset. Optional attack_id to associate with an attack.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PhishingAttack::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:5120'], // 5MB
            'attack_id' => ['nullable', 'exists:phishing_attacks,id'],
        ]);

        $file = $request->file('file');
        $tenantId = \App\Models\Tenant::currentId();
        if (! $tenantId) {
            return response()->json(['message' => 'No tenant context.'], 422);
        }

        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $filename = Str::random(12).'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.'.$ext;
        $dir = 'attack_assets/'.$tenantId;
        $path = $file->storeAs($dir, $filename, 'public');

        $asset = AttackAsset::create([
            'tenant_id' => $tenantId,
            'attack_id' => $validated['attack_id'] ?? null,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'storage_path' => $path,
            'public_url' => null,
            'width' => null,
            'height' => null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'id' => $asset->id,
            'original_name' => $asset->original_name,
            'url' => $asset->url,
            'mime_type' => $asset->mime_type,
            'width' => $asset->width,
            'height' => $asset->height,
        ], 201);
    }

    /**
     * Delete an asset and its file.
     */
    public function destroy(AttackAsset $asset): JsonResponse
    {
        if ($asset->tenant_id !== \App\Models\Tenant::currentId()) {
            abort(404);
        }
        $this->authorize('create', PhishingAttack::class);

        if (Storage::disk('public')->exists($asset->storage_path)) {
            Storage::disk('public')->delete($asset->storage_path);
        }
        $asset->delete();

        return response()->json(['deleted' => true]);
    }
}
