<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();

        $activeSessions = ExamSession::query()->where('status', true)->count();
        $totalSessions = ExamSession::query()->count();
        $totalParticipants = Participant::query()->count();
        $totalQuestions = Question::query()->count();

        $attemptsToday = ExamAttempt::query()
            ->whereDate('started_at', $today)
            ->count();

        $submittedToday = ExamAttempt::query()
            ->where('is_submitted', true)
            ->whereDate('finished_at', $today)
            ->count();

        $inProgress = ExamAttempt::query()
            ->where('is_submitted', false)
            ->whereNotNull('started_at')
            ->count();

        $recentSessions = ExamSession::query()
            ->withCount(['participants', 'attempts'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentAttempts = ExamAttempt::query()
            ->with(['participant:id,name,session_id', 'session:id,title'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'active_sessions' => $activeSessions,
                'total_sessions' => $totalSessions,
                'total_participants' => $totalParticipants,
                'total_questions' => $totalQuestions,
                'attempts_today' => $attemptsToday,
                'submitted_today' => $submittedToday,
                'in_progress' => $inProgress,
            ],
            'recentSessions' => $recentSessions,
            'recentAttempts' => $recentAttempts,
            'currentUser' => Auth::user(),
        ]);
    }
}
