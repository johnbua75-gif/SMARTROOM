<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FacultyScheduleAssistantService
{
    public function respond(User $user, string $rawQuery, bool $useLlm = false): array
    {
        $query = trim($rawQuery);
        if ($query === '') {
            return [
                'intent' => 'empty',
                'answer' => 'Please type a schedule question, for example: "What is my schedule today?"',
                'items' => [],
            ];
        }

        $intent = $this->detectIntent($query);
        $name = $intent['name'] ?? 'unknown';

        switch ($name) {
            case 'today_schedule':
                return $this->answerToday($user);
            case 'tomorrow_schedule':
                return $this->answerTomorrow($user);
            case 'next_class_location':
                return $this->answerNextClass($user);
            case 'weekday_schedule':
                return $this->answerWeekday($user, (int) ($intent['weekday_iso'] ?? 1));
            case 'first_class':
                return $this->answerFirstClass($user);
            default:
                // If requested, or if environment indicates LLM is configured, use LLM fallback
                $hasKey = (bool) env('GROQ_API_KEY');
                if ($useLlm || $hasKey) {
                    $llm = $this->callLlm($user, $query);
                    if ($llm && !empty($llm['answer'])) {
                        return array_merge(['intent' => 'llm_fallback'], $llm);
                    }
                }

                return $this->answerUpcomingOverview($user);
        }
    }

    /**
     * Call external Groq AI as a fallback to answer more complex or ambiguous queries.
     * Requires GROQ_API_KEY in environment. This is a lightweight prompt with recent
     * upcoming schedule facts to help the model answer.
     *
     * @return array|null ['answer'=>string, 'items'=>array]
     */
    private function callLlm(User $user, string $query): ?array
    {
        $apiKey = env('GROQ_API_KEY');
        if (! $apiKey) return null;

        // Collect a few upcoming items as context
        $upcoming = $this->baseQuery($user)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(6)
            ->get()
            ->map(function (Schedule $s) {
                return sprintf("%s | %s | %s - %s", $s->course?->title ?? 'Untitled', optional($s->start_at)->toDateTimeString(), optional($s->end_at)->toDateTimeString(), $s->classroom?->name ?? 'Room N/A');
            })->values()->all();

        // Strongly request structured JSON output from the model to make parsing deterministic.
        $prompt = "You are a helpful schedule assistant. Use the instructor's upcoming classes to answer the question.\n\n";
        $prompt .= "Upcoming classes:\n" . implode("\n", $upcoming) . "\n\n";
        $prompt .= "Question: " . $query . "\n\n";
        $prompt .= "Respond ONLY with a JSON object with the following keys: \n";
        $prompt .= "{\n  \"answer\": string,\n  \"items\": [ { \"subject\": string, \"date\": string, \"time\": string, \"location\": string } ]\n}\n";
        $prompt .= "The 'answer' should be a concise human-friendly summary. The 'items' array may contain up to 3 upcoming classes with exact date/time/location. Return valid JSON only.";

        try {
            // Per-user daily quota check
            $limit = (int) env('GROQ_DAILY_LIMIT', 100);
            $key = 'llm_calls_user_'.($user->id ?? 'anon').'_'.now()->format('Ymd');
            $used = (int) Cache::get($key, 0);
            if ($used >= $limit) {
                return [
                    'answer' => 'LLM daily quota exceeded. Try again tomorrow or disable LLM.',
                    'items' => [],
                ];
            }

            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.ai/v1/completions', [
                'model' => env('GROQ_MODEL', 'groq-mini'),
                'prompt' => $prompt,
                'max_tokens' => 256,
                'temperature' => 0.1,
            ]);

            if (! $resp->ok()) return null;
            $data = $resp->json();

            // Attempt to parse common response shapes
            $text = $data['choices'][0]['text'] ?? $data['output'] ?? null;
            if (is_array($text)) {
                $text = implode("\n", $text);
            }

            if (! $text && isset($data['choices'][0]['message']['content'])) {
                $text = $data['choices'][0]['message']['content'];
            }

            if (! $text) return null;

            // Expect the model to return a JSON object. Try to find and decode it.
            $jsonText = null;
            if (is_string($text)) {
                if (preg_match('/\{.*\}/s', $text, $m)) {
                    $jsonText = $m[0];
                } else {
                    $jsonText = $text;
                }
            }

            $parsed = null;
            if ($jsonText) {
                $parsed = json_decode($jsonText, true);
            }

            $items = [];
            $answerText = null;
            if (is_array($parsed) && isset($parsed['answer'])) {
                $answerText = (string) $parsed['answer'];
                if (isset($parsed['items']) && is_array($parsed['items'])) {
                    $items = array_slice($parsed['items'], 0, 3);
                }
            } else {
                // Fallback: treat whole model text as answer
                $answerText = trim($text);
            }

            // Increment usage counter (set TTL to end of day if first increment)
            $seconds = now()->endOfDay()->diffInSeconds(now());
            if ($used === 0) {
                Cache::put($key, 1, $seconds);
            } else {
                Cache::increment($key);
            }

            return [
                'answer' => $answerText ?? '',
                'items' => $items,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    private function detectIntent(string $query): array
    {
        $q = strtolower($query);

        $weekdayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        foreach ($weekdayMap as $name => $iso) {
            if (str_contains($q, $name)) {
                return ['name' => 'weekday_schedule', 'weekday_iso' => $iso];
            }
        }

        if (str_contains($q, 'today')) {
            return ['name' => 'today_schedule'];
        }

        if (str_contains($q, 'tomorrow')) {
            return ['name' => 'tomorrow_schedule'];
        }

        if (str_contains($q, 'next class') || (str_contains($q, 'where') && str_contains($q, 'next'))) {
            return ['name' => 'next_class_location'];
        }

        if (str_contains($q, 'first class')) {
            return ['name' => 'first_class'];
        }

        return ['name' => 'upcoming_overview'];
    }

    private function answerToday(User $user): array
    {
        $today = now();
        $items = $this->getSchedulesForDate($user, $today);

        if ($items->isEmpty()) {
            return [
                'intent' => 'today_schedule',
                'answer' => 'You have no classes scheduled today.',
                'items' => [],
            ];
        }

        return [
            'intent' => 'today_schedule',
            'answer' => 'Here is your schedule for today ('.$today->format('l, M d').').',
            'items' => $this->formatItems($items),
        ];
    }

    private function answerTomorrow(User $user): array
    {
        $tomorrow = now()->addDay();
        $items = $this->getSchedulesForDate($user, $tomorrow);

        if ($items->isEmpty()) {
            return [
                'intent' => 'tomorrow_schedule',
                'answer' => 'You have no classes scheduled tomorrow.',
                'items' => [],
            ];
        }

        return [
            'intent' => 'tomorrow_schedule',
            'answer' => 'You have '.($items->count()).' class'.($items->count() > 1 ? 'es' : '').' tomorrow ('.$tomorrow->format('l, M d').').',
            'items' => $this->formatItems($items),
        ];
    }

    private function answerNextClass(User $user): array
    {
        $nextClass = $this->baseQuery($user)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->first();

        if (! $nextClass) {
            return [
                'intent' => 'next_class_location',
                'answer' => 'You have no upcoming classes in the schedule.',
                'items' => [],
            ];
        }

        $start = $nextClass->start_at;
        $location = $this->formatLocation($nextClass);

        return [
            'intent' => 'next_class_location',
            'answer' => 'Your next class is '.($nextClass->course?->title ?? 'Untitled Subject').' at '.$location.' on '.$start?->format('l, M d \a\t g:i A').'.',
            'items' => $this->formatItems(collect([$nextClass])),
        ];
    }

    private function answerWeekday(User $user, int $isoDay): array
    {
        $dayOfWeek = $isoDay % 7;

        $rows = $this->baseQuery($user)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_at')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'intent' => 'weekday_schedule',
                'answer' => 'No classes found for '.$this->weekdayName($isoDay).'.',
                'items' => [],
            ];
        }

        $deduped = $rows
            ->groupBy(function (Schedule $schedule): string {
                $start = $schedule->start_at?->format('H:i') ?? '';
                $end = $schedule->end_at?->format('H:i') ?? '';
                $courseId = (string) ($schedule->course_id ?? '0');
                $roomId = (string) ($schedule->classroom_id ?? '0');

                return $courseId.'|'.$roomId.'|'.$start.'|'.$end;
            })
            ->map(fn (Collection $group): Schedule => $group->first())
            ->sortBy('start_at')
            ->values();

        return [
            'intent' => 'weekday_schedule',
            'answer' => 'Here is your '.$this->weekdayName($isoDay).' schedule.',
            'items' => $this->formatItems($deduped),
        ];
    }

    private function answerFirstClass(User $user): array
    {
        $todayStart = now()->copy()->startOfDay();
        $upcoming = $this->baseQuery($user)
            ->where('start_at', '>=', $todayStart)
            ->orderBy('start_at')
            ->first();

        if (! $upcoming) {
            return [
                'intent' => 'first_class',
                'answer' => 'No class found from today onward.',
                'items' => [],
            ];
        }

        $start = $upcoming->start_at;

        return [
            'intent' => 'first_class',
            'answer' => 'Your first upcoming class is '.$start?->format('l, M d \a\t g:i A').'.',
            'items' => $this->formatItems(collect([$upcoming])),
        ];
    }

    private function answerUpcomingOverview(User $user): array
    {
        $rows = $this->baseQuery($user)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'intent' => 'upcoming_overview',
                'answer' => 'I could not find upcoming classes. Try asking: "What is my schedule today?"',
                'items' => [],
            ];
        }

        return [
            'intent' => 'upcoming_overview',
            'answer' => 'Here are your next classes.',
            'items' => $this->formatItems($rows),
        ];
    }

    private function getSchedulesForDate(User $user, Carbon $date): Collection
    {
        return $this->baseQuery($user)
            ->whereDate('start_at', $date->toDateString())
            ->orderBy('start_at')
            ->get();
    }

    private function baseQuery(User $user)
    {
        return Schedule::query()
            ->with(['classroom', 'course'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->whereNotIn('status', ['cancelled']);
    }

    private function formatItems(Collection $schedules): array
    {
        return $schedules->map(function (Schedule $schedule): array {
            $start = $schedule->start_at;
            $end = $schedule->end_at;

            $time = $start?->format('g:i A') ?? '-';
            if ($start && $end) {
                $time = $start->format('g:i A').' - '.$end->format('g:i A');
            }

            return [
                'subject' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                'code' => (string) ($schedule->course?->code ?? 'N/A'),
                'date' => $start?->format('D, M d, Y') ?? '-',
                'time' => $time,
                'location' => $this->formatLocation($schedule),
                'students' => (int) ($schedule->enrolled ?? 0),
                'status' => (string) ($schedule->status ?? 'scheduled'),
            ];
        })->values()->all();
    }

    private function formatLocation(Schedule $schedule): string
    {
        return trim(((string) ($schedule->classroom?->name ?? 'Room N/A')).', '.((string) ($schedule->classroom?->building ?? '')),
            ', ');
    }

    private function weekdayName(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Sunday',
        };
    }
}
