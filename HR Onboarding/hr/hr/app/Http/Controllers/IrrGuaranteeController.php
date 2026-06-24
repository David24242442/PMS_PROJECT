<?php

namespace App\Http\Controllers;

use App\Models\IrrGuarantee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IrrGuaranteeController extends Controller
{
    public function ssubmitirrguarantee(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is submitted'
        ]);

        return true;

    }
    public function verifyirrguarantee(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $record->recordstatus = 2;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 2,
            'details' => 'The information is verified'
        ]);

        return $record;

    }
    public function approveirrguarantee(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $record->recordstatus = 3;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 3,
            'details' => 'The information is approved'
        ]);

        return $record;

    }

    public function moveirrguaranteetorecheck(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is moved back to recheck status'
        ]);

        return $record;

    }

    public function moveirrguaranteetodraft(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $record->recordstatus = 0;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'The information is moved back to draft status'
        ]);

        return $record;

    }

    public function loadirrguarinfohistories(Request $request){

        $record = IrrGuarantee::find($request)->first();

        $histories = $record->histories;

        return $histories->load('creator:id,name');

    }
}
