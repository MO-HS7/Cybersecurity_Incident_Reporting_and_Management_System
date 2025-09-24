<?php

namespace App\Http\Controllers;

use App\Models\MLModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Class MLModelController
 *
 * Handles CRUD operations for ML Models, including listing, creation, editing, and deletion.
 *
 * @package App\Http\Controllers
 */
class MLModelController extends Controller
{
    /**
     * Display a listing of ML Models.
     *
     * @OA\Get(
     *     path="/ml-models",
     *     summary="List all ML Models",
     *     tags={"MLModels"},
     *     @OA\Response(
     *         response=200,
     *         description="ML Models retrieved successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $mlModels = MLModel::latest()->paginate(10);

        return Inertia::render('MLModels/Index', [
            'mlModels' => $mlModels
        ]);
    }

    /**
     * Show the form for creating a new ML Model.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('MLModels/Create');
    }

    /**
     * Store a newly created ML Model in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ml_models,name',
                'description' => 'nullable|string|max:1000',
            ]);

            $mlModel = MLModel::create($validated);

            Log::info('MLModel created successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model created successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to create MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create ML Model: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Display the specified ML Model.
     *
     * @param MLModel $mlModel
     * @return \Inertia\Response
     */
    public function show(MLModel $mlModel)
    {
        return Inertia::render('MLModels/Show', [
            'mlModel' => $mlModel
        ]);
    }

    /**
     * Show the form for editing the specified ML Model.
     *
     * @param MLModel $mlModel
     * @return \Inertia\Response
     */
    public function edit(MLModel $mlModel)
    {
        return Inertia::render('MLModels/Edit', [
            'mlModel' => $mlModel
        ]);
    }

    /**
     * Update the specified ML Model in storage.
     *
     * @param Request $request
     * @param MLModel $mlModel
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, MLModel $mlModel)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ml_models,name,' . $mlModel->id,
                'description' => 'nullable|string|max:1000',
            ]);

            $mlModel->update($validated);

            Log::info('MLModel updated successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update ML Model: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Remove the specified ML Model from storage.
     *
     * @param MLModel $mlModel
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MLModel $mlModel)
    {
        try {
            $mlModel->delete();
            Log::info('MLModel deleted successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete ML Model: ' . $e->getMessage()]);
        }
    }
}
