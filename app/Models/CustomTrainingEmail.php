<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CustomTrainingEmail extends Model
{

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the content of the custom template from S3
     */
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
            Log::error('Failed to fetch custom training template from S3: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get active custom template for a company
     */
    public static function getActiveTemplateForCompany($companyId): ?self
    {
        return self::where('company_id', $companyId)
                   ->where('status', true)
                   ->first();
    }
}
