<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResourceReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $reports = ResourceReport::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['user', 'reviewer', 'resource.resourceType'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'openCount' => ResourceReport::open()->count(),
        ]);
    }

    public function update(Request $request, ResourceReport $report)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'dismissed'])],
        ]);

        $report->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Report marked as '.$validated['status'].'.');
    }
}
