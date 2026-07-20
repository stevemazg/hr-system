<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Illuminate\Support\Str;

class CalendarController extends Controller {

    // Regenerate token
    public function regenerate(Request $request) {
        $user = $request->user();
        $user->update(['calendar_token' => Str::random(48)]);
        return back()->with('success', 'Calendar link regenerated. Update your subscription URL in Outlook.');
    }

    // Public ICS feed — no auth, token in URL
    public function feed(string $token) {
        $user = User::where('calendar_token', $token)->firstOrFail();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//HR System//hr.pro-business.co.uk//EN',
            'X-WR-CALNAME:' . $this->esc($user->full_name . ' — Leave'),
            'X-WR-CALDESC:Leave calendar from HR System',
            'X-WR-TIMEZONE:Europe/London',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        // Approved leave requests (12 months back, 18 months forward)
        $requests = LeaveRequest::with('leaveType')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('end_date', '>=', now()->subYear())
            ->where('start_date', '<=', now()->addMonths(18))
            ->get();

        foreach ($requests as $req) {
            $dtStart = $req->start_date->format('Ymd');
            $dtEnd   = $req->end_date->copy()->addDay()->format('Ymd'); // ICS end is exclusive
            $uid     = 'leave-' . $req->id . '@hr.pro-business.co.uk';
            $summary = $req->leaveType->name;
            if ($req->half_day) $summary .= ' (' . strtoupper($req->half_day_period) . ')';
            $created = $req->created_at->format('Ymd\THis\Z');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTART;VALUE=DATE:' . $dtStart;
            $lines[] = 'DTEND;VALUE=DATE:' . $dtEnd;
            $lines[] = 'SUMMARY:' . $this->esc($summary);
            $lines[] = 'DESCRIPTION:' . $this->esc($req->leaveType->name . ($req->notes ? ' — ' . $req->notes : ''));
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'TRANSP:TRANSPARENT';
            $lines[] = 'CREATED:' . $created;
            $lines[] = 'LAST-MODIFIED:' . $req->updated_at->format('Ymd\THis\Z');
            $lines[] = 'END:VEVENT';
        }

        // Pending leave (as tentative)
        $pending = LeaveRequest::with('leaveType')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('end_date', '>=', now())
            ->get();

        foreach ($pending as $req) {
            $dtStart = $req->start_date->format('Ymd');
            $dtEnd   = $req->end_date->copy()->addDay()->format('Ymd');
            $uid     = 'leave-pending-' . $req->id . '@hr.pro-business.co.uk';
            $summary = '[Pending] ' . $req->leaveType->name;

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTART;VALUE=DATE:' . $dtStart;
            $lines[] = 'DTEND;VALUE=DATE:' . $dtEnd;
            $lines[] = 'SUMMARY:' . $this->esc($summary);
            $lines[] = 'STATUS:TENTATIVE';
            $lines[] = 'TRANSP:TRANSPARENT';
            $lines[] = 'END:VEVENT';
        }

        // Public holidays for their org
        if ($user->org_id) {
            $bhs = PublicHoliday::where('org_id', $user->org_id)
                ->where('active', true)
                ->where('date', '>=', now()->subMonths(3))
                ->where('date', '<=', now()->addMonths(18))
                ->get();

            foreach ($bhs as $bh) {
                $ds  = $bh->date->format('Ymd');
                $uid = 'bh-' . $bh->id . '@hr.pro-business.co.uk';
                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:' . $uid;
                $lines[] = 'DTSTART;VALUE=DATE:' . $ds;
                $lines[] = 'DTEND;VALUE=DATE:' . $bh->date->copy()->addDay()->format('Ymd');
                $lines[] = 'SUMMARY:' . $this->esc('🏦 ' . $bh->name);
                $lines[] = 'STATUS:CONFIRMED';
                $lines[] = 'TRANSP:TRANSPARENT';
                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        $body = implode("\r\n", $lines) . "\r\n";

        return response($body, 200, [
            'Content-Type'        => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="leave-' . $user->id . '.ics"',
            'Cache-Control'       => 'no-cache, must-revalidate',
        ]);
    }

    private function esc(string $s): string {
        $s = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $s);
        // Fold long lines (RFC 5545)
        $out = '';
        while (mb_strlen($s) > 74) {
            $out .= mb_substr($s, 0, 74) . "\r\n ";
            $s = mb_substr($s, 74);
        }
        return $out . $s;
    }
}
