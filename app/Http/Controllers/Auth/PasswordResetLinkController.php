<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        try {
            $status = Password::sendResetLink($request->only('email'));
            if ($status == Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }
            return back()->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            SystemLog::log('mail_failed', 'Password reset email could not be sent: '.$e->getMessage(), [
                'email' => $request->input('email'),
                'exception' => get_class($e),
            ]);
            return back()->withErrors(['email' => 'We could not send the reset link. The error has been logged for administrators.']);
        }
    }
}
