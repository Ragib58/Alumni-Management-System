<?php

namespace App\Providers;

use App\Models\AlumniProfile;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\Sponsor;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\AlumniProfilePolicy;
use App\Policies\AttendancePolicy;
use App\Policies\EventPolicy;
use App\Policies\EventRegistrationPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\SponsorPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class              => UserPolicy::class,
        AlumniProfile::class     => AlumniProfilePolicy::class,
        Event::class             => EventPolicy::class,
        EventRegistration::class => EventRegistrationPolicy::class,
        Payment::class           => PaymentPolicy::class,
        Ticket::class            => TicketPolicy::class,
        Attendance::class        => AttendancePolicy::class,
        Sponsor::class           => SponsorPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
