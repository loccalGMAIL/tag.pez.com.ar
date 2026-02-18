<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'eretail_base_url',
        'eretail_username',
        'eretail_password',
        'eretail_default_shop_code',
        'eretail_db_host',
        'eretail_db_port',
        'eretail_db_database',
        'eretail_db_username',
        'eretail_db_password',
    ];

    protected $hidden = [
        'eretail_password',
        'eretail_db_password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function eretailPassword(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Crypt::decryptString($value) : null,
            set: fn($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function eretailDbPassword(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Crypt::decryptString($value) : null,
            set: fn($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function uploads()
    {
        return $this->hasMany(Upload::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function settings()
    {
        return $this->hasMany(AppSetting::class);
    }

    public function getERetailConfig(): array
    {
        return [
            'base_url'          => $this->eretail_base_url,
            'username'          => $this->eretail_username,
            'password'          => $this->eretail_password,
            'default_shop_code' => $this->eretail_default_shop_code,
            'timeout'           => 30,
            'retry_times'       => 3,
            'retry_delay'       => 1000,
        ];
    }

    public function getERetailDbConfig(): array
    {
        return [
            'driver'    => 'mysql',
            'host'      => $this->eretail_db_host,
            'port'      => $this->eretail_db_port ?? '3306',
            'database'  => $this->eretail_db_database,
            'username'  => $this->eretail_db_username,
            'password'  => $this->eretail_db_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
        ];
    }
}
