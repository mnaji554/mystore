<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourierDocumentController extends Controller
{
    public function __invoke(User $user, string $document): StreamedResponse
    {
        $this->authorize('manage-shipping');
        abort_unless($user->hasRole(Role::COURIER), 404);

        $column = match ($document) {
            'national-id' => 'national_id_image_path',
            'vehicle-registration' => 'vehicle_registration_image_path',
            'driving-license' => 'driving_license_image_path',
            default => abort(404),
        };

        $path = $user->courierProfile?->{$column};
        abort_unless($path && Str::startsWith($path, "courier-documents/{$user->id}/"), 404);

        return Storage::disk('private_documents')->download($path, "{$document}.".pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
