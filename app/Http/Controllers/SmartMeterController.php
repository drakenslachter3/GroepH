<?php

namespace App\Http\Controllers;

use App\Models\SmartMeter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SmartMeterController extends Controller
{
    /**
     * Constructor with authorization check
     */
    public function __construct()
    {
        // We'll check authorization in each method instead of using middleware
    }

    /**
     * Check if user has admin access
     */
    private function checkAdminAccess()
    {
        if (!Auth::user() || !Auth::user()->hasRole(['admin', 'owner'])) {
            abort(403, 'Geen toegang. Alleen beheerders kunnen meters beheren.');
        }
    }

    /**
     * Toon alle meters
     */
    public function index()
    {
        $this->checkAdminAccess();
        
        $smartMeters = SmartMeter::with('user')->paginate(10);
        return view('smartmeters.index', compact('smartMeters'));
    }

    /**
     * Toon formulier om een nieuwe meter toe te voegen
     */
    public function create(Request $request)
    {
        $this->checkAdminAccess();
        
        // Als er een user_id parameter is, haal de gebruiker op
        $selectedUser = null;
        if ($request->has('user_id')) {
            $selectedUser = User::find($request->user_id);
        }
        
        $users = User::orderBy('name')->get();
        
        return view('smartmeters.form', compact('users', 'selectedUser'));
    }

    /**
     * Sla een nieuwe meter op
     */
    public function store(Request $request)
    {
        $this->checkAdminAccess();
        
        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:255', 'unique:smart_meters'],
            'location' => ['nullable', 'string', 'max:255'],
            'measures_electricity' => ['boolean'],
            'measures_gas' => ['boolean'],
            'installation_date' => ['nullable', 'date'],
            'active' => ['boolean'],
            'account_id' => ['nullable', 'exists:users,id'],
        ]);

        // Zorg dat we tenminste één meettype hebben
        if (!($request->has('measures_electricity') || $request->has('measures_gas'))) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'De meter moet tenminste één meettype hebben (elektriciteit of gas).']);
        }

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['measures_electricity'] = $request->has('measures_electricity') ? 1 : 0;
        $validated['measures_gas'] = $request->has('measures_gas') ? 1 : 0;
        
        DB::beginTransaction();
        try {
            $smartmeter = SmartMeter::create($validated);
            DB::commit();
            
            if ($request->filled('account_id')) {
                return redirect()->route('smartmeters.userMeters', $validated['account_id'])
                    ->with('status', 'Slimme meter succesvol aangemaakt en gekoppeld!');
            }
            
            return redirect()->route('smartmeters.index')
                ->with('status', 'Slimme meter succesvol aangemaakt!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Er is een fout opgetreden: ' . $e->getMessage()]);
        }
    }

    /**
     * Toon details van een meter
     */
    public function show(SmartMeter $smartmeter)
    {
        $this->checkAdminAccess();
        
        return view('smartmeters.show', compact('smartmeter'));
    }

    /**
     * Toon formulier om een meter te bewerken
     */
    public function edit(SmartMeter $smartmeter) // Changed from $smartMeter to $smartmeter
    {
        $this->checkAdminAccess();
        
        $users = User::orderBy('name')->get();
        $selectedUser = $smartmeter->user;
        
        return view('smartmeters.form', compact('smartmeter', 'users', 'selectedUser'));
    }

    /**
     * Update een meter
     */
    public function update(Request $request, SmartMeter $smartmeter) // Changed from $smartMeter to $smartmeter
    {
        $this->checkAdminAccess();
        
        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:255', Rule::unique('smart_meters')->ignore($smartmeter->id)],
            'location' => ['nullable', 'string', 'max:255'],
            'measures_electricity' => ['boolean'],
            'measures_gas' => ['boolean'],
            'installation_date' => ['nullable', 'date'],
            'active' => ['boolean'],
            'account_id' => ['nullable', 'exists:users,id'],
        ]);

        // Zorg dat we tenminste één meettype hebben
        if (!($request->has('measures_electricity') || $request->has('measures_gas'))) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'De meter moet tenminste één meettype hebben (elektriciteit of gas).']);
        }

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['measures_electricity'] = $request->has('measures_electricity') ? 1 : 0;
        $validated['measures_gas'] = $request->has('measures_gas') ? 1 : 0;
        
        // Als er geen account_id is, zet deze op null
        if (!$request->filled('account_id')) {
            $validated['account_id'] = null;
        }
        
        DB::beginTransaction();
        try {
            $smartmeter->update($validated);
            DB::commit();
            
            return redirect()->route('smartmeters.show', $smartmeter->id)
                ->with('status', 'Slimme meter succesvol bijgewerkt!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Er is een fout opgetreden: ' . $e->getMessage()]);
        }
    }

    /**
     * Verwijder een meter
     */
    public function destroy(SmartMeter $smartmeter)
{
    $this->checkAdminAccess();
    
    try {
        DB::beginTransaction();
        
        // Verwijder daarna de meter zelf
        $smartmeter->delete();
        \Log::info('Smart meter deleted successfully');
        
        DB::commit();
        
        return redirect()->route('smartmeters.index')
            ->with('status', "Slimme meter succesvol verwijderd!");
    } catch (\Exception $e) {
        DB::rollBack();
        // Enhanced error logging
        \Log::error('Error deleting smart meter: ' . $e->getMessage());
        \Log::error('Exception stack trace: ' . $e->getTraceAsString());
        
        return redirect()->back()
            ->with('error', 'Fout bij het verwijderen van de meter: ' . $e->getMessage());
    }
}

    /**
     * Toon een gebruiker met zijn gekoppelde meters
     */
    public function userMeters(User $user)
    {
        $this->checkAdminAccess();
        
        $user->load('smartMeters');
        $availableMeters = SmartMeter::whereNull('account_id')
            ->orWhere('account_id', $user->id)
            ->get();
            
        return view('smartmeters.user-meters', compact('user', 'availableMeters'));
    }

    /**
     * Koppel een meter aan een gebruiker
     */
    public function linkMeter(Request $request, User $user)
    {
        $this->checkAdminAccess();
        
        $validated = $request->validate([
            'meter_id' => ['required', 'exists:smart_meters,id'],
        ]);
        
        $meter = SmartMeter::findOrFail($validated['meter_id']);
        
        // Als de meter al gekoppeld is aan een andere gebruiker, dan geven we een foutmelding
        if ($meter->account_id && $meter->account_id != $user->id) {
            return redirect()->back()
                ->with('error', 'Deze meter is al gekoppeld aan een andere gebruiker.');
        }
        
        try {
            // Koppel de meter aan de gebruiker
            $meter->account_id = $user->id;
            $meter->save();
            
            return redirect()->route('smartmeters.userMeters', $user->id)
                ->with('status', 'Slimme meter succesvol gekoppeld!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Fout bij het koppelen van de meter: ' . $e->getMessage());
        }
    }

    /**
     * Ontkoppel een meter van een gebruiker
     */
    public function unlinkMeter(Request $request, User $user, SmartMeter $smartmeter)
    {
        $this->checkAdminAccess();
        
        if ($smartmeter->account_id != $user->id) {
            return redirect()->back()
                ->with('error', 'Deze meter is niet gekoppeld aan deze gebruiker.');
        }
        
        try {
            $smartmeter->account_id = null;
            $smartmeter->save();
            
            return redirect()->route('smartmeters.userMeters', $user->id)
                ->with('status', 'Slimme meter succesvol ontkoppeld!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Fout bij het ontkoppelen van de meter: ' . $e->getMessage());
        }
    }

    /**
     * Zoek naar meters op basis van meter_id (voor AJAX)
     */
    public function search(Request $request)
    {
        $this->checkAdminAccess();
        
        $query = $request->get('query');
        
        $meters = SmartMeter::where('meter_id', 'like', '%' . $query . '%')
            ->whereNull('account_id')
            ->limit(10)
            ->get(['id', 'meter_id', 'location', 'measures_electricity', 'measures_gas']);
        
        // Converteer de meettype-velden naar een leesbare string
        $meters->each(function($meter) {
            $meter->type_description = $meter->getMeasurementTypeString();
        });
            
        return response()->json($meters);
    }

    
}