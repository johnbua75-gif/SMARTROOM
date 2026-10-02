<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;
use Throwable;

class ScheduleImportService
{
    /**
     * @return array<int, array<string, string|null>>
     */
    public function parseUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Invalid uploaded file path.');
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->parseCsv($path);
        }

        if ($extension === 'xlsx') {
            return $this->parseXlsx($path);
        }

        if ($extension === 'xls') {
            return $this->parseXls($path);
        }

        throw new \RuntimeException('Unsupported file format.');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $rows = [];
        $header = null;

        try {
            while (($data = fgetcsv($handle)) !== false) {
                if ($data === [null] || $data === []) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->normalizeHeaderRow($data);

                    continue;
                }

                $rows[] = $this->combineRow($header, $data);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseXlsx(string $path): array
    {
        $xlsx = SimpleXLSX::parse($path);
        if (! $xlsx) {
            throw new \RuntimeException('Unable to parse XLSX file.');
        }

        return $this->parseGridRows($xlsx->rows());
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseXls(string $path): array
    {
        $xls = SimpleXLS::parseFile($path);
        if (! $xls) {
            throw new \RuntimeException('Unable to parse XLS file.');
        }

        return $this->parseGridRows($xls->rows());
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, string|null>>
     */
    private function parseGridRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $header = null;
        $output = [];

        foreach ($rows as $row) {
            if ($header === null) {
                $header = $this->normalizeHeaderRow($row);

                continue;
            }

            $output[] = $this->combineRow($header, $row);
        }

        return $output;
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    private function normalizeHeaderRow(array $headerRow): array
    {
        $normalized = [];

        foreach ($headerRow as $index => $value) {
            $text = trim((string) $value);
            if ((int) $index === 0) {
                $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
            }

            $key = strtolower($text);
            $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';
            $key = trim($key, '_');
            $normalized[] = $key !== '' ? $key : 'column_'.($index + 1);
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, mixed>  $data
     * @return array<string, string|null>
     */
    private function combineRow(array $header, array $data): array
    {
        $result = [];

        foreach ($header as $index => $key) {
            $value = $data[$index] ?? null;
            $stringValue = $value === null ? null : trim((string) $value);
            $result[$key] = $stringValue === '' ? null : $stringValue;
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string|null>>  $rows
     * @param  array<string, mixed>  $defaults
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>}
     */
    public function prepareImportRows(array $rows, array $defaults, RoomAvailabilityService $availabilityService, ScheduleService $scheduleService): array
    {
        $classroomsById = Classroom::query()->get()->keyBy('id');
        $classroomsByName = Classroom::query()->get()->keyBy(function (Classroom $classroom): string {
            return strtolower(trim($classroom->name));
        });

        $courses = Course::query()
            ->with('instructor')
            ->whereHas('instructor', function ($scope) use ($scheduleService): void {
                $scheduleService->applyItDepartmentScope($scope);
            })
            ->get();

        $coursesById = $courses->keyBy('id');
        $coursesByCode = $courses->keyBy(function (Course $course): string {
            return strtolower(trim((string) $course->code));
        });
        $coursesByTitle = $courses->groupBy(function (Course $course): string {
            return $this->normalizeLookup((string) $course->title);
        });
        $coursesByInstructorName = $courses->groupBy(function (Course $course): string {
            return $this->normalizeLookup((string) $course->instructor?->name);
        });
        $coursesByInstructorEmail = $courses->groupBy(function (Course $course): string {
            return strtolower(trim((string) $course->instructor?->email));
        });

        $preparedRows = [];
        $errors = [];

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $rowErrors = [];

            $classroom = null;
            $roomId = $this->extractNullableInt($rawRow['classroom_id'] ?? $rawRow['room_id'] ?? null)
                ?? $this->extractNullableInt($defaults['default_classroom_id'] ?? null);

            if ($roomId !== null) {
                $classroom = $classroomsById->get($roomId);
            }

            if (! $classroom) {
                $roomName = trim((string) ($rawRow['classroom'] ?? $rawRow['room'] ?? $rawRow['classroom_name'] ?? $rawRow['room_name'] ?? ''));
                if ($roomName !== '') {
                    $classroom = $classroomsByName->get(strtolower($roomName));
                }
            }

            if (! $classroom) {
                $rowErrors[] = 'Classroom is required or not found.';
            }

            $course = null;
            $courseId = $this->extractNullableInt($rawRow['course_id'] ?? null);
            $instructorName = $this->normalizeLookup((string) ($rawRow['instructor'] ?? $rawRow['instructor_name'] ?? $rawRow['faculty'] ?? $rawRow['faculty_name'] ?? $rawRow['teacher'] ?? ''));
            $instructorEmail = strtolower(trim((string) ($rawRow['instructor_email'] ?? $rawRow['faculty_email'] ?? $rawRow['teacher_email'] ?? '')));

            if ($courseId !== null) {
                $course = $coursesById->get($courseId);
            }

            if (! $course) {
                $courseCode = strtolower(trim((string) ($rawRow['course_code'] ?? $rawRow['code'] ?? $rawRow['course'] ?? '')));
                if ($courseCode !== '') {
                    $course = $coursesByCode->get($courseCode);
                }
            }

            if (! $course) {
                $courseTitle = $this->normalizeLookup((string) ($rawRow['course_title'] ?? $rawRow['subject'] ?? $rawRow['title'] ?? $rawRow['course_name'] ?? ''));

                if ($courseTitle !== '') {
                    /** @var Collection<int, Course> $titleCandidates */
                    $titleCandidates = collect($coursesByTitle->get($courseTitle, collect()));

                    if ($instructorName !== '' || $instructorEmail !== '') {
                        $titleCandidates = $titleCandidates->filter(function (Course $candidate) use ($instructorName, $instructorEmail): bool {
                            $candidateName = $this->normalizeLookup((string) $candidate->instructor?->name);
                            $candidateEmail = strtolower(trim((string) $candidate->instructor?->email));

                            if ($instructorEmail !== '' && $candidateEmail === $instructorEmail) {
                                return true;
                            }

                            if ($instructorName !== '' && $candidateName === $instructorName) {
                                return true;
                            }

                            return false;
                        })->values();
                    }

                    if ($titleCandidates->count() === 1) {
                        $course = $titleCandidates->first();
                    } elseif ($titleCandidates->count() > 1) {
                        $rowErrors[] = 'Multiple courses matched this subject and instructor. Use course_code or course_id in import row.';
                    }
                }
            }

            if (! $course) {
                /** @var Collection<int, Course> $instructorCandidates */
                $instructorCandidates = collect();

                if ($instructorEmail !== '') {
                    $instructorCandidates = collect($coursesByInstructorEmail->get($instructorEmail, collect()));
                }

                if ($instructorName !== '') {
                    if ($instructorCandidates->isEmpty()) {
                        $instructorCandidates = collect($coursesByInstructorName->get($instructorName, collect()));
                    } else {
                        $instructorCandidates = $instructorCandidates
                            ->filter(function (Course $candidate) use ($instructorName): bool {
                                return $this->normalizeLookup((string) $candidate->instructor?->name) === $instructorName;
                            })
                            ->values();
                    }
                }

                if ($instructorCandidates->isNotEmpty()) {
                    $course = $instructorCandidates
                        ->sortBy(function (Course $candidate): string {
                            return strtolower((string) ($candidate->code ?? ''));
                        })
                        ->first();
                }
            }

            $startValue = trim((string) ($rawRow['start_at'] ?? $rawRow['start'] ?? $rawRow['start_datetime'] ?? ''));
            $endValue = trim((string) ($rawRow['end_at'] ?? $rawRow['end'] ?? $rawRow['end_datetime'] ?? ''));

            $startAt = null;
            $endAt = null;
            if ($startValue === '') {
                $rowErrors[] = 'Start datetime is required.';
            } else {
                try {
                    $startAt = Carbon::parse($startValue);
                } catch (Throwable $exception) {
                    $rowErrors[] = 'Start datetime format is invalid.';
                }
            }

            if ($endValue === '') {
                $rowErrors[] = 'End datetime is required.';
            } else {
                try {
                    $endAt = Carbon::parse($endValue);
                } catch (Throwable $exception) {
                    $rowErrors[] = 'End datetime format is invalid.';
                }
            }

            if ($startAt && $endAt && $endAt->lte($startAt)) {
                $rowErrors[] = 'End datetime must be after start datetime.';
            }

            $status = strtolower(trim((string) ($rawRow['status'] ?? 'scheduled')));
            if (! in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled'], true)) {
                $status = 'scheduled';
            }

            $enrolled = $this->extractNullableInt($rawRow['enrolled'] ?? null) ?? 0;

            $blockSection = trim((string) ($rawRow['block_section'] ?? $rawRow['block'] ?? $rawRow['section'] ?? ''));

            if ($rowErrors === [] && $classroom && $startAt && $endAt) {
                $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
                    (int) $classroom->id,
                    $startAt,
                    $endAt,
                    null,
                    false
                );

                if ($scheduleConflict['has_conflict']) {
                    $rowErrors[] = 'Room is occupied by official schedule at selected time.';
                }

                $reservationConflict = $availabilityService->checkReservationConflict(
                    (int) $classroom->id,
                    $startAt,
                    $endAt,
                    null,
                    false
                );

                if ($reservationConflict['has_conflict']) {
                    $rowErrors[] = 'Room is already reserved at selected time.';
                }
            }

            $payload = [
                'classroom_id' => $classroom?->id,
                'course_id' => $course?->id,
                'block_section' => $blockSection !== '' ? mb_substr($blockSection, 0, 64) : null,
                'start_at' => $startAt?->toDateTimeString(),
                'end_at' => $endAt?->toDateTimeString(),
                'status' => $status,
                'day_of_week' => $startAt?->dayOfWeek,
                'enrolled' => max(0, $enrolled),
            ];

            $preparedRows[] = [
                'row_number' => $rowNumber,
                'room' => $classroom?->name,
                'course' => $course?->code,
                'instructor' => $course?->instructor?->name
                    ?? trim((string) ($rawRow['instructor'] ?? $rawRow['instructor_name'] ?? $rawRow['faculty'] ?? $rawRow['faculty_name'] ?? $rawRow['teacher'] ?? '')),
                'subject' => $course?->title,
                'start_at' => $payload['start_at'],
                'end_at' => $payload['end_at'],
                'status' => $status,
                'enrolled' => $payload['enrolled'],
                'is_valid' => count($rowErrors) === 0,
                'errors' => $rowErrors,
                'payload' => $payload,
            ];

            if ($rowErrors !== []) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'messages' => $rowErrors,
                ];
            }
        }

        return [
            'rows' => $preparedRows,
            'errors' => $errors,
        ];
    }

    private function extractNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

    private function normalizeLookup(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
