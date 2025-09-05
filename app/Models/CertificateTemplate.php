<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
   use HasFactory;

   protected $table = 'certificate_templates';
   protected $fillable = ['company_id', 'template_id', 'certificate_type', 'filepath', 'layout_name', 'selected'];
}
