<?php
namespace App\Http\Controllers;

use App\Http\Controllers\EnergyVisualizationController;
use App\Models\SmartMeter;
use App\Models\UserGridLayout;
use App\Services\EnergyConversionService;
use App\Services\EnergyPredictionService;
use Carbon\Carbon;
use App\Services\EnergyNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private $energyVisController;
    private $notificationService;

    public function __construct(EnergyConversionService $conversionService, EnergyPredictionService $predictionService, EnergyNotificationService $notificationService)
    {
        $this->energyVisController = new EnergyVisualizationController($conversionService, $predictionService);
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Eerst data ophalen
        $energydashboard_data = $this->energyVisController->dashboard($request);

        $period = $energydashboard_data['period'] ?? 'month';

        // Load user's smart meters with latest readings

        $user->load(['smartMeters', 'smartMeters.latestReading']);

        $defaultPeriod = 'day';
        $defaultDate = Carbon::today()->format('Y-m-d');
        $defaultMeterId = optional(SmartMeter::getAllSmartMetersForCurrentUser()->first())->meter_id
                        ?? '2019-ETI-EMON-V01-105C4E-16405E';

        if (!isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }

        // Add last refresh time information
        $energydashboard_data['lastRefresh'] = Carbon::now()->format('d-m-Y H:i:s');

        // Include the user with smart meters data
        $energydashboard_data['user'] = $user;

        // Gebruik de juiste variabelen voor notificaties
        if (Auth::check() && isset($energydashboard_data['totals'])) {
            $this->notificationService->generateNotificationsForUser(
                Auth::user(),
                $energydashboard_data['totals']['electricity_prediction'] ?? [],
                $energydashboard_data['totals']['gas_prediction'] ?? [],
                $period
            );
        }

        if ($request->has('selectedMeterId')) {
            session(['selected_meter_id' => $request->input('selectedMeterId')]);
        }
        if ($request->has('period') && $request->has('date')) {
            session([
                'dashboard_period' => $request->input('period'),
                'dashboard_date'   => $request->input('date'),
            ]);
        }

        $period = session('dashboard_period', $defaultPeriod);
        $date = session('dashboard_date', $defaultDate);
        $selectedMeterId = session('selected_meter_id', $defaultMeterId);

        session([
            'dashboard_period' => $period,
            'dashboard_date' => $date,
            'selected_meter_id' => $selectedMeterId,
        ]);

        $energydashboard_data = $this->energyVisController->dashboard($request);

        $userGridLayoutModel = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );
        $energydashboard_data['gridLayout'] = $userGridLayoutModel->layout;

        if (! isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }

        $energydashboard_data['lastRefresh'] = Carbon::now()->format('d-m-Y H:i:s');
        $energydashboard_data['user'] = $user;
        $energydashboard_data['period'] = $period;
        $energydashboard_data['date'] = $date;
        $energydashboard_data['meterDataForPeriod'] = $this->getEnergyData($selectedMeterId, $period, $date);

        return view('dashboard', $energydashboard_data);
    }

    // New method to handle date and period settings
    public function setTime(Request $request)
    {
        $request->validate([
            'period'       => 'required|in:day,month,year',
            'date'         => 'required',
            'housing_type' => 'required|string',
        ]);

        $period      = $request->input('period');
        $housingType = $request->input('housing_type');
        $inputDate = $request->input('date');

        // Format the date based on the period type
        $formattedDate = $this->formatDateByPeriod($period, $inputDate);

        // Redirect back to dashboard with the new parameters
        return redirect()->route('dashboard', [
            'period'       => $period,
            'date'         => $formattedDate,
            'housing_type' => $housingType,
        ]);
    }

    // Helper method to format dates based on period
    private function formatDateByPeriod($period, $inputDate)
    {
        switch ($period) {
            case 'day':
                // For day period, the date should already be in YYYY-MM-DD format
                return $inputDate;

            case 'month':
                                                // For month period, ensure we have YYYY-MM-DD with first day of month
                if (strlen($inputDate) === 7) { // YYYY-MM format
                    return $inputDate;
                }
                return $inputDate;

            case 'year':
                                                // For year period, ensure we have YYYY-MM-DD with first day of year
                if (strlen($inputDate) === 4) { // YYYY format
                    return $inputDate;
                }
                return $inputDate;

            default:
                // Default to current date if something goes wrong
                return Carbon::now()->format('Y-m-d');
        }
    }

    public function setWidget(Request $request)
    {
        $user       = Auth::user();
        $position   = (int) $request->input('grid_position');
        $widgetType = $request->input('widget_type');

        $request->validate([
            'grid_position' => 'required|numeric',
            'widget_type'   => 'required|string',
        ]);

        $userGridLayout = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        $gridLayout  = $userGridLayout->layout;
        $widgetTypes = [$widgetType];

        foreach ($widgetTypes as $widget) {
            $currentIndex = array_search($widget, $gridLayout);
            if ($currentIndex !== false) {
                array_splice($gridLayout, $currentIndex, 1);

                if ($currentIndex < $position) {
                    $position--;
                }
            }
            array_splice($gridLayout, $position, 0, [$widget]);

            $position++;
        }

        $userGridLayout->layout = $gridLayout;
        $userGridLayout->save();

        return redirect()->route('dashboard')->with('status', 'Widget toegevoegd!');
    }

    public function resetLayout(Request $request)
    {
        $user = Auth::user();
        UserGridLayout::updateOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        return redirect()->route('dashboard')->with('status', 'Dashboard layout is gereset!');
    }

    private function getDefaultLayout()
    {
        return [
            'switch-meter',
            'energy-status-electricity',
            'energy-status-gas',
            'energy-chart-electricity',
            'energy-chart-gas',
            'usage-prediction',
            'date-selector',
            'historical-comparison',
            'trend-analysis',
            'energy-suggestions',
        ];
    }

    public function saveSelectedMeter(Request $request)
    {
        $meterDatabaseId = $request->meter;
        $layout  = UserGridLayout::where('user_id', auth()->id())->first();

        if ($layout) {
            $layout->selected_smartmeter = $meterDatabaseId;
            $layout->save();
        } else {
            throw new \Exception('[SaveSelectedMeter, DashboardController]: meter kan niet opgeslagen worden, omdat user_grid_layout nog niet bestaat voor deze gebruiker!');
        }

        $smartMeterId = SmartMeter::getMeterIdByDatabaseId($meterDatabaseId);

        return redirect()->route('dashboard', ['selectedMeterId' => $smartMeterId])
                         ->with('status', 'Meterkeuze doorgevoerd - het dashboard is nu up-to-date!');
    }

    private function getEnergyData(string $meterId, string $period, string $date)
    {
        // Probeer eerst uit de MySQL database op te halen
        $latestData = \App\Models\InfluxData::where('tags->meter_id', $meterId)
            ->where('tags->period', $period)
            ->where('tags->date', $date)
            ->orderBy('time', 'desc')
            ->first();

        if ($latestData) {
            return $latestData->fields;
        }

        // Als er geen gegevens zijn, haal ze dan op en sla ze op
        $influxService = app(\App\Services\InfluxDBService::class);
        $result        = $influxService->storeEnergyDashboardData($meterId, $period, $date);

        return $result['data'];
    }
}
