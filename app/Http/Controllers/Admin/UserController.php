<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->where('role', User::ROLE_ADMIN);
        $keyword = trim((string) $request->query('q', ''));

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('username', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        return view('admin.users.index', [
            'users' => $query->latest('id')->paginate(15)->withQueryString(),
            'filters' => [
                'q' => $keyword,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(),
            'isEdit' => false,
            'roles' => $this->roles(),
        ]);
    }

    public function store(AdminUserRequest $request): RedirectResponse
    {
        User::query()->create($this->normalizePayload($request->validated()));

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $this->ensureManageableUser($user);

        return view('admin.users.form', [
            'user' => $user,
            'isEdit' => true,
            'roles' => $this->roles(),
        ]);
    }

    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageableUser($user);

        $payload = $this->normalizePayload($request->validated());

        if ($this->adminCount() <= 1 && (int) $user->id === (int) $user->id && (string) $payload['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->withErrors(['user' => 'Perubahan dibatalkan: sistem harus menyisakan minimal satu akun admin.']);
        }

        if (blank($payload['password'] ?? null)) {
            unset($payload['password']);
        }

        $user->update($payload);

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureManageableUser($user);

        if ((int) Auth::id() === (int) $user->id) {
            return back()->withErrors(['user' => 'Akun yang sedang dipakai login tidak boleh dihapus.']);
        }

        if ($this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'Penghapusan dibatalkan: sistem harus menyisakan minimal satu akun admin.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil dihapus.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'in:delete'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $payload['ids'])));

        $users = User::query()
            ->whereIn('id', $ids)
            ->where('role', User::ROLE_ADMIN)
            ->get();

        if ($users->isEmpty()) {
            return back()->withErrors(['user' => 'Aksi massal dibatalkan: tidak ada user admin yang valid pada pilihan.']);
        }

        $deletedCount = 0;
        $skippedLastAdmin = 0;
        $skippedOwnAccount = 0;
        $skippedNonInternal = max(0, count($ids) - $users->count());
        $adminCount = $this->adminCount();
        $currentUserId = (int) Auth::id();

        foreach ($users as $user) {
            if ((int) $user->id === $currentUserId) {
                $skippedOwnAccount++;
                continue;
            }

            if ($adminCount <= 1) {
                $skippedLastAdmin++;
                continue;
            }

            $adminCount--;
            $user->delete();
            $deletedCount++;
        }

        return back()->with('success', $this->buildBulkMessage(
            "Aksi massal selesai: {$deletedCount} user dihapus.",
            $skippedNonInternal,
            $skippedOwnAccount,
            $skippedLastAdmin,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            User::ROLE_ADMIN => 'Admin',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $payload['role'] = User::ROLE_ADMIN;
        $payload['name'] = trim((string) ($payload['name'] ?? ''));
        $payload['username'] = strtolower(trim((string) ($payload['username'] ?? '')));
        $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));

        return $payload;
    }

    private function adminCount(): int
    {
        return User::query()->where('role', User::ROLE_ADMIN)->count();
    }

    private function buildBulkMessage(
        string $baseMessage,
        int $skippedNonInternal,
        int $skippedOwnAccount,
        int $skippedLastAdmin,
    ): string {
        $message = $baseMessage;

        if ($skippedOwnAccount > 0) {
            $message .= " {$skippedOwnAccount} akun dilewati karena merupakan akun login saat ini.";
        }
        if ($skippedLastAdmin > 0) {
            $message .= " {$skippedLastAdmin} akun admin dilewati agar minimal satu admin tetap tersedia.";
        }
        if ($skippedNonInternal > 0) {
            $message .= " {$skippedNonInternal} data dilewati karena bukan user admin.";
        }

        return $message;
    }

    private function ensureManageableUser(User $user): void
    {
        if ($user->role !== User::ROLE_ADMIN) {
            abort(404);
        }
    }
}
