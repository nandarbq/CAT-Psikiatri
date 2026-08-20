<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModuleRequest;
use App\Http\Requests\StoreModuleRequest;
use App\Models\Module;
use App\Models\Question;
use App\Support\Concerns\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleController extends Controller
{
    use HandlesPagination;

    public function index(Request $request): View
    {
        $query = Module::query()->withCount(['questions', 'activeQuestions', 'examSessions']);
        $keyword = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        if ($keyword !== '') {
            $query->where('name', 'like', '%'.$keyword.'%');
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (bool) ((int) $status));
        }

        return view('admin.modules.index', [
            'modules' => $this->paginateQuery($query->orderBy('name'), $request),
            'filters' => [
                'q' => $keyword,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.modules.form', [
            'module' => new Module,
            'isEdit' => false,
        ]);
    }

    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $rows = array_values($payload['questions']);

        $module = DB::transaction(function () use ($payload, $rows): Module {
            $module = Module::query()->create([
                'name' => trim((string) $payload['name']),
                'description' => blank($payload['description'] ?? null) ? null : trim((string) $payload['description']),
                'status' => (bool) $payload['status'],
            ]);

            foreach ($rows as $index => $row) {
                Question::query()->create([
                    'module_id' => $module->id,
                    'question' => trim((string) $row['question']),
                    'order_no' => $index + 1,
                    'is_active' => (bool) $row['is_active'],
                ]);
            }

            return $module;
        });

        return redirect()
            ->route('admin.modules.edit', $module)
            ->with('success', 'Modul berhasil dibuat dengan '.count($rows).' soal.');
    }

    public function edit(Module $module): View
    {
        $module->load(['questions' => fn ($query) => $query->orderBy('order_no')]);

        return view('admin.modules.form', [
            'module' => $module,
            'isEdit' => true,
        ]);
    }

    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $payload = $request->validated();
        $payload['description'] = blank($payload['description'] ?? null) ? null : trim((string) $payload['description']);

        $module->update($payload);

        return redirect()->route('admin.modules.edit', $module)->with('success', 'Modul berhasil diperbarui.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        if ($module->examSessions()->exists()) {
            return back()->withErrors(['module' => 'Modul tidak dapat dihapus karena sudah dipakai pada sesi tes.']);
        }

        try {
            DB::transaction(function () use ($module): void {
                // Lock the module row to avoid concurrent modifications.
                $locked = Module::query()->whereKey($module->id)->lockForUpdate()->first();

                if ($locked === null) {
                    throw new \RuntimeException('Modul tidak ditemukan saat akan dihapus.');
                }

                if ($locked->examSessions()->exists()) {
                    throw new \RuntimeException('Modul tidak dapat dihapus karena sudah dipakai pada sesi tes.');
                }

                $hasAnsweredQuestion = Question::query()
                    ->where('module_id', $locked->id)
                    ->whereHas('answers')
                    ->exists();

                if ($hasAnsweredQuestion) {
                    throw new \RuntimeException('Modul tidak dapat dihapus karena soal di dalamnya sudah dipakai jawaban peserta.');
                }

                // Safe to delete questions then module
                $locked->questions()->delete();
                $locked->delete();
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['module' => $e->getMessage() ?: 'Gagal menghapus modul.']);
        }

        return redirect()->route('admin.modules.index')->with('success', 'Modul berhasil dihapus.');
    }

    public function updateStatus(Request $request, Module $module): RedirectResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $payload['status'];
        $module->forceFill(['status' => $isActive])->save();

        $label = $isActive ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Modul {$module->name} berhasil {$label}.");
    }

    public function addQuestions(Request $request, Module $module): RedirectResponse
    {
        $payload = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:10000'],
            'questions.*.is_active' => ['required', 'boolean'],
        ]);

        $rows = array_values($payload['questions']);

        DB::transaction(function () use ($module, $rows): void {
            $nextOrderNo = ((int) Question::query()->where('module_id', $module->id)->max('order_no')) + 1;

            foreach ($rows as $row) {
                Question::query()->create([
                    'module_id' => $module->id,
                    'question' => trim((string) $row['question']),
                    'order_no' => $nextOrderNo++,
                    'is_active' => (bool) $row['is_active'],
                ]);
            }
        });

        return redirect()
            ->route('admin.modules.edit', $module)
            ->with('success', count($rows).' soal berhasil ditambahkan ke modul.');
    }
}
