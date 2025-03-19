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
        $users = User::with('smartMeter')->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Toon het formulier voor het aanmaken van een nieuwe gebruiker
     */
    public function create()
    {
        $smartMeters = SmartMeter::whereDoesntHave('user')->get();
        return view('users.form', compact('smartMeters'));
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
        
        // Koppel de smart meter indien geselecteerd
        if (!empty($validated['smart_meter_id'])) {
            $smartMeter = SmartMeter::findOrFail($validated['smart_meter_id']);
            $smartMeter->account_id = $user->id;
            $smartMeter->save();
        }
        
        return redirect()->route('users.index')
            ->with('status', 'Gebruiker succesvol aangemaakt!');
    }

    /**
     * Toon de details van een specifieke gebruiker
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Toon het formulier voor het bewerken van een gebruiker
     */
    public function edit(User $user)
    {
        // Haal slimme meters op die niet gekoppeld zijn OF gekoppeld aan deze gebruiker
        $smartMeters = SmartMeter::where(function($query) use ($user) {
            $query->whereDoesntHave('user')
                  ->orWhere('account_id', $user->id);
        })->get();
        
        return view('users.form', compact('user', 'smartMeters'));
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
        
        // Update smart meter koppeling
        if (isset($validated['smart_meter_id'])) {
            // Als er een smart meter was gekoppeld, maak deze los
            if ($user->smartMeter) {
                $user->smartMeter->account_id = null;
                $user->smartMeter->save();
            }
            
            // Als er een nieuwe smart meter is geselecteerd, koppel deze
            if (!empty($validated['smart_meter_id'])) {
                $smartMeter = SmartMeter::findOrFail($validated['smart_meter_id']);
                $smartMeter->account_id = $user->id;
                $smartMeter->save();
            }
        }   
        
        return redirect()->route('users.show', $user->id)
            ->with('status', 'Gebruiker succesvol bijgewerkt!');
    }

    /**
     * Verwijder een specifieke gebruiker uit de database
     */
    public function destroy(User $user)
    {
        // Als de gebruiker een slimme meter heeft, ontkoppel deze
        if ($user->smartMeter) {
            $user->smartMeter->account_id = null;
            $user->smartMeter->save();
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
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'role' => 'required|in:user,admin,owner',
            'smart_meter_id' => 'nullable|exists:smart_meters,id',
        ];
        
        // Als het een nieuwe gebruiker is of als er een wachtwoord is opgegeven
        if (!$id || $request->filled('password')) {
            $rules['password'] = 'required|min:8|confirmed';
        }
        
        return $request->validate($rules);
    }
}