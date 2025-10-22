<?php

namespace App\Modules\Estates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Check if user is authenticated and is the owner
        $user = $request->user();
        $isOwner = $user && $user->id === $this->user_id;

        // Apply address privacy based on display_mode
        if (!$isOwner && isset($data['address_data'])) {
            $data['address_data'] = $this->filterAddressData($data['address_data']);
            $data['address'] = $this->filterAddress($data['address_data']);
            $data['zip'] = $this->filterZip($data['address_data']);
        }

        // Add user/owner information (sanitized for public display)
        if ($this->user) {
            $data['user'] = $this->getUserData($this->user);
        }

        return $data;
    }

    /**
     * Get sanitized user data for public display
     */
    private function getUserData($user): array
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            // Only show phone if user has enabled it
            'phone' => $user->show_phone ? $user->phone : null,
            // Don't expose email for privacy
            // Password is already hidden by User model
        ];

        // Add profile picture if exists
        if ($user->profilePicture) {
            $userData['profile_picture'] = [
                'url' => $user->profilePicture->url,
                'name' => $user->profilePicture->name,
            ];
        } else {
            $userData['profile_picture'] = null;
        }

        return $userData;
    }

    /**
     * Filter address data based on display_mode
     */
    private function filterAddressData(?array $addressData): ?array
    {
        if (!$addressData) {
            return null;
        }

        $displayMode = $addressData['display_mode'] ?? 'street';

        switch ($displayMode) {
            case 'exact':
                // Show everything including coordinates and exact address
                return $addressData;

            case 'street':
                // Show street but hide house number and coordinates
                return [
                    'zip' => $addressData['zip'] ?? null,
                    'street' => $addressData['street'] ?? null,
                    'house_number' => null,
                    'plot_number' => null,
                    'display_mode' => $displayMode,
                    'coordinates' => null,
                ];

            case 'street_only':
                // Show only street name, no zip or numbers
                return [
                    'zip' => null,
                    'street' => $addressData['street'] ?? null,
                    'house_number' => null,
                    'plot_number' => null,
                    'display_mode' => $displayMode,
                    'coordinates' => null,
                ];

            case 'city_only':
                // Hide everything, location_id relationship will show city
                return [
                    'zip' => null,
                    'street' => null,
                    'house_number' => null,
                    'plot_number' => null,
                    'display_mode' => $displayMode,
                    'coordinates' => null,
                ];

            default:
                return [
                    'zip' => $addressData['zip'] ?? null,
                    'street' => $addressData['street'] ?? null,
                    'house_number' => null,
                    'plot_number' => null,
                    'display_mode' => $displayMode,
                    'coordinates' => null,
                ];
        }
    }

    /**
     * Filter legacy address field
     */
    private function filterAddress(?array $addressData): ?string
    {
        if (!$addressData) {
            return null;
        }

        $displayMode = $addressData['display_mode'] ?? 'street';

        switch ($displayMode) {
            case 'exact':
                return $addressData['street'] ?? null;
            case 'street':
            case 'street_only':
                return $addressData['street'] ?? null;
            case 'city_only':
                return null;
            default:
                return $addressData['street'] ?? null;
        }
    }

    /**
     * Filter legacy zip field
     */
    private function filterZip(?array $addressData): ?string
    {
        if (!$addressData) {
            return null;
        }

        $displayMode = $addressData['display_mode'] ?? 'street';

        switch ($displayMode) {
            case 'exact':
            case 'street':
                return $addressData['zip'] ?? null;
            case 'street_only':
            case 'city_only':
                return null;
            default:
                return $addressData['zip'] ?? null;
        }
    }
}
