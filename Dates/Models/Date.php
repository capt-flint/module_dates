<?php

namespace App\Modules\Dates\Models;

use App\Modules\Proposals\Models\Proposal;
use App\Modules\Users\Dto\UserPreferencesYears;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Eloquent as Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class Date
 * @package App\Modules\Dates\Models
 * @version January 5, 2018, 1:52 pm UTC
 *
 * @property \App\Modules\Dates\Models\User user
 * @property date day
 * @property integer user_id
 */
class Date extends Model
{

    public $fillable = [
        'day',
        'user_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'day' => 'date',
        'user_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'day' => 'required|max:255'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function user()
    {
        return $this->hasOne(\App\Modules\Users\Models\User::class, 'id', 'user_id');
    }

    /**
     * @return mixed
     */
    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'day_id', 'id');
    }

    /**
     * @return mixed
     */
    public function proposalsActive()
    {
        return $this->hasMany(Proposal::class, 'day_id', 'id')->where('status', '!=', 'declined')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('status', 'pending')->where('created_at', '>', Carbon::parse('-24 hours'));
                })
                    ->orWhere(function ($query) {
                        $query->where('status', 'accepted');
                    });
            });
    }

    /**
     * @param $query
     * @param string|null $gender
     * @param \App\Modules\Users\Dto\UserPreferencesYears $preferencesYears
     * @param int $id
     *
     * @return mixed
     */
    public function scopeDatesAndPreferences($query, string $gender = null, UserPreferencesYears $preferencesYears, int $id)
    {
        return $query->whereHas('user', function ($q) use ($gender, $preferencesYears, $id) {
            if (null !== $gender) {
                $q->where('gender', $gender);
            }
            $q->whereBetween('birthday', [$preferencesYears->getMax(), $preferencesYears->getMin()]);
            $q->where('id', '!=', $id);
        });
    }

    /**
     * @param $query
     * @param User $user
     *
     * @return mixed
     */
    public function scopeExistingProposals($query, User $user)
    {
        $activeDaysId = $user->proposals->pluck('day_id')->toArray();
        return $query->whereNotIn('id', $activeDaysId);
    }

    /**
     * @param User $user
     * @param string $min
     * @param string $max
     *
     * @return Collection
     */
    public function getPublicDays(User $user, string $min, string $max): Collection
    {
        $orientation = $user->getUserOrientation();
        $preferencesYears = $user->getUserPreferencesYear();
        $radius = $user->preference->radius;
        $coordinates['longitude'] = optional($user->location)->longitude;
        $coordinates['latitude'] = optional($user->location)->latitude;

        $result = Date::datesAndPreferences($orientation, $preferencesYears, $user->id)
            ->whereHas('user.location', function ($q) use ($radius, $coordinates) {
                $q->usersForRadius($coordinates, $radius);
            })
            ->where('day', '>=', $min)
            ->where('day', '<=', $max)
            ->existingProposals($user)
            ->get();

        return $result;
    }

    /**
     * @param User $user
     * @param string $day
     *
     * @return Collection
     */
    public function getPublicDay(User $user, string $day): Collection
    {
        $orientation = $user->getUserOrientation();
        $preferencesYears = $user->getUserPreferencesYear();
        $radius = $user->preference->radius;
        $coordinates['longitude'] = optional($user->location)->longitude;
        $coordinates['latitude'] = optional($user->location)->latitude;

        $result = Date::datesAndPreferences($orientation, $preferencesYears, $user->id)
            ->whereHas('user.location', function ($q) use ($radius, $coordinates) {
                $q->usersForRadius($coordinates, $radius);
            })
            ->where('day', '=', $day)
            ->existingProposals($user)
            ->get();

        return $result;
    }

    /**
     * @param string $min
     * @param string $max
     *
     * @return Collection
     */
    public function getPublicDaysForMe(string $min, string $max): Collection
    {
        $result = Date::where('day', '>=', $min)
            ->where('day', '<=', $max)
            ->where('user_id', '=', Auth::id())
            ->get();

        return $result;
    }

    /**
     * @param Collection $days
     *
     * @return array
     */
    public function getDataForPublicDays(Collection $days): array
    {
        $data = [];
        $grouped = $days->groupBy(function ($item) {
            return $item->day->format('y-m-d');
        });

        foreach ($grouped as $key => $items) {
            $resultItem = (object)[
                '_id' => (string) $items[0]->id,
                'date' => $items[0]->day->format(config('app.data_format')),
                'users' => array_map(
                    function ($value) {
                        return (string) $value;
                    },
                    $items->pluck('user_id')->toArray()
                )
            ];

            $data[] = $resultItem;
        }
        return $data;
    }

    /**
     * @param Collection $days
     *
     * @return array
     */
    public function getDataForUser(Collection $days): array
    {
        if ($days->isEmpty()) {
            return [];
        }

        $data = [
            '_id' => (string) $days[0]->id,
            'date' => $days[0]->day->format(config('app.data_format'))
        ];
        foreach ($days as $day) {
            $data['users'][] = $day->user->getUserForAvailableDate();
        }

        return $data;
    }


    /**
     * @param Collection $days
     *
     * @return array
     */
    public function getDataForMe(Collection $days): array
    {
        if ($days->isEmpty()) {
            return [];
        }

        $data = [];

        foreach ($days as $key => $items) {
            $resultItem = (object)[
                '_id' => (string) $items->id,
                'date' => $items->day->format(config('app.data_format')),
            ];
            $data[] = $resultItem;
        }

        return $data;
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return array
     */
    public function generateDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format(config('app.data_format'));
        }
        return $dates;
    }
}
