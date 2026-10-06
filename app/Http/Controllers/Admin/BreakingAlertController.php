<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BreakingAlert;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BreakingAlertController extends Controller
{
    private const SITE_TIMEZONE = 'Asia/Hebron';

    public function index(): View
    {
        $alerts = BreakingAlert::query()->with('user')->latest()->paginate(20);

        return view('admin.breaking-alerts.index', compact('alerts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sound_enabled'] = $request->boolean('sound_enabled', true);
        $data['duration_minutes'] = (int) $data['duration_minutes'];
        $data['starts_at'] = Carbon::parse($data['starts_at'], self::SITE_TIMEZONE)
            ->setTimezone(config('app.timezone'));
        $data['expires_at'] = $data['starts_at']->copy()->addMinutes($data['duration_minutes']);

        BreakingAlert::create($data);

        return back()->with('success', 'تم نشر الخبر العاجل بنجاح.');
    }

    public function update(Request $request, BreakingAlert $breakingAlert): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['sound_enabled'] = $request->boolean('sound_enabled');
        $data['duration_minutes'] = (int) $data['duration_minutes'];
        $data['starts_at'] = Carbon::parse($data['starts_at'], self::SITE_TIMEZONE)
            ->setTimezone(config('app.timezone'));
        $data['expires_at'] = $data['starts_at']->copy()->addMinutes($data['duration_minutes']);

        $breakingAlert->update($data);

        return back()->with('success', 'تم تحديث الخبر العاجل.');
    }

    public function destroy(BreakingAlert $breakingAlert): RedirectResponse
    {
        $breakingAlert->delete();

        return back()->with('success', 'تم حذف الخبر العاجل.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'link' => ['nullable', 'url', 'max:191'],
            'duration_minutes' => ['required', 'integer', 'in:5,10'],
            'starts_at' => [
                'bail',
                'required',
                'date_format:Y-m-d\\TH:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $startsAt = Carbon::createFromFormat('Y-m-d\\TH:i', (string) $value, self::SITE_TIMEZONE);

                    if ($startsAt->lessThanOrEqualTo(now(self::SITE_TIMEZONE))) {
                        $fail('يجب أن يكون موعد بداية الخبر العاجل بعد الوقت الحالي.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
            'sound_enabled' => ['nullable', 'boolean'],
        ]);
    }
}
