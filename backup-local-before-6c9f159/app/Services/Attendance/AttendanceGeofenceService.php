<?php

namespace App\Services\Attendance;

use App\Models\Company;
use App\Models\User;
use RuntimeException;

class AttendanceGeofenceService
{
    public const OFFICE_RADIUS_METERS = 500;

    public function shouldApplyOfficeGeofence(User $user): bool
    {
        $roles = $user->roles
            ->pluck('name')
            ->map(fn ($role) => strtolower((string)$role))
            ->all();

        $remoteRoles = ['sales_staff', 'sales', 'marketing_staff', 'marketing'];

        return count(array_intersect($roles, $remoteRoles)) === 0;
    }

    public function validateClockInLocation(User $user, float $latitude, float $longitude): bool
    {
        if (!$this->shouldApplyOfficeGeofence($user)) {
            return true;
        }

        [$officeLatitude, $officeLongitude] = $this->officeCoordinates($user);

        return $this->distanceInMeters(
            $officeLatitude,
            $officeLongitude,
            $latitude,
            $longitude
        ) <= self::OFFICE_RADIUS_METERS;
    }

    public function officeCoordinates(User $user): array
    {
        $company = Company::with('defaultAddress')->findOrFail($user->company_id);

        $latitude = $company->defaultAddress?->latitude ?: $company->latitude;
        $longitude = $company->defaultAddress?->longitude ?: $company->longitude;

        if (blank($latitude) || blank($longitude)) {
            throw new RuntimeException('Office latitude/longitude is not configured.');
        }

        return [(float)$latitude, (float)$longitude];
    }

    public function distanceInMeters(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $earthRadius = 6371000;

        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) * sin($latitudeDelta / 2)
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude))
            * sin($longitudeDelta / 2) * sin($longitudeDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
