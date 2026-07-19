<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterCategoryRequest;
use App\Models\MasterCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = MasterCategory::query()->withCount('questions');
        $keyword = trim((string) $request->query('q', ''));
        $isActive = $request->query('is_active');

        if ($keyword !== '') {
            $query->where('name', 'like', '%'.$keyword.'%');
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) ((int) $isActive));
        }

        return view('admin.categories.index', [
            'categories' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'filters' => [
                'q' => $keyword,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new MasterCategory(),
            'isEdit' => false,
        ]);
    }

    public function store(MasterCategoryRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['slug'] = $this->generateUniqueSlug((string) $payload['name']);

        MasterCategory::query()->create($payload);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(MasterCategory $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'isEdit' => true,
        ]);
    }

    public function update(MasterCategoryRequest $request, MasterCategory $category): RedirectResponse
    {
        $payload = $request->validated();

        if ((string) $category->name !== (string) $payload['name']) {
            $payload['slug'] = $this->generateUniqueSlug((string) $payload['name'], $category->id);
        }

        $category->update($payload);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(MasterCategory $category): RedirectResponse
    {
        if ($category->questions()->exists()) {
            return back()->withErrors(['category' => 'Kategori tidak dapat dihapus karena masih dipakai oleh soal.']);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:master_categories,id'],
            'action' => ['required', 'in:activate,deactivate,delete'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $payload['ids'])));
        $action = (string) $payload['action'];

        if ($action === 'activate') {
            MasterCategory::query()->whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' kategori diaktifkan.');
        }

        if ($action === 'deactivate') {
            MasterCategory::query()->whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' kategori dinonaktifkan.');
        }

        $categories = MasterCategory::query()->whereIn('id', $ids)->get();
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($categories as $category) {
            if ($category->questions()->exists()) {
                $skippedCount++;
                continue;
            }

            $category->delete();
            $deletedCount++;
        }

        $message = "Aksi massal selesai: {$deletedCount} kategori dihapus.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} kategori dilewati karena masih dipakai soal.";
        }

        return back()->with('success', $message);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            MasterCategory::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
