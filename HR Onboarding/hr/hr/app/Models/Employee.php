<?php

namespace App\Models;

use App\Models\User;
use App\Models\Wife;
use App\Models\Union;
use App\Models\Upload;
use App\Models\History;
use App\Models\Nominee;
use App\Models\Children;
use App\Models\Education;
use App\Models\Guarantor;
use App\Models\Reference;
use App\Models\BankSocial;
use App\Models\PresentJob;
use App\Models\IrrGuarantee;
use App\Models\Driverlicense;
use App\Models\WorkExperience;
use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id');
    }
    
    public function banksocial(){
        return $this->hasOne(BankSocial::class,'emp_id');
    }
    public function irrguarantor(){
        return $this->hasOne(IrrGuarantee::class,'emp_id');
    }
    public function educations(){
        return $this->hasMany(Education::class,'emp_id');
    }
    public function presentjob(){
        return $this->hasOne(PresentJob::class,'emp_id');
    }

    public function nominee(){
        return $this->hasOne(Nominee::class,'emp_id');
    }

    public function unioninfo(){
        return $this->hasOne(Union::class,'emp_id');
    }

    public function workpermit(){
        return $this->hasOne(Workpermit::class,'emp_id');
    }

    public function driverlicense(){
        return $this->hasOne(Driverlicense::class,'emp_id');
    }

    public function guarantos(){
        return $this->hasMany(Guarantor::class,'emp_id');
    }
    public function econs(){
        return $this->hasMany(EmergencyContact::class,'emp_id');
    }
    public function soccontact(){
        return $this->hasOne(SocialContact::class,'emp_id');
    }
    public function childrens(){
        return $this->hasMany(Children::class,'emp_id');
    }

    public function wives(){
        return $this->hasMany(Wife::class,'emp_id');
    }
    public function refs(){
        return $this->hasMany(Reference::class,'emp_id');
    }
    public function workexps(){
        return $this->hasMany(WorkExperience::class,'emp_id');
    }

    public function uploads()
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    public function profilepicture()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'profilepicture');
    }

    public function appletters()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'appletters');
    }

    public function appointmentletters()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'appointmentletters');
    }

    public function probationconfs()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'probationconfs');
    }

    public function cv()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'cv');
    }

    public function petratrust()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'petratrust');
    }

    public function nhis()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'nhis');
    }

    public function birthcert()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'birthcert');
    }

    public function pclearanceform()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'pclearanceform');
    }

    public function ssnit()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'ssnit');
    }
    
    public function ghcard()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'ghcard');
    }
    public function signature()
    {
        return $this->morphOne(Upload::class, 'uploadable')->where('type', 'signature');
    }

    public function guarsignature()
    {
        return $this->morphOne(Upload::class, 'uploadable')->where('type', 'guarsignature');
    }

    public function creator(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'histos');
    }

    public function lasthistory()
    {
        return $this->morphMany(History::class, 'histos')->take(10)->latest();
    }
}
