<?php

namespace App\Http\Controllers\General;

use Illuminate\Http\Request;
use App\Mail\ReservationCommentMail;
use Illuminate\Support\Facades\Mail;
use App\Models\reservation_classroom;

class ReservationCommentController extends Controller
{
    //
    public function sendComment(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations_classrooms,id',
            'comment' => 'required|string',
        ]);

        $reservation = ReservationsClassroom::findOrFail($request->reservation_id);
        $senderEmail = auth()->user()->email;

        // Enviar el correo
        Mail::to($reservation->Email)
            ->send(new ReservationCommentMail(
                $reservation,
                $request->comment,
                $senderEmail
            ));

        return response()->json(['message' => 'Comentario enviado correctamente']);
    }
}
