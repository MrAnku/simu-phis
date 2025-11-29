<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomNotificationEmail extends Model
{
    protected $casts = [
        'status' => 'boolean',
    ];

    public function getTemplateContent(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        try {
            $s3 = Storage::disk('s3');
            $filePath = ltrim($this->file_path, '/');

            if ($s3->exists($filePath)) {
                return $s3->get($filePath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch custom notification template from S3: ' . $e->getMessage());
        }

        return null;
    }

    public static function getActiveTemplateForCompany($companyId): ?self
    {
        return self::where('company_id', $companyId)
            ->where('status', true)
            ->first();
    }
}
