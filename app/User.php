<?php

namespace App;

use App\Language;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    use SupportsTenantIsolation;

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_manage_id',
        'language_id',
        'create_by',
        'update_by',
        'delete_by',
        'business_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['deleted_at', 'email_verification_expires_at'];

    protected static function booted()
    {
        static::creating(function (User $user) {
            if ($user->role_manage_id) {
                $roleExists = RoleManage::withoutGlobalScopes()
                    ->whereKey($user->role_manage_id)
                    ->exists();

                if ($roleExists) {
                    return;
                }
            }

            $roleName = $user->email === config('app.super_admin_email', 'admin@gmail.com')
                ? 'Super Admin'
                : 'Admin';

            $role = RoleManage::withoutGlobalScopes()->where('name', $roleName)->first();

            if (!$role) {
                $role = new RoleManage([
                    'name' => $roleName,
                    'content' => '{}',
                ]);
                $role->save();
            }

            $user->role_manage_id = $role->id;
        });
    }

    public function profile()
    {
        return $this->hasOne('App\Profile');
    }

    public function business()
    {
        return $this->belongsTo(BusinessSetting::class, 'business_id');
    }

    public function tenantMemberships()
    {
        return $this->hasMany(TenantMembership::class);
    }
    public function role()
    {
        return $this->belongsTo('App\RoleManage', 'role_manage_id')
            ->withoutGlobalScopes();
    }

    /**
     * This function return assaign language 
     *
     * @author      Md. Al-Mahmud <mamun120520@gmail.com>
     * @version     1.0
     * @see         
     * @since       10/01/2022
     * Time         13:02:46
     * @param       
     * @return      
     */
    public function language()
    {
        # code...   
        return $this->belongsTo(Language::class, 'language_id');
    }
    #end

}
