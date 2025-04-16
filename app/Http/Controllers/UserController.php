<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SmartMeter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Toon een lijst met alle gebruikers
     */
    public function index()
    {
        $users = User::withCount('smartMeters')->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Toon het formulier voor het aanmaken van een nieuwe gebruiker
     */
    public function create()
    {
        return view('users.form');
    }

    /**
     * Sla een nieuwe gebruiker op in de database
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request);
        
        // Hash het wachtwoord
        $validated['password'] = Hash::make($validated['password']);
        
        // Stel active in als het niet is ingesteld
        $validated['active'] = $request->has('active') ? 1 : 0;
        
        // Maak de gebruiker aan
        $user = User::create($validated);
        
        return redirect()->route('users.index')
            ->with('status', 'Gebruiker succesvol aangemaakt!');
    }

    /**
     * Toon de details van een specifieke gebruiker
     */
    public function show(User $user)
    {
        $user->load('smartMeters');
        return view('users.show', compact('user'));
    }

    /**
     * Toon het formulier voor het bewerken van een gebruiker
     */
    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    /**
     * Update een specifieke gebruiker in de database
     */
    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user->id);
        
        // Als er een wachtwoord is opgegeven, hash deze
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Stel active in
        $validated['active'] = $request->has('active') ? 1 : 0;
        
        // Update de gebruiker
        $user->update($validated);
        
        return redirect()->route('users.show', $user->id)
            ->with('status', 'Gebruiker succesvol bijgewerkt!');
    }

    /**
     * Verwijder een specifieke gebruiker uit de database
     */
    public function destroy(User $user)
    {
        // Als de gebruiker slimme meters heeft, ontkoppel deze
        foreach ($user->smartMeters as $meter) {
            $meter->account_id = null;
            $meter->save();
        }
        
        // Verwijder de gebruiker
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('status', 'Gebruiker succesvol verwijderd!');
    }
    
    /**
     * Valideer de gebruiker gegevens
     */
    private function validateUser(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $id ? Rule::unique('users')->ignore($id) : Rule::unique('users'),
            ],
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:user,admin,owner',
        ];
        
        // Als het een nieuwe gebruiker is of als er een wachtwoord is opgegeven
        if (!$id || $request->filled('password')) {
            $rules['password'] = 'required|min:8|confirmed';
        }
        
        return $request->validate($rules);
    }
}