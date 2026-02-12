<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\MasterSchedule;
use App\Models\MasterDayOff;
use App\Models\MasterServicePivot;
use App\Models\User;
use App\Services\BookingValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Create a new booking (AJAX)
     */
    public function store(Request $request)
    {
        $input = $request->json()->all();

        $user_id = intval($input['user_id'] ?? 0);
        $yslygi_id = intval($input['yslygi_id'] ?? 0);
        $date_time = $input['date_time'] ?? '';
        $id_master = intval($input['id_master'] ?? 0);

        if (!Auth::check() || Auth::id() != $user_id) {
            return response()->json(['status' => 'error', 'message' => 'Ошибка авторизации']);
        }

        $service = Service::find($yslygi_id);
        if (!$service) {
            return response()->json(['status' => 'error', 'message' => 'Услуга не найдена']);
        }

        [$ok, $message] = BookingValidator::validate($id_master, $yslygi_id, $date_time, null);
        if (!$ok) {
            return response()->json(['status' => 'error', 'message' => $message]);
        }

        // Create booking
        Booking::create([
            'user_id' => $user_id,
            'yslygi_id' => $yslygi_id,
            'date_time' => $date_time,
            'id_master' => $id_master,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Запись успешно создана! Мы ждем вас в салоне.']);
    }

    /**
     * Edit booking (AJAX)
     */
    public function update(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Необходимо авторизоваться']);
        }

        $booking_id = $request->input('booking_id');
        $date_time = $request->input('date_time');
        $user_id = Auth::id();

        $booking = Booking::find($booking_id);
        if (!$booking || $booking->user_id != $user_id) {
            return response()->json(['status' => 'error', 'message' => 'У вас нет прав для редактирования этой записи.']);
        }

        $service = Service::find($booking->yslygi_id);
        $duration_minutes = $service->duration_minutes ?? 60;

        // Check 2 hours in advance
        $now = now();
        $newDateTime = \Carbon\Carbon::parse($date_time);

        if ($newDateTime <= $now) {
            return response()->json(['status' => 'error', 'message' => 'Нельзя изменить запись на прошедшее время.']);
        }

        if ($newDateTime->diffInHours($now) < 2) {
            return response()->json(['status' => 'error', 'message' => 'Изменять запись можно не менее чем за 2 часа до назначенного времени.']);
        }

        $date_time_str = $newDateTime->format('Y-m-d H:i:s');

        [$ok, $message] = BookingValidator::validate(
            $booking->id_master,
            $booking->yslygi_id,
            $date_time_str,
            (int) $booking->id_zapis
        );
        if (!$ok) {
            return response()->json(['status' => 'error', 'message' => $message]);
        }

        // Update booking
        $booking->update(['date_time' => $date_time]);

        return response()->json(['status' => 'success', 'message' => 'Запись успешно обновлена']);
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $booking = Booking::where('id_zapis', $request->booking_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($booking) {
            $booking->delete();
            session()->flash('message', 'Запись успешно отменена');
        } else {
            session()->flash('message', 'Ошибка: запись не найдена или не принадлежит вам');
        }

        return redirect()->route('profile');
    }

    /**
     * Get available time slots (AJAX)
     */
    public function getAvailableSlots(Request $request)
    {
        $service_id = $request->get('service_id');
        $date = $request->get('date');
        $master_id = $request->get('master_id');

        if ($service_id && $date && !$master_id) {
            return $this->getTimeSlotsForService($service_id, $date);
        } elseif ($master_id && $service_id && $date) {
            return $this->getTimeSlotsForMaster($master_id, $service_id, $date, $request->get('exclude_booking_id'));
        }

        return response()->json(['status' => 'error', 'message' => 'Недостаточно параметров']);
    }

    protected function getTimeSlotsForService($service_id, $date)
    {
        $service = Service::find($service_id);
        $duration_minutes = $service->duration_minutes ?? 60;

        $master_ids = DB::table('user')
            ->join('master_services', 'user.id_user', '=', 'master_services.id_master')
            ->where('master_services.id_yslygi', $service_id)
            ->where('user.id_roli', 1)
            ->pluck('user.id_user')
            ->toArray();

        if (empty($master_ids)) {
            return response()->json(['status' => 'error', 'message' => 'Нет мастеров для этой услуги']);
        }

        $all_slots = [];
        $day_of_week = date('w', strtotime($date));

        foreach ($master_ids as $mid) {
            $slots = $this->getSlotsForMaster($mid, $date, $duration_minutes, $day_of_week, null);
            $all_slots = array_merge($all_slots, $slots);
        }

        $all_slots = array_unique($all_slots);
        sort($all_slots);

        $formatted = array_map(fn($time) => ['time' => $time, 'display' => $time], $all_slots);

        return response()->json([
            'status' => 'success',
            'available_slots' => $formatted,
            'message' => empty($formatted) ? 'На выбранную дату нет доступного времени' : null,
        ]);
    }

    protected function getTimeSlotsForMaster($master_id, $service_id, $date, $exclude_booking_id = null)
    {
        $service = Service::find($service_id);
        if (!$service) {
            return response()->json(['status' => 'error', 'message' => 'Услуга не найдена']);
        }

        $duration_minutes = $service->duration_minutes ?? 60;
        $day_of_week = date('w', strtotime($date));
        $slots = $this->getSlotsForMaster($master_id, $date, $duration_minutes, $day_of_week, $exclude_booking_id);

        $formatted = array_map(fn($time) => ['time' => $time, 'formatted' => $time], $slots);

        return response()->json([
            'status' => 'success',
            'available_slots' => $formatted,
            'message' => count($formatted) . ' доступных слотов',
        ]);
    }

    protected function getSlotsForMaster($master_id, $date, $duration_minutes, $day_of_week, $exclude_booking_id)
    {
        $slots = [];

        $schedule = MasterSchedule::where('id_master', $master_id)
            ->where('day_of_week', $day_of_week)
            ->where('is_active', 1)
            ->first();

        if (!$schedule) return $slots;

        // Check day off
        if (MasterDayOff::where('id_master', $master_id)->where('date_off', $date)->exists()) {
            return $slots;
        }

        // Get existing bookings
        $query = DB::table('zapis')
            ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
            ->where('zapis.id_master', $master_id)
            ->whereDate('zapis.date_time', $date)
            ->select('zapis.date_time', 'yslygi.duration_minutes',
                DB::raw('DATE_ADD(zapis.date_time, INTERVAL COALESCE(yslygi.duration_minutes, 60) MINUTE) as end_time'))
            ->orderBy('zapis.date_time');

        if ($exclude_booking_id) {
            $query->where('zapis.id_zapis', '!=', $exclude_booking_id);
        }

        $existing_bookings = $query->get();

        // Generate slots with 15-minute intervals
        $start = strtotime($date . ' ' . $schedule->start_time);
        $end = strtotime($date . ' ' . $schedule->end_time);
        $interval = 15 * 60;

        for ($time = $start; $time <= ($end - $duration_minutes * 60); $time += $interval) {
            $time_end = $time + ($duration_minutes * 60);
            $slot_available = true;

            foreach ($existing_bookings as $booking) {
                $booking_start = strtotime($booking->date_time);
                $booking_end = strtotime($booking->end_time);

                if ($time < $booking_end && $time_end > $booking_start) {
                    $slot_available = false;
                    break;
                }
            }

            if ($slot_available && $time_end <= $end) {
                $slots[] = date('H:i', $time);
            }
        }

        return $slots;
    }

    /**
     * Get available masters for a time slot (AJAX)
     */
    public function getAvailableMasters(Request $request)
    {
        $service_id = intval($request->get('id_yslygi'));
        $date = $request->get('date');
        $time = $request->get('time');

        if (!$service_id || !$date || !$time) {
            return response()->json(['error' => 'Не указаны необходимые параметры']);
        }

        $datetime = $date . ' ' . $time . ':00';
        $day_of_week = date('w', strtotime($date));

        $service = Service::find($service_id);
        $duration = $service->duration_minutes ?? 60;

        // Get masters who provide this service, work this day, and don't have day off
        $all_masters = DB::table('user')
            ->join('master_services', 'user.id_user', '=', 'master_services.id_master')
            ->where('master_services.id_yslygi', $service_id)
            ->where('user.id_roli', 1)
            ->whereIn('user.id_user', function ($q) use ($day_of_week) {
                $q->select('id_master')
                    ->from('master_schedule')
                    ->where('day_of_week', $day_of_week)
                    ->where('is_active', 1);
            })
            ->whereNotIn('user.id_user', function ($q) use ($date) {
                $q->select('id_master')
                    ->from('master_days_off')
                    ->where('date_off', $date);
            })
            ->select('user.id_user', DB::raw("CONCAT(user.lastname, ' ', user.name, ' ', COALESCE(user.firstname, '')) as FIO"))
            ->orderBy('user.lastname')
            ->get();

        if ($all_masters->isEmpty()) {
            return response()->json(['error' => 'Нет доступных мастеров для этой услуги в выбранный день']);
        }

        $available_masters = [];

        foreach ($all_masters as $master) {
            $schedule = MasterSchedule::where('id_master', $master->id_user)
                ->where('day_of_week', $day_of_week)
                ->where('is_active', 1)
                ->first();

            if (!$schedule) continue;

            $start_time = strtotime($schedule->start_time);
            $end_time = strtotime($schedule->end_time);
            $selected_time = strtotime($time . ':00');

            if ($selected_time < $start_time || $selected_time > $end_time) continue;

            $service_end_time = $selected_time + ($duration * 60);
            if ($service_end_time > $end_time) continue;

            // Check conflicts
            $conflicts = DB::select("
                SELECT COUNT(*) as cnt
                FROM zapis z
                JOIN yslygi y ON z.yslygi_id = y.id_yslygi
                WHERE z.id_master = ?
                AND DATE(z.date_time) = ?
                AND (
                    (? BETWEEN z.date_time AND DATE_SUB(DATE_ADD(z.date_time, INTERVAL y.duration_minutes MINUTE), INTERVAL 1 SECOND))
                    OR
                    (DATE_ADD(?, INTERVAL ? MINUTE) BETWEEN DATE_ADD(z.date_time, INTERVAL 1 SECOND) AND DATE_ADD(z.date_time, INTERVAL y.duration_minutes MINUTE))
                    OR
                    (? <= z.date_time AND DATE_ADD(?, INTERVAL ? MINUTE) >= DATE_ADD(z.date_time, INTERVAL y.duration_minutes MINUTE))
                )
            ", [$master->id_user, $date, $datetime, $datetime, $duration, $datetime, $datetime, $duration]);

            if ($conflicts[0]->cnt > 0) continue;

            $available_masters[] = ['id_user' => $master->id_user, 'FIO' => $master->FIO];
        }

        if (empty($available_masters)) {
            return response()->json(['error' => 'Нет доступных мастеров на выбранное время']);
        }

        return response()->json($available_masters);
    }

    /**
     * Get masters for a service (AJAX)
     */
    public function getMasters(Request $request)
    {
        $service_id = intval($request->get('service_id'));
        $date = $request->get('date');

        $query = DB::table('user')
            ->join('master_services', 'user.id_user', '=', 'master_services.id_master')
            ->where('master_services.id_yslygi', $service_id)
            ->where('user.id_roli', 1);

        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $day_of_week = date('w', strtotime($date));
            $query->whereIn('user.id_user', function ($q) use ($day_of_week) {
                $q->select('id_master')
                    ->from('master_schedule')
                    ->where('day_of_week', $day_of_week)
                    ->where('is_active', 1);
            })->whereNotIn('user.id_user', function ($q) use ($date) {
                $q->select('id_master')
                    ->from('master_days_off')
                    ->where('date_off', $date);
            });
        }

        $masters = $query->select('user.id_user', DB::raw("CONCAT(user.lastname, ' ', user.name, ' ', COALESCE(user.firstname, '')) as FIO"))
            ->orderBy('user.lastname')
            ->get()
            ->map(fn($m) => ['id_user' => $m->id_user, 'FIO' => $m->FIO]);

        return response()->json($masters);
    }
}
