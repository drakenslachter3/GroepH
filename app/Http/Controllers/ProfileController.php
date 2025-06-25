<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'additional_info' => 'nullable|string|max:1000',
        ]);

        $user->update($validated);

        return redirect()->route('profile.edit')
            ->with('success', 'Profiel succesvol bijgewerkt!');
    }

    public function suggestions()
    {
        $user = Auth::user();
        $suggestions = $user->activeSuggestions()->with('createdBy')->latest()->get();
        
        return view('profile.suggestions', compact('suggestions'));
    }

    public function dismissSuggestion($suggestionId)
    {
        $user = Auth::user();
        
        $suggestion = $user->suggestions()->findOrFail($suggestionId);
        $suggestion->dismiss();

        return response()->json(['message' => 'Suggestion dismissed']);
    }

    public function completeSuggestion($suggestionId)
    {
        $user = Auth::user();
        
        $suggestion = $user->suggestions()->findOrFail($suggestionId);
        $suggestion->markCompleted();

        return response()->json(['message' => 'Suggestion marked as completed']);
    }
}