<?php

namespace App\Providers;

use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\AlumniProfileRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\SponsorRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ActivityLogRepository;
use App\Repositories\Eloquent\AlumniProfileRepository;
use App\Repositories\Eloquent\AttendanceRepository;
use App\Repositories\Eloquent\EventRegistrationRepository;
use App\Repositories\Eloquent\EventRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\SponsorRepository;
use App\Repositories\Eloquent\TicketRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository contracts to their Eloquent implementations, enabling the
 * clean-architecture dependency inversion used by the Service layer.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class              => UserRepository::class,
        AlumniProfileRepositoryInterface::class     => AlumniProfileRepository::class,
        EventRepositoryInterface::class             => EventRepository::class,
        EventRegistrationRepositoryInterface::class => EventRegistrationRepository::class,
        PaymentRepositoryInterface::class           => PaymentRepository::class,
        TicketRepositoryInterface::class            => TicketRepository::class,
        AttendanceRepositoryInterface::class        => AttendanceRepository::class,
        SponsorRepositoryInterface::class           => SponsorRepository::class,
        ActivityLogRepositoryInterface::class       => ActivityLogRepository::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
