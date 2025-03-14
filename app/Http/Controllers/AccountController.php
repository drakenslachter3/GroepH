<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SmartMeter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Toon een lijst met alle accounts
     */
    public function index()
    {
        $accounts = Account::with('smartMeter')->paginate(10);
        return view('Accounts.index', compact('accounts'));
    }

    /**
     * Toon het formulier voor het aanmaken van een nieuw account
     */
    public function create()
    {
        $smartMeters = SmartMeter::whereDoesntHave('account')->get();
        return view('Accounts.form', compact('smartMeters'));
    }

    /**
     * Sla een nieuw account op in de database
     */
    public function store(Request $request)
    {
        $validated = $this->validateAccount($request);
        
        // Hash het wachtwoord
        $validated['password'] = Hash::make($validated['password']);
        
        // Stel active in als het niet is ingesteld
        $validated['active'] = $request->has('active') ? 1 : 0;
        
        // Maak het account aan
        $account = Account::create($validated);
        
        // Koppel de smart meter indien geselecteerd
        if (!empty($validated['smart_meter_id'])) {
            $smartMeter = SmartMeter::findOrFail($validated['smart_meter_id']);
            $smartMeter->account_id = $account->id;
            $smartMeter->save();
        }
        
        return redirect()->route('accounts.index')
            ->with('status', 'Account succesvol aangemaakt!');
    }

    /**
     * Toon de details van een specifiek account
     */
    public function show(Account $account)
    {
        return view('accounts.show', compact('account'));
    }

    /**
     * Toon het formulier voor het bewerken van een account
     */
    public function edit(Account $account)
    {
        // Haal slimme meters op die niet gekoppeld zijn OF gekoppeld aan dit account
        $smartMeters = SmartMeter::where(function($query) use ($account) {
            $query->whereDoesntHave('account')
                  ->orWhere('account_id', $account->id);
        })->get();
        
        return view('Accounts.form', compact('account', 'smartMeters'));
    }

    /**
     * Update een specifiek account in de database
     */
    public function update(Request $request, Account $account)
    {
        $validated = $this->validateAccount($request, $account->id);
        
        // Als er een wachtwoord is opgegeven, hash deze
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Stel active in
        $validated['active'] = $request->has('active') ? 1 : 0;
        
        // Update het account
        $account->update($validated);
        
        // Update smart meter koppeling
        if (isset($validated['smart_meter_id'])) {
            // Als er een smart meter was gekoppeld, maak deze los
            if ($account->smartMeter) {
                $account->smartMeter->account_id = null;
                $account->smartMeter->save();
            }
            
            // Als er een nieuwe smart meter is geselecteerd, koppel deze
            if (!empty($validated['smart_meter_id'])) {
                $smartMeter = SmartMeter::findOrFail($validated['smart_meter_id']);
                $smartMeter->account_id = $account->id;
                $smartMeter->save();
            }
        }
        
        return redirect()->route('accounts.show', $account->id)
            ->with('status', 'Account succesvol bijgewerkt!');
    }

    /**
     * Verwijder een specifiek account uit de database
     */
    public function destroy(Account $account)
    {
        // Als het account een slimme meter heeft, ontkoppel deze
        if ($account->smartMeter) {
            $account->smartMeter->account_id = null;
            $account->smartMeter->save();
        }
        
        // Verwijder het account
        $account->delete();
        
        return redirect()->route('Accounts.index')
            ->with('status', 'Account succesvol verwijderd!');
    }
    
    /**
     * Valideer de account gegevens
     */
    private function validateAccount(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $id ? Rule::unique('accounts')->ignore($id) : Rule::unique('accounts'),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'role' => 'required|in:user,admin,owner',
            'smart_meter_id' => 'nullable|exists:smart_meters,id',
        ];
        
        // Als het een nieuw account is of als er een wachtwoord is opgegeven
        if (!$id || $request->filled('password')) {
            $rules['password'] = 'required|min:8|confirmed';
        }
        
        return $request->validate($rules);
    }
}