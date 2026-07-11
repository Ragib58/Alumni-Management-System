<?php

namespace App\Providers;

use App\Models\AlumniProfile;
use App\Models\User;
use App\Policies\AlumniProfilePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class          => UserPolicy::class,
        AlumniProfile::class => AlumniProfilePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
