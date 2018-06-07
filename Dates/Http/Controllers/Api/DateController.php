<?php

namespace App\Modules\Dates\Http\Controllers\Api;

use App\Modules\Dates\Http\Requests\AvailableDateAPIRequest;
use App\Modules\Dates\Http\Requests\PublicDateAPIRequest;
use App\Modules\Dates\Http\Requests\SaveCalendarAPIRequest;
use App\Modules\Dates\Models\Date;
use App\Modules\Dates\Repositories\DateRepository;
use App\Modules\Users\Http\Controllers\Api\ControllerResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Response;

/**
 * Class DateController
 * @package App\Modules\Dates\Http\Controllers\Api
 */
class DateController extends Controller
{
    use ControllerResponseTrait;

    /**
     * @param PublicDateAPIRequest $request
     *
     * @return JsonResponse
     */
    public function getPublicDates(PublicDateAPIRequest $request): JsonResponse
    {
        $min = $request['min'];
        $max = $request['max'];

        $modelDate = app(Date::class);

        $dateRepository = app(DateRepository::class);

        $dates = $dateRepository->findPublicDays(Auth::user(), $min, $max);

        $resultData = $modelDate->getDataForPublicDays($dates);

        return $this->respondSuccessWithData($resultData, 'Successfully got the public calendar.');
    }

    /**
     * @param AvailableDateAPIRequest $request
     *
     * @return JsonResponse
     */
    public function getAvailableDate(AvailableDateAPIRequest $request): JsonResponse
    {
        $day = $request['date'];

        $modelDate = app(Date::class);

        $dateRepository = app(DateRepository::class);

        $dates = $dateRepository->findPublicDay(Auth::user(), $day);

        $result = $modelDate->getDataForUser($dates);
        return $this->respondSuccessWithData($result, 'Successfully got available users for the day.');
    }

    /**
     * @param PublicDateAPIRequest $request
     *
     * @return JsonResponse
     */
    public function getAvailableDateForMe(PublicDateAPIRequest $request): JsonResponse
    {
        $min = $request['min'];
        $max = $request['max'];

        $modelDate = app(Date::class);

        $dateRepository = app(DateRepository::class);

        $dates = $dateRepository->findPublicDaysForMe($min, $max);

        $result = $modelDate->getDataForMe($dates);

        return $this->respondSuccessWithData($result, 'Successfully got the personal calendar.');
    }

    /**
     * @param SaveCalendarAPIRequest $request
     *
     * @return JsonResponse
     */
    public function saveMyDays(SaveCalendarAPIRequest $request)
    {
        $dataSave = [];
        $requestData = $request->json()->all();
        $modelDate = app(Date::class);

        foreach ($requestData['dates'] as $date) {
            $dataSave[] = [
                'day' => $date,
                'user_id' => Auth::id(),
            ];
        }
        $dateRepository = app(DateRepository::class);

        $dateRepository->deleteWhere(['user_id' => Auth::id()]);
        $dateRepository->insert($dataSave);

        $from = Carbon::parse($requestData['range'][0]);
        $to = Carbon::parse($requestData['range'][1]);

        $dates['datesAffected'] = $modelDate->generateDateRange($from, $to);

        return $this->respondSuccessWithData($dates, 'Successfully got the personal calendar.');
    }
}
