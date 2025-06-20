<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InfluxdbOutage;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InfluxdbOutageController extends Controller
{
    public function __construct()
    {

    }

    public function index(): View
    {
        $outages = InfluxdbOutage::orderBy('start_time', 'desc')->paginate(15);
        return view('admin.influxdb-outages.index', compact('outages'));
    }

    public function create(): View
    {
        return view('admin.influxdb-outages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:actief,opgelost'
        ]);

        InfluxdbOutage::create($validated);

        return redirect()->route('admin.influxdb-outages.index')
            ->with('success', 'Uitval tijdstip succesvol toegevoegd.');
    }

    public function edit(InfluxdbOutage $influxdbOutage): View
    {
        return view('admin.influxdb-outages.edit', compact('influxdbOutage'));
    }

    public function update(Request $request, InfluxdbOutage $influxdbOutage): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:actief,opgelost'
        ]);

        $influxdbOutage->update($validated);

        return redirect()->route('admin.influxdb-outages.index')
            ->with('success', 'Uitval tijdstip succesvol bijgewerkt.');
    }

    public function destroy(InfluxdbOutage $influxdbOutage): RedirectResponse
    {
        $influxdbOutage->delete();
        return redirect()->route('admin.influxdb-outages.index')
            ->with('success', 'Uitval tijdstip succesvol verwijderd.');
    }
}