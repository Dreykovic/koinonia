<?php

namespace App\Services;

use App\Models\BulletinEdition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayService
{
    public function getWeekBirthdays(?BulletinEdition $bulletin): Collection
    {
        if (!$bulletin) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
        } else {
            $weekStart = $bulletin->week_start;
            $weekEnd = $bulletin->week_end;
        }

        $dates = collect();
        $current = $weekStart->copy();
        while ($current->lte($weekEnd)) {
            $dates->push($current->copy());
            $current->addDay();
        }

        $monthDayPairs = $dates->map(fn ($d) => [$d->month, $d->day]);

        return User::active()
            ->whereNotNull('birthday')
            ->where(function ($query) use ($monthDayPairs) {
                foreach ($monthDayPairs as [$month, $day]) {
                    $query->orWhere(function ($q) use ($month, $day) {
                        $q->whereMonth('birthday', $month)->whereDay('birthday', $day);
                    });
                }
            })
            ->get()
            ->map(function ($user) use ($weekStart) {
                $birthday = Carbon::parse($user->birthday);
                $birthdayThisYear = $birthday->copy()->year($weekStart->year);
                $user->birthday_day = $birthdayThisYear->locale('fr')->isoFormat('dddd D');
                return $user;
            })
            ->sortBy(fn ($user) => Carbon::parse($user->birthday)->format('m-d'));
    }
}
