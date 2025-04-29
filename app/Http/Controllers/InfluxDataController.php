<?php

namespace App\Http\Controllers;

use App\Models\InfluxData;
use App\Services\InfluxDBService;
use Illuminate\Http\Request;

class InfluxDataController extends Controller
{
    protected $influxService;

    public function __construct(InfluxDBService $influxService)
    {
        $this->influxService = $influxService;
    }

    /**
     * Display a listing of the data from MySQL
     */
    public function index()
    {
        $data = InfluxData::orderBy('time', 'desc')->paginate(15);
        return view('influx.index', compact('data'));
    }

    /**
     * Show the form for creating a new query
     */
    public function create()
    {
        return view('influx.create');
    }

    /**
     * Execute a query and store the results
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string',
        ]);

        try {
            $results = $this->influxService->queryAndSave($validated['query']);
            return redirect()->route('influx.index')
                ->with('success', 'Query executed successfully. ' . count($results) . ' records saved.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error executing query: ' . $e->getMessage());
        }
    }

    /**
     * Test the connection to InfluxDB
     */
    public function testConnection()
    {
        $success = $this->influxService->testConnection();
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful!' : 'Connection failed. Check configuration.',
        ]);
    }
}