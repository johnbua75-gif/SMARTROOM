@include('frontend.faculty._attendance-session-content', [
    'session' => $session ?? [],
    'records' => $records ?? collect([]),
    'stats' => $stats ?? ['total' => 0, 'present' => 0, 'absent' => 0, 'excused' => 0, 'rate' => 0],
])
