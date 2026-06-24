<?php

namespace App\Http\Controllers;

use App\Models\BankSocial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankSocialController extends Controller
{
    
    public function submitbankinfo(Request $request){

        $record = BankSocial::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is submitted'
        ]);

        return true;

    }
    public function verifybankinfo(Request $request){

        $record = BankSocial::find($request)->first();

        $record->recordstatus = 2;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 2,
            'details' => 'The information is verified'
        ]);

        return $record;

    }
    public function approvebankinfo(Request $request){

        $record = BankSocial::find($request)->first();

        $record->recordstatus = 3;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 3,
            'details' => 'The information is approved'
        ]);

        return $record;

    }
    public function movebankinfotorecheck(Request $request){

        $record = BankSocial::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is moved back to recheck status'
        ]);

        return $record;

    }
    public function movebankinfotodraft(Request $request){

        $record = BankSocial::find($request)->first();

        $record->recordstatus = 0;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'The information is moved back to draft status'
        ]);

        return $record;

    }

    public function loadbankinfohistories(Request $request){

        $record = BankSocial::find($request)->first();

        $histories = $record->histories;

        return $histories->load('creator:id,name');

    }
}
