<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\MLModel;
use App\Models\User;
use App\Notifications\AlertCreatedNotification;
use App\Notifications\CriticalThreatDetectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Class AlertController
 *
 * Handles CRUD operations for Alerts and sending notifications
 * based on severity and user roles.
 *
 * @package App\Http\Controllers
 */
class AlertController extends Controller
{
    /**
     * Display a paginated list of alerts.
     *
     * @OA\Get(
     *     path="/alerts",
     *     summary="List alerts",
     *     tags={"Alerts"},
     *     @OA\Response(
     *         response=200,
     *         description="List of alerts returned successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $alerts = Alert::with(['networkLog', 'mlModel'])
            ->latest('detected_at')
            ->paginate(10);

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts
        ]);
    }

    /**
     * Show the form to create a new alert.
     *
     * @OA\Get(
     *     path="/alerts/create",
     *     summary="Show create alert form",
     *     tags={"Alerts"},
     *     @OA\Response(
     *         response=200,
     *         description="Form data for creating an alert"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $networkLogs = NetworkLog::select('id', 'file_name', 'upload_date')->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'filename' => $log->file_name,
                'upload_date' => $log->upload_date->format('Y-m-d H:i:s')
            ]);

        $mlModels = MLModel::all(['id', 'name', 'description']);

        return Inertia::render('Alerts/Create', [
            'networkLogs' => $networkLogs,
            'mlModels' => $mlModels,
        ]);
    }

    /**
     * Store a newly created alert.
     *
     * @OA\Post(
     *     path="/alerts",
     *     summary="Create a new alert",
     *     tags={"Alerts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"network_log_id","ml_model_id","attack_type","severity"},
     *             @OA\Property(property="network_log_id", type="integer"),
     *             @OA\Property(property="ml_model_id", type="integer"),
     *             @OA\Property(property="attack_type", type="string"),
     *             @OA\Property(property="severity", type="string", enum={"low","medium","high","critical"}),
     *             @OA\Property(property="source_ip", type="string", format="ipv4"),
     *             @OA\Property(property="destination_ip", type="string", format="ipv4"),
     *             @OA\Property(property="confidence_score", type="number", format="float"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Alert created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Log::info('Alert store attempt:', $request->all());

        try {
            $validated = $request->validate([
                'network_log_id' => 'required|exists:network_logs,id',
                'ml_model_id' => 'required|exists:ml_models,id',
                'attack_type' => 'required|string|max:255',
                'severity' => 'required|in:low,medium,high,critical',
                'source_ip' => 'nullable|ip',
                'destination_ip' => 'nullable|ip',
                'confidence_score' => 'nullable|numeric|between:0,1',
                'description' => 'nullable|string|max:1000',
            ]);

            Log::info('Validation passed:', $validated);

            $alert = Alert::create(array_merge($validated, [
                'detected_at' => now(),
                'status' => 'new'
            ]));

            Log::info('Alert created successfully:', ['id' => $alert->id]);

            // إرسال الإشعارات
            try {
                $this->sendAlertNotifications($alert);
                Log::info('Notifications sent successfully for alert ID: ' . $alert->id);
            } catch (\Exception $e) {
                Log::error('Failed to send notifications: ' . $e->getMessage());
            }

            return redirect()->route('alerts.index')
                ->with('success', 'Alert created successfully!');

        } catch (\Exception $e) {
            Log::error('Alert creation error: ' . $e->getMessage());
            return back()->with(['error' => 'Failed to create alert: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Send notifications to users based on alert severity.
     *
     * @param Alert $alert
     * @return void
     */
    private function sendAlertNotifications(Alert $alert)
    {
        $currentUser = Auth::user();
        if ($currentUser) {
            $currentUser->notify(new AlertCreatedNotification($alert));
        }

        if ($alert->severity === 'critical') {
            $adminsAndAnalysts = User::whereIn('role', ['Admin', 'Analyst'])->get();
            foreach ($adminsAndAnalysts as $user) {
                $user->notify(new CriticalThreatDetectedNotification($alert));
            }
        }

        $this->sendNotificationsByRole($alert, $currentUser);
    }

    /**
     * Send role-based notifications.
     *
     * @param Alert $alert
     * @param User|null $currentUser
     * @return void
     */
    private function sendNotificationsByRole(Alert $alert, $currentUser)
    {
        $notificationRules = [
            'critical' => ['Admin', 'Analyst'],
            'high' => ['Admin', 'Analyst'],
            'medium' => ['Admin', 'Analyst'],
            'low' => ['Admin']
        ];

        $targetRoles = $notificationRules[$alert->severity] ?? ['Admin'];
        $targetUsers = User::whereIn('role', $targetRoles)
            ->where('id', '!=', $currentUser?->id)
            ->get();

        foreach ($targetUsers as $user) {
            try {
                $user->notify(new AlertCreatedNotification($alert));
            } catch (\Exception $e) {
                Log::error("Failed to send notification to {$user->email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Display a specific alert.
     *
     * @OA\Get(
     *     path="/alerts/{id}",
     *     summary="Show alert details",
     *     tags={"Alerts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alert details returned"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alert not found"
     *     )
     * )
     *
     * @param Alert $alert
     * @return \Inertia\Response
     */
    public function show(Alert $alert)
    {
        $alert->load(['networkLog.user', 'mlModel']);
        return Inertia::render('Alerts/Show', ['alert' => $alert]);
    }

    /**
     * Show the form for editing an alert.
     *
     * @param Alert $alert
     * @return \Inertia\Response
     */
    public function edit(Alert $alert)
    {
        $networkLogs = NetworkLog::select('id', 'file_name', 'upload_date')->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'filename' => $log->file_name,
                'upload_date' => $log->upload_date->format('Y-m-d H:i:s')
            ]);

        $mlModels = MLModel::all(['id', 'name', 'description']);

        return Inertia::render('Alerts/Edit', [
            'alert' => $alert,
            'networkLogs' => $networkLogs,
            'mlModels' => $mlModels,
        ]);
    }

    /**
     * Update an existing alert.
     *
     * @param Request $request
     * @param Alert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Alert $alert)
    {
        try {
            $validated = $request->validate([
                'network_log_id' => 'required|exists:network_logs,id',
                'ml_model_id' => 'required|exists:ml_models,id',
                'attack_type' => 'required|string|max:255',
                'severity' => 'required|in:low,medium,high,critical',
                'status' => 'sometimes|in:new,investigating,resolved,false_positive',
                'source_ip' => 'nullable|ip',
                'destination_ip' => 'nullable|ip',
                'confidence_score' => 'nullable|numeric|between:0,1',
                'description' => 'nullable|string|max:1000',
            ]);

            $alert->update(array_merge($validated, [
                'status' => $validated['status'] ?? $alert->status
            ]));

            return redirect()->route('alerts.index')
                ->with('success', 'Alert updated successfully!');

        } catch (\Exception $e) {
            Log::error('Alert update failed: ' . $e->getMessage());
            return back()->with(['error' => 'Update failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Delete an alert.
     *
     * @OA\Delete(
     *     path="/alerts/{id}",
     *     summary="Delete an alert",
     *     tags={"Alerts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Alert deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alert not found"
     *     )
     * )
     *
     * @param Alert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Alert $alert)
    {
        try {
            $alert->delete();
            return redirect()->route('alerts.index')
                ->with('success', 'Alert deleted successfully!');
        } catch (\Exception $e) {
            return back()->with(['error' => 'Deletion failed: ' . $e->getMessage()]);
        }
    }
}
