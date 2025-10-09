<!DOCTYPE html>
<html>
<head>
    <title>Overall Platform Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; color: #333; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
        .content { padding: 20px; }
        .metrics-summary { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .metric-item { display: inline-block; margin: 10px 15px; text-align: center; }
        .metric-value { font-size: 20px; font-weight: bold; color: #007bff; }
        .metric-label { font-size: 12px; color: #6c757d; margin-top: 5px; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .footer { margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Overall Platform Report</h2>
        <h3>{{ $company_name }}</h3>
    </div>
    
    <div class="content">
        <p>Dear {{ $company_name }} Administrator,</p>

        <p>Your security report is attached.</p>

        <p>Please review the attached PDF for complete details.</p>
        
        <p>Best regards,<br><strong>{{ env('APP_NAME') }} Team</strong></p>
    </div>
    
    <div class="footer">
        <p>Automated report from {{ env('APP_NAME') }} Security Platform</p>
        <p>© {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.</p>
    </div>
</body>
</html>