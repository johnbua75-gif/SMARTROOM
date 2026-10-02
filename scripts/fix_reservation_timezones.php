    <?php
// Fix reservations that were saved with local (Asia/Manila) times but stored as UTC.
// Heuristic: if start_at - created_at >= 6 hours, assume it was stored as local time and shift -8 hours.

require __DIR__.'/../vendor/autoload.php';

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$now = Carbon::now('UTC');
$shiftHours = 8;

$reservations = Reservation::where('start_at', '>=', $now->subDays(1))
    ->orderBy('start_at')
    ->get();

$fixed = 0;
$checked = 0;

foreach ($reservations as $r) {
    $checked++;
    $created = Carbon::parse($r->created_at)->setTimezone('UTC');
    $start = Carbon::parse($r->start_at)->setTimezone('UTC');
    $diffHours = ($start->getTimestamp() - $created->getTimestamp()) / 3600;
    echo "ID={$r->id} created={$created->toDateTimeString()} start={$start->toDateTimeString()} diffHours={$diffHours}\n";

    if ($diffHours >= 6) {
        $oldStart = $r->start_at;
        $oldEnd = $r->end_at;
        $r->start_at = Carbon::parse($r->start_at)->subHours($shiftHours)->toDateTimeString();
        $r->end_at = Carbon::parse($r->end_at)->subHours($shiftHours)->toDateTimeString();
        $r->save();
        echo "Fixed reservation id={$r->id}: start {$oldStart} -> {$r->start_at}, end {$oldEnd} -> {$r->end_at}\n";
        $fixed++;
    }
}

echo "Checked: {$checked}, Fixed: {$fixed}\n";

return 0;
