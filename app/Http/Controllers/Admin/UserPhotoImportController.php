<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadUserPhotoImportRequest;
use App\Jobs\ProcessUserPhotoImportBatch;
use App\Models\UserPhotoImportBatch;
use App\Services\UserPhotoImportReportService;
use App\Services\UserPhotoImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserPhotoImportController extends Controller
{
    public function __construct(
        protected UserPhotoImportService $importService,
        protected UserPhotoImportReportService $reportService
    ) {}

    /**
     * Display the bulk photo import upload form and list of recent batches.
     */
    public function index(): View
    {
        $recentBatches = UserPhotoImportBatch::with('uploader')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.users.photo-import.index', [
            'recentBatches' => $recentBatches,
        ]);
    }

    /**
     * Handle ZIP upload, inspect files, build preview, and redirect to preview page.
     */
    public function store(UploadUserPhotoImportRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $batch = $this->importService->uploadAndInspect(
                $request->file('file'),
                $validated['matching_type'],
                $validated['existing_photo_policy'],
                auth()->id()
            );

            return redirect()->route('admin.users.photo-import.show', $batch)
                ->with('status', 'File ZIP berhasil diinspeksi. Silakan tinjau ringkasan preview sebelum konfirmasi.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => 'Gagal memproses file ZIP: '.$e->getMessage()]);
        }
    }

    /**
     * Display batch preview summary, filtered items, and action controls.
     */
    public function show(Request $request, UserPhotoImportBatch $batch): View
    {
        $this->ensureAuthorizedToViewBatch($batch);

        // Fallback: If batch is stuck in importing status without active progress, process small batches synchronously
        $totalToImport = $batch->ready_new_count + $batch->ready_replace_count;
        if ($batch->status === 'importing' && ($totalToImport <= 100 || config('queue.default') === 'sync')) {
            ProcessUserPhotoImportBatch::dispatchSync($batch);
            $batch->refresh();
        }

        $query = $batch->items()->with('user');

        if ($statusFilter = $request->input('status')) {
            $query->where('status', $statusFilter);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(25)->withQueryString();

        return view('admin.users.photo-import.show', [
            'batch' => $batch,
            'items' => $items,
            'currentStatus' => $statusFilter,
            'search' => $search,
        ]);
    }

    /**
     * Confirm import and start background/sync batch processing.
     */
    public function confirm(UserPhotoImportBatch $batch): RedirectResponse
    {
        $this->ensureAuthorizedToViewBatch($batch);

        if (! $batch->isCommittable()) {
            return redirect()->back()->with('error', 'Status batch tidak memungkinkan untuk dikonfirmasi.');
        }

        $batch->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);

        $totalToProcess = $batch->ready_new_count + $batch->ready_replace_count;

        // Process small batches (<= 100 photos) synchronously for immediate response and progress,
        // or dispatch queue job for large batches.
        if ($totalToProcess <= 100 || config('queue.default') === 'sync') {
            ProcessUserPhotoImportBatch::dispatchSync($batch);
        } else {
            ProcessUserPhotoImportBatch::dispatch($batch);
        }

        return redirect()->route('admin.users.photo-import.show', $batch)
            ->with('status', 'Proses import foto profil telah diproses.');
    }

    /**
     * Cancel an import batch and delete temporary staging files.
     */
    public function cancel(UserPhotoImportBatch $batch): RedirectResponse
    {
        $this->ensureAuthorizedToViewBatch($batch);

        try {
            $this->importService->cancelImport($batch);

            return redirect()->route('admin.users.photo-import.index')
                ->with('status', 'Batch import foto berhasil dibatalkan.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get JSON progress status for live UI polling.
     */
    public function progress(UserPhotoImportBatch $batch): JsonResponse
    {
        $this->ensureAuthorizedToViewBatch($batch);

        $batch->refresh();
        $totalToImport = $batch->ready_new_count + $batch->ready_replace_count;

        return response()->json([
            'status' => $batch->status,
            'status_label' => $batch->status_label,
            'total_to_import' => $totalToImport,
            'processed_count' => $batch->processed_count,
            'failed_count' => $batch->failed_count,
            'remaining_count' => max(0, $totalToImport - $batch->processed_count),
            'progress_percent' => $totalToImport > 0 ? (int) round(($batch->processed_count / $totalToImport) * 100) : 0,
            'is_completed' => $batch->isTerminal(),
        ]);
    }

    /**
     * Download Excel report for the batch.
     */
    public function downloadReport(UserPhotoImportBatch $batch): BinaryFileResponse
    {
        $this->ensureAuthorizedToViewBatch($batch);

        return $this->reportService->downloadReport($batch);
    }

    /**
     * Ensure the current user has access to view/modify the batch.
     */
    protected function ensureAuthorizedToViewBatch(UserPhotoImportBatch $batch): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('superadmin')) {
            return;
        }

        if (! $user->hasPermissionTo('users.bulk-import-photos')) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola import foto user.');
        }
    }
}
