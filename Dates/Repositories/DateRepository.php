<?php

namespace App\Modules\Dates\Repositories;

use App\Modules\Dates\Models\Date;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class DateRepository
 * @package App\Modules\Dates\Repositories
 * @version January 5, 2018, 1:52 pm UTC
 *
 * @method Date findWithoutFail($id, $columns = ['*'])
 * @method Date find($id, $columns = ['*'])
 * @method Date first($columns = ['*'])
*/
class DateRepository extends BaseRepository
{

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Date::class;
    }


    /**
     * @param array $dataSave
     */
    public function insert(array $dataSave): void
    {
        Date::insert($dataSave);
    }

    /**
     * @param User $user
     * @param string $min
     * @param string $max
     *
     * @return Collection
     */
    public function findPublicDays(User $user, string $min, string $max): Collection
    {
        $modelDate = app(Date::class);

        return $modelDate->getPublicDays($user, $min, $max);
    }

    /**
     * @param User $user
     * @param string $day
     *
     * @return Collection
     */
    public function findPublicDay(User $user, string $day): Collection
    {
        $modelDate = app(Date::class);

        return $modelDate->getPublicDay($user, $day);
    }

    /**
     * @param string $min
     * @param string $max
     *
     * @return Collection
     */
    public function findPublicDaysForMe(string $min, string $max): Collection
    {
        $modelDate = app(Date::class);

        return $modelDate->getPublicDaysForMe($min, $max);
    }

    public function count(): int
    {
        return Date::all()->count();
    }
}
