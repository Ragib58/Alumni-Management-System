<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\RoleType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\AlumniProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AlumniProfileRepositoryInterface $profiles,
        private readonly ActivityLogger $activity,
    ) {
    }

    /**
     * Register a new alumni member. A blank profile shell is created so the
     * user immediately appears in "edit profile" flows.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            /** @var User $user */
            $user = $this->users->create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'] ?? null,
                'password' => $data['password'],
                'status'   => UserStatus::Active->value,
            ]);

            $user->assignRole(RoleType::Alumni->value);

            // Seed a profile row (optionally with fields supplied at signup).
            $this->profiles->upsertForUser($user->id, [
                'student_id' => $data['student_id'] ?? null,
                'batch'      => $data['batch'] ?? null,
                'department' => $data['department'] ?? null,
                'session'    => $data['session'] ?? null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $this->activity->log(ActivityAction::Registration, 'New account registered', $user, $user);

            return [
                'user'  => $user->load(['roles:id,name', 'alumniProfile']),
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate by credentials and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is '.$user->status->value.'. Please contact an administrator.'],
            ]);
        }

        $token = $user->createToken($deviceName ?: 'auth_token')->plainTextToken;

        $this->activity->log(ActivityAction::Login, 'User logged in', $user, $user);

        return [
            'user'  => $user->load(['roles:id,name', 'alumniProfile']),
            'token' => $token,
        ];
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    /**
     * Send a password reset link.
     *
     * @return string One of the Password broker status constants.
     */
    public function sendResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }

    /**
     * Reset the password using a token from the emailed link.
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate existing sessions/tokens after a reset.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }

    /**
     * The currently authenticated user with roles + profile eager-loaded.
     */
    public function currentUser(User $user): User
    {
        return $user->load(['roles:id,name', 'permissions:id,name', 'alumniProfile']);
    }
}
