<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
            })
            ->withCount(['suggestions', 'activeSuggestions'])
            ->paginate(20);

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Show user details with additional info and suggestions
     */
    public function show(User $user)
    {
        $user->load(['suggestions.createdBy']);
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show form to add suggestion for user
     */
    public function createSuggestion(User $user)
    {
        return view('admin.users.create-suggestion', compact('user'));
    }

    /**
     * Store a new suggestion for user
     */
    public function storeSuggestion(Request $request, User $user)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'suggestion' => 'required|string|max:1000',
        ]);

        UserSuggestion::create([
            'user_id' => $user->id,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'suggestion' => $validated['suggestion'],
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Suggestion toegevoegd voor ' . $user->name);
    }

    /**
     * Update a suggestion
     */
    public function updateSuggestion(Request $request, UserSuggestion $suggestion)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'suggestion' => 'required|string|max:1000',
        ]);

        $suggestion->update($validated);

        return redirect()->route('admin.users.show', $suggestion->user)
            ->with('success', 'Suggestion bijgewerkt');
    }

    /**
     * Delete a suggestion
     */
    public function deleteSuggestion(UserSuggestion $suggestion)
    {
        $user = $suggestion->user;
        $suggestion->delete();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Suggestion verwijderd');
    }

    /**
     * Overview of all suggestions
     */
    public function suggestionsOverview()
    {
        $suggestions = UserSuggestion::with(['user', 'createdBy'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_suggestions' => UserSuggestion::count(),
            'active_suggestions' => UserSuggestion::where('status', 'active')->count(),
            'completed_suggestions' => UserSuggestion::where('status', 'completed')->count(),
            'dismissed_suggestions' => UserSuggestion::where('status', 'dismissed')->count(),
        ];

        return view('admin.suggestions.overview', compact('suggestions', 'stats'));
    }
}