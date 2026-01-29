<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',

        'billing_zip', 'billing_address', 'billing_city', 'billing_state',
        'billing_country','billing_address_two',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    // Wallet relationship
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Wallet transactions through wallet
    public function walletTransactions()
    {
        return $this->hasManyThrough(WalletTransaction::class, Wallet::class);
    }

    // QS Orders (Woohoo orders)
    public function qsOrders()
    {
        return $this->hasMany(QsOrder::class);
    }

    // KGen Orders
    public function kgenOrders()
    {
        return $this->hasMany(KGenOrder::class);
    }

    // CC Avenue Payments
    public function ccAvenuePayments()
    {
        return $this->hasMany(CcAvenuePayment::class);
    }

    // Unlimit Payments
    public function unlimitPayments()
    {
        return $this->hasMany(UnlimitPayment::class);
    }

    // All payments (union of both payment gateways)
    public function payments()
    {
        // This returns a collection, not a relationship
        // Use this in controllers when you need all payments
        return $this->ccAvenuePayments->merge($this->unlimitPayments);
    }

    // OTPs
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    // Email verification codes
    public function emailVerificationCodes()
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    // User IPs
    public function userIps()
    {
        return $this->hasMany(UserIp::class);
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->wallet()->create();
        });
    }
}
