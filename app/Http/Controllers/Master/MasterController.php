<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    public function index(Request $request)
    {
        $master_id = Auth::id();
        $tab = $request->get('tab', 'shift');
        $month = intval($request->get('month', date('n')));
        $year = intval($request->get('year', date('Y')));
        $today = date('Y-m-d');

        // Today's schedule
        $todaySchedule = DB::table('zapis')
            ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
            ->join('user', 'zapis.user_id', '=', 'user.id_user')
            ->where('zapis.id_master', $master_id)
            ->whereDate('zapis.date_time', $today)
            ->select(
                'zapis.date_time',
                'yslygi.name as service_name',
                'yslygi.price as service_price',
                DB::raw("CONCAT(user.lastname, ' ', user.name, ' ', COALESCE(user.firstname, '')) as client_name"),
                'user.nomber_tel as client_phone'
            )
            ->orderBy('zapis.date_time')
            ->get();

        // Monthly schedule
        $schedule = DB::table('zapis')
            ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
            ->join('user', 'zapis.user_id', '=', 'user.id_user')
            ->where('zapis.id_master', $master_id)
            ->whereMonth('zapis.date_time', $month)
            ->whereYear('zapis.date_time', $year)
            ->select(
                'zapis.date_time',
                'yslygi.name as service_name',
                'yslygi.price as service_price',
                DB::raw("CONCAT(user.lastname, ' ', user.name, ' ', COALESCE(user.firstname, '')) as client_name"),
                'user.nomber_tel as client_phone'
            )
            ->orderBy('zapis.date_time')
            ->get();

        // Group by days
        $scheduledDays = [];
        foreach ($schedule as $entry) {
            $date = date('Y-m-d', strtotime($entry->date_time));
            $scheduledDays[$date][] = $entry;
        }

        // Month info
        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        $monthName = $monthNames[$month];

        $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
        $numberDays = date('t', $firstDayOfMonth);
        $dayOfWeek = date('w', $firstDayOfMonth);

        // Working days
        $working_days = DB::table('master_schedule')
            ->where('id_master', $master_id)
            ->where('is_active', 1)
            ->pluck('day_of_week')
            ->toArray();

        return view('master.index', compact(
            'tab', 'todaySchedule', 'scheduledDays', 'month', 'year',
            'monthName', 'monthNames', 'numberDays', 'dayOfWeek', 'working_days'
        ));
    }

    public function getClientHistory(Request $request)
    {
        $clientName = trim($request->get('client_name', ''));
        $currentDate = trim($request->get('current_date', ''));

        if (empty($clientName)) {
            return response()->json(['error' => 'Имя клиента не указано'], 400);
        }

        $query = DB::table('zapis as z')
            ->join('yslygi as y', 'z.yslygi_id', '=', 'y.id_yslygi')
            ->join('user as client', 'z.user_id', '=', 'client.id_user')
            ->join('user as m', 'z.id_master', '=', 'm.id_user')
            ->whereRaw("CONCAT(client.lastname, ' ', client.name, ' ', COALESCE(client.firstname, '')) = ?", [$clientName]);

        if (!empty($currentDate)) {
            $query->where('z.date_time', '<', $currentDate);
        }

        $query->whereRaw('z.date_time < NOW()');

        $history = $query->groupBy('y.id_kategori')
            ->selectRaw("
                MAX(z.date_time) as date_time,
                MAX(y.name) AS service_name,
                MAX(y.price) AS service_price,
                y.id_kategori,
                MAX(CONCAT(m.lastname, ' ', m.name, ' ', COALESCE(m.firstname, ''))) AS master_name
            ")
            ->orderByRaw('MAX(z.date_time) DESC')
            ->limit(10)
            ->get();

        return response()->json($history);
    }
}
