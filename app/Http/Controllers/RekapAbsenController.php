<?php

namespace App\Http\Controllers;

use App\Models\RekapAbsen;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UpdateRekapAbsenRequest;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RekapAbsenExport implements FromCollection, WithHeadings, WithMapping
{
    // get user rekap absen
    public function collection()
    {
        return RekapAbsen::where('user_id', Auth::user()->id)
            ->with('user', 'checkin', 'checkout')
            ->get();
    }

    // map rekap absen to array
    public function map($rekap): array
    {
        return [
            $rekap->id,
            $rekap->user->name,
            $rekap->user->nik,
            $rekap->user->plant,
            $rekap->user->pt,
            $rekap->user->tanggal_lahir ? $rekap->user->tanggal_lahir->format('d/m/Y') : null,
            $rekap->tanggal,
            $rekap->shift,

            $rekap->checkin ? $rekap->checkin->created_at->timezone('Asia/Jakarta')->format('H:i:s') : null,
            $rekap->checkin ? $rekap->checkin->latitude : null,
            $rekap->checkin ? $rekap->checkin->longitude : null,
            $rekap->checkin ? url($rekap->checkin->photo) : null,

            $rekap->checkout ? $rekap->checkout->created_at->timezone('Asia/Jakarta')->format('H:i:s') : null,
            $rekap->checkout ? $rekap->checkout->latitude : null,
            $rekap->checkout ? $rekap->checkout->longitude : null,
            $rekap->checkout ? url($rekap->checkout->photo) : null,
        ];
    }

    // set excel headings
    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'NIK',
            'Plant',
            'PT',
            'Tanggal Lahir',
            'Tanggal',
            'Shift',

            'Checkin',
            'Checkin Latitude',
            'Checkin Longitude',
            'Checkin Photo',

            'Checkout',
            'Checkout Latitude',
            'Checkout Longitude',
            'Checkout Photo',
        ];
    }
}

class RekapAbsenController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // get all rekap absen
        $rekaps = RekapAbsen::where('user_id', Auth::user()->id)
            ->orderBy('tanggal', 'desc')
            ->with('user', 'checkin', 'checkout')
            ->paginate(10);

        // return view
        return view('absens.history', compact('rekaps'));
    }

    /**
     * Download the specified resource.
     */
    public function download()
    {
        // download excel
        return Excel::download(new RekapAbsenExport, 'rekap_absens.csv', ExcelType::CSV);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRekapAbsenRequest $request, RekapAbsen $rekap): RedirectResponse
    {
        // update shift and save
        $rekap->update($request->validated());
        $rekap->save();

        // return redirect
        return Redirect::route('absens.index')->with('status', 'Shift berhasil diperbarui');
    }
}
