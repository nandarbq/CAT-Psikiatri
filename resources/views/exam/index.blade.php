@include('exam.partials.runner', [
    'pageTitle' => 'Sesi Tes',
    'questions' => $questions,
    'answers' => $answers,
    'currentIndex' => $currentIndex,
    'saveUrl' => route('exam.save-answer'),
    'submitUrl' => route('exam.submit'),
    'deadlineAt' => $deadlineAt,
    'requiresLocation' => $requiresLocation,
    'sessionTitle' => $attempt->session->title,
    'metaChips' => [
        'Peserta: '.$attempt->participant->name,
        
    ],
])
