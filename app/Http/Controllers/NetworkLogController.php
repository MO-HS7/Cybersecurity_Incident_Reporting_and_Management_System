<?php

namespace App\Http\Controllers;

use App\Models\NetworkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Class NetworkLogController
 *
 * Handles CRUD operations for network log files,
 * including upload, view, update, and deletion.
 *
 * @package App\Http\Controllers
 */
class NetworkLogController extends Controller
{
    /**
     * Display a listing of network logs.
     *
     * @OA\Get(
     *     path="/network-logs",
     *     summary="List all network logs",
     *     tags={"NetworkLogs"},
     *     @OA\Response(
     *         response=200,
     *         description="Network logs retrieved successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $networkLogs = NetworkLog::with('user')
            ->when(Auth::user()->role !== 'Admin', function ($query) {
                return $query->where('user_id', Auth::id());
            })
            ->latest('upload_date')
            ->paginate(10);

        return Inertia::render('NetworkLogs/Index', [
            'networkLogs' => $networkLogs
        ]);
    }

    /**
     * Show the form for creating a new network log.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('NetworkLogs/Create');
    }

    /**
     * Store a newly uploaded network log.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            \Log::info('NetworkLog upload started', $request->all());

            $request->validate([
                'file' => 'required|file|mimes:csv,txt,pcap|max:10240', // 10MB max
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();

            $filePath = $file->storeAs('network_logs', $fileName, 'public');

            \Log::info('File stored at: ' . $filePath);

            $networkLog = NetworkLog::create([
                'user_id'     => Auth::id(),
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $filePath,
                'upload_date' => now(),
                'status'      => 'pending',
            ]);

            \Log::info('NetworkLog created successfully', ['id' => $networkLog->id]);

            // Trigger optional ML processing
            try {
                \Artisan::call('ids:process-log', ['log_id' => $networkLog->id]);
            } catch (\Exception $e) {
                \Log::warning('ML processing failed, but upload succeeded', [
                    'log_id' => $networkLog->id,
                    'error'  => $e->getMessage(),
                ]);
            }

            return redirect()->route('network-logs.index')
                ->with('success', 'Network log uploaded successfully!');
        } catch (\Exception $e) {
            \Log::error('NetworkLog upload failed: ' . $e->getMessage());
            return back()->with(['file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified network log.
     *
     * @param NetworkLog $networkLog
     * @return \Inertia\Response
     */
    public function show(NetworkLog $networkLog)
    {
        $networkLog->load(['user', 'alerts.mlModel']);

        return Inertia::render('NetworkLogs/Show', [
            'networkLog' => $networkLog
        ]);
    }

    /**
     * Show the form for editing the specified network log.
     *
     * @param NetworkLog $networkLog
     * @return \Inertia\Response
     */
    public function edit(NetworkLog $networkLog)
    {
        return Inertia::render('NetworkLogs/Edit', [
            'networkLog' => $networkLog
        ]);
    }

    /**
     * Update the specified network log status.
     *
     * @param Request $request
     * @param NetworkLog $networkLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, NetworkLog $networkLog)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,processed,failed',
        ]);

        $networkLog->update($request->only('status'));

        return redirect()->route('network-logs.index')
            ->with('success', 'Network log updated successfully.');
    }

    /**
     * Remove the specified network log and its file from storage.
     *
     * @param NetworkLog $networkLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(NetworkLog $networkLog)
    {
        // Delete the file from storage
        if ($networkLog->file_path && Storage::disk('public')->exists($networkLog->file_path)) {
            Storage::disk('public')->delete($networkLog->file_path);
        }

        $networkLog->delete();

        return redirect()->route('network-logs.index')
            ->with('success', 'Network log deleted successfully.');
    }
}
