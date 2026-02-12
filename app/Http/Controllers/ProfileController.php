<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id_user;
        $user_role = $user->id_roli;

        $stats = null;
        $salary = 0;
        $start_date = $request->get('start_date', date('Y-m-01'));
        $end_date = $request->get('end_date', date('Y-m-t'));

        // Master statistics
        if ($user_role == 1) {
            $stats = DB::table('zapis')
                ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
                ->where('zapis.id_master', $user_id)
                ->whereBetween('zapis.date_time', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                ->whereRaw('zapis.date_time < NOW()')
                ->selectRaw('COUNT(*) as total_bookings, COALESCE(SUM(yslygi.price), 0) as total_revenue')
                ->first();

            $salary = ($stats->total_revenue ?? 0) * 0.50;
        }

        // Active bookings
        $active_bookings = DB::table('zapis')
            ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
            ->join('user as m', 'zapis.id_master', '=', 'm.id_user')
            ->where('zapis.user_id', $user_id)
            ->whereRaw('zapis.date_time >= NOW()')
            ->select(
                'zapis.id_zapis',
                'zapis.date_time',
                'yslygi.name as service_name',
                'yslygi.price as service_price',
                DB::raw("CONCAT(m.lastname, ' ', m.name, ' ', COALESCE(m.firstname, '')) as master_name")
            )
            ->orderBy('zapis.date_time')
            ->get();

        // Past bookings
        $past_bookings = DB::table('zapis')
            ->join('yslygi', 'zapis.yslygi_id', '=', 'yslygi.id_yslygi')
            ->join('user as m', 'zapis.id_master', '=', 'm.id_user')
            ->where('zapis.user_id', $user_id)
            ->whereRaw('zapis.date_time < NOW()')
            ->select(
                'zapis.id_zapis',
                'zapis.date_time',
                'yslygi.name as service_name',
                'yslygi.price as service_price',
                DB::raw("CONCAT(m.lastname, ' ', m.name, ' ', COALESCE(m.firstname, '')) as master_name")
            )
            ->orderByDesc('zapis.date_time')
            ->get();

        $total_price = $active_bookings->sum('service_price');

        $user_info = User::select('lastname', 'name', 'firstname', 'nomber_tel')
            ->where('id_user', $user_id)
            ->first();

        return view('profile', compact(
            'user', 'user_role', 'stats', 'salary', 'start_date', 'end_date',
            'active_bookings', 'past_bookings', 'total_price', 'user_info'
        ));
    }

    public function update(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Пожалуйста, войдите в систему']);
        }

        $user_id = Auth::id();
        $lastname = trim($request->input('lastname', ''));
        $name = trim($request->input('name', ''));
        $firstname = trim($request->input('firstname', ''));
        $nomber_tel = $request->input('nomber_tel', '');
        $new_password = $request->input('new_password', '');

        if (empty($lastname) || empty($name)) {
            return response()->json(['status' => 'error', 'message' => 'Фамилия и имя обязательны для заполнения']);
        }

        if (!preg_match('/^[А-Яа-яЁё\s\-]+$/u', $lastname)) {
            return response()->json(['status' => 'error', 'message' => 'Фамилия должна содержать только буквы']);
        }

        if (!preg_match('/^[А-Яа-яЁё\s\-]+$/u', $name)) {
            return response()->json(['status' => 'error', 'message' => 'Имя должно содержать только буквы']);
        }

        if (!empty($firstname) && !preg_match('/^[А-Яа-яЁё\s\-]+$/u', $firstname)) {
            return response()->json(['status' => 'error', 'message' => 'Отчество должно содержать только буквы']);
        }

        if (!empty($new_password) && strlen($new_password) < 6) {
            return response()->json(['status' => 'error', 'message' => 'Пароль должен содержать минимум 6 символов']);
        }

        $user = User::find($user_id);
        $user->lastname = $lastname;
        $user->name = $name;
        $user->firstname = $firstname;
        $user->nomber_tel = $nomber_tel;

        if (!empty($new_password)) {
            $user->password = Hash::make($new_password);
        }

        $user->save();

        session(['FIO' => $user->full_name]);

        return response()->json(['status' => 'success', 'message' => 'Профиль успешно обновлен!']);
    }
}
