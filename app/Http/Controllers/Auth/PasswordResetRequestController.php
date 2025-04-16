<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PasswordResetRequested;
use App\Mail\PasswordResetApproved;
use App\Mail\PasswordResetDenied;
use Carbon\Carbon;

class PasswordResetRequestController extends Controller
{
    /**
     * Show the form to request a password reset.
     */
    public function create()
    {
        return view('auth.password-reset-request');
    }

    /**
     * Handle a password reset request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if there's an existing pending request
        $existingRequest = PasswordResetRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingRequest) {
            return back()->with('status', 'A password reset request has already been submitted. Please wait for admin approval.');
        }

        // Create a new password reset request
        $token = Str::random(64);
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => $token,
            'expires_at' => Carbon::now()->addDays(1), // Expires in 24 hours
        ]);

        //Hier zou je een mail sturen naar de admin om te resetten
        // Mail::to(ADMIN EMAIL HIER)->send(new PasswordResetRequested($resetRequest));

        return back()->with('status', 'Your password reset request has been submitted and is pending admin approval.');
    }

    /**
     * Show the admin inbox for password reset requests.
     */
    public function adminIndex()
    {
        $resetRequests = PasswordResetRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.password-reset-inbox', compact('resetRequests'));
    }

    /**
     * Approve a password reset request.
     */
    public function approve(PasswordResetRequest $resetRequest)
    {
        if (!$resetRequest->isPending() || $resetRequest->isExpired()) {
            return back()->with('error', 'This password reset request cannot be approved.');
        }

        $resetRequest->approve();
        
        // Send email to user with reset link
        Mail::to($resetRequest->email)->send(new PasswordResetApproved($resetRequest));

        return back()->with('status', 'Password reset request has been approved and email sent to the user.');
    }

    /**
     * Deny a password reset request.
     */
    public function deny(PasswordResetRequest $resetRequest)
    {
        if (!$resetRequest->isPending() || $resetRequest->isExpired()) {
            return back()->with('error', 'This password reset request cannot be denied.');
        }

        $resetRequest->deny();
        
        // Send email to user notifying of denial
        Mail::to($resetRequest->email)->send(new PasswordResetDenied($resetRequest));

        return back()->with('status', 'Password reset request has been denied and email sent to the user.');
    }

    /**
     * Show the reset password form.
     */
    public function resetForm(string $token)
    {
        $resetRequest = PasswordResetRequest::where('token', $token)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRequest) {
            return redirect()->route('password.request')
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $resetRequest->email]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $resetRequest = PasswordResetRequest::where('token', $request->token)
            ->where('email', $request->email)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRequest) {
            return redirect()->route('password.request')
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        $user = User::find($resetRequest->user_id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete all reset requests for this user
        PasswordResetRequest::where('user_id', $user->id)->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }
}