<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\MasterSchedule;
use App\Models\MasterDayOff;
use App\Models\MasterServicePivot;
use Illuminate\Support\Facades\DB;

class BookingValidator
{
    /**
     * Validate booking: day off, working hours, duration, no double-booking, master provides service.
     *
     * @param int         $id_master
     * @param int         $yslygi_id
     * @param string      $date_time  'Y-m-d H:i:s' or 'Y-m-d H:i'
     * @param int|null    $exclude_booking_id  When editing, exclude this booking from conflict check
     * @return array  [ true ] or [ false, 'error message' ]
     */
    public static function validate(int $id_master, int $yslygi_id, string $date_time, ?int $exclude_booking_id = null): array
    {
        $service = Service::find($yslygi_id);
        if (!$service) {
            return [false, 'Услуга не найдена'];
        }

        $duration_minutes = (int) ($service->duration_minutes ?? 60);
        $ts = strtotime($date_time);
        if ($ts === false) {
            return [false, 'Некорректная дата и время'];
        }

        $date_only = date('Y-m-d', $ts);
        $time_only = date('H:i:s', $ts);
        $day_of_week = (int) date('w', $ts); // 0=Sun, 1=Mon, ... 6=Sat

        // 1) Мастер оказывает услугу
        if (!MasterServicePivot::where('id_master', $id_master)->where('id_yslygi', $yslygi_id)->exists()) {
            return [false, 'Мастер не предоставляет выбранную услугу'];
        }

        // 2) Выходной мастера в этот день
        if (MasterDayOff::where('id_master', $id_master)->whereDate('date_off', $date_only)->exists()) {
            return [false, 'У мастера выходной в выбранный день'];
        }

        // 3) Рабочий день и время в рамках графика
        $schedule = MasterSchedule::where('id_master', $id_master)
            ->where('day_of_week', $day_of_week)
            ->where('is_active', 1)
            ->first();

        if (!$schedule) {
            return [false, 'Мастер не работает в выбранный день'];
        }

        $startTime = $schedule->start_time;
        $endTime = $schedule->end_time;
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        if ($time_only < $startTime || $time_only > $endTime) {
            return [false, 'Выбранное время вне рабочего графика мастера'];
        }

        // 4) Услуга успевает завершиться до конца рабочего дня (учёт длительности)
        $booking_end_ts = $ts + ($duration_minutes * 60);
        $booking_end_time = date('H:i:s', $booking_end_ts);
        $schedule_end_ts = strtotime($date_only . ' ' . $endTime);
        if ($booking_end_ts > $schedule_end_ts) {
            return [false, 'Услуга не успеет завершиться до конца рабочего дня мастера'];
        }

        // 5) Нет пересечений с другими записями (учёт длительности услуг)
        $new_start = date('Y-m-d H:i:s', $ts);
        $new_end = date('Y-m-d H:i:s', $booking_end_ts);

        $query = DB::table('zapis as z')
            ->join('yslygi as y', 'z.yslygi_id', '=', 'y.id_yslygi')
            ->where('z.id_master', $id_master)
            ->where(function ($q) use ($new_start, $new_end) {
                // Пересечение: (A_start < B_end) AND (B_start < A_end)
                $q->whereRaw('z.date_time < ?', [$new_end])
                    ->whereRaw('DATE_ADD(z.date_time, INTERVAL COALESCE(y.duration_minutes, 60) MINUTE) > ?', [$new_start]);
            });

        if ($exclude_booking_id !== null) {
            $query->where('z.id_zapis', '!=', $exclude_booking_id);
        }

        if ($query->exists()) {
            return [false, 'В выбранное время у мастера уже есть запись или будет наложение по времени'];
        }

        return [true, null];
    }
}
