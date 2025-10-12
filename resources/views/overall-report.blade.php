<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Platform Security Report - {{ $company_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 40px 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Added page break controls to prevent content from being cut */
        .header,
        .metrics-grid,
        .metric-card,
        .chart-card,
        .risk-card,
        .alert-box,
        .recommendation-box,
        .campaign-section,
        .training-card,
        .policy-card {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .section-wrapper {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        /* </CHANGE> */
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 24px;
            /* Reduced padding-bottom from 30px to 16px to eliminate large empty space */
            padding-bottom: 16px;
            /* </CHANGE> */
            border-bottom: 2px solid #e2e8f0;
        }
        
        .header-icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            background: #3b82f6;
            border-radius: 12px;
            margin-bottom: 16px;
            position: relative;
        }
        
        /* Removed emoji and replaced with simple geometric shape for PDF compatibility */
        .header-icon::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 28px;
            border: 3px solid white;
            border-radius: 4px 4px 0 0;
            background: transparent;
        }
        
        .header-icon::after {
            content: "";
            position: absolute;
            top: 60%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
        }
        /* </CHANGE> */
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 14px;
            color: #64748b;
        }
        
        /* Metrics Grid */
        .metrics-grid {
            /* Replaced CSS Grid with inline-block for PDF compatibility */
            width: 100%;
            margin-bottom: 40px;
            font-size: 0; /* Remove whitespace between inline-block elements */
        }
        
        /* Added fallback for PDF renderers that don't support grid well */
        @media print {
            .metrics-grid {
                display: table;
                width: 100%;
                border-spacing: 20px 0;
            }
            
            .metric-card {
                display: table-cell;
                width: 25%;
            }
        }
        
        .metric-card {
            display: inline-block;
            width: 23%; /* Reduced from 23.5% to 23% to ensure proper spacing in PDF rendering */
            margin-right: 1.33%;
            margin-bottom: 20px;
            vertical-align: top;
            font-size: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            /* Added fixed height to KPI metric cards for consistency */
            min-height: 120px;
            height: 120px;
            padding: 16px;
            /* </CHANGE> */
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .metric-card:nth-child(4n) {
            margin-right: 0;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }
        
        .metric-card.blue::before { background: #3b82f6; }
        .metric-card.red::before { background: #ef4444; }
        .metric-card.purple::before { background: #a855f7; }
        .metric-card.green::before { background: #10b981; }
        
        .metric-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
            /* Added word wrapping for long labels */
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.2;
            /* </CHANGE> */
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .metric-subtitle {
            font-size: 11px;
            color: #94a3b8;
        }
        
        /* Charts Section */
        .charts-section {
            /* Replaced CSS Grid with inline-block for PDF compatibility */
            width: 100%;
            margin-bottom: 40px;
            font-size: 0;
        }
        
        .chart-card {
            display: inline-block;
            width: 49%;
            margin-right: 2%;
            vertical-align: top;
            font-size: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            box-sizing: border-box;
            /* Added page break controls */
            page-break-inside: avoid;
            break-inside: avoid;
            /* </CHANGE> */
        }
        
        .chart-card:nth-child(2n) {
            margin-right: 0;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .chart-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 24px;
        }
        
        /* Bar Chart */
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            gap: 12px;
            padding: 0 10px;
        }
        
        .bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .bar-container {
            width: 100%;
            display: flex;
            gap: 4px;
            align-items: flex-end;
            height: 160px;
        }
        
        .bar {
            flex: 1;
            border-radius: 4px 4px 0 0;
            position: relative;
        }
        
        .bar.blue { background: #3b82f6; }
        .bar.red { background: #ef4444; }
        
        .bar-label {
            font-size: 10px;
            color: #64748b;
            text-align: center;
            max-width: 60px;
            line-height: 1.2;
        }
        
        /* Pie Chart */
        .pie-chart {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            position: relative;
        }
        
        .pie-legend {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 11px;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        .legend-color.blue { background: #3b82f6; }
        .legend-color.green { background: #10b981; }
        .legend-color.orange { background: #f59e0b; }
        .legend-color.cyan { background: #06b6d4; }
        
        /* Detailed Risk Analysis */
        .risk-analysis {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .section-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 24px;
        }
        
        .risk-cards {
            /* Replaced CSS Grid with inline-block for PDF compatibility */
            width: 100%;
            font-size: 0;
        }
        
        .risk-card {
            display: inline-block;
            width: 23%; /* Reduced from 23.5% to 23% to ensure proper spacing in PDF rendering */
            margin-right: 1.33%;
            margin-bottom: 20px;
            vertical-align: top;
            font-size: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            box-sizing: border-box;
            /* Added fixed height and overflow handling to prevent card size changes from varying text lengths */
            min-height: 200px;
            height: 200px;
            /* </CHANGE> */
            /* Added page break controls */
            page-break-inside: avoid;
            break-inside: avoid;
            /* </CHANGE> */
        }
        
        .risk-card:nth-child(4n) {
            margin-right: 0;
        }
        
        .risk-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            /* Added fixed height for title area to prevent expansion */
            min-height: 40px;
            /* </CHANGE> */
        }
        
        .risk-card-title {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            /* Added word wrapping and overflow handling for long titles */
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 140px;
            line-height: 1.3;
            /* </CHANGE> */
        }
        
        .risk-badge {
            font-size: 9px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .risk-badge.danger {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .risk-badge.success {
            background: #d1fae5;
            color: #059669;
        }
        
        .risk-metric {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .risk-metric strong {
            color: #0f172a;
            font-weight: 600;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            background: #3b82f6;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Alert Box */
        .alert-box {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            /* Added page break controls */
            page-break-inside: avoid;
            break-inside: avoid;
            /* </CHANGE> */
        }
        
        .alert-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .alert-icon {
            color: #f59e0b;
            font-size: 18px;
        }
        
        .alert-title {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
        }
        
        .alert-message {
            font-size: 12px;
            color: #78350f;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .alert-metrics {
            /* Replaced CSS Grid with inline-block for PDF compatibility */
            width: 100%;
            font-size: 0;
        }
        
        .alert-metric {
            display: inline-block;
            width: 32%;
            margin-right: 2%;
            margin-bottom: 16px; /* Added margin-bottom for proper spacing when cards wrap to next row */
            vertical-align: top;
            font-size: 14px;
            background: white;
            padding: 12px;
            border-radius: 6px;
            box-sizing: border-box;
            /* Added page break controls */
            page-break-inside: avoid;
            break-inside: avoid;
            /* </CHANGE> */
        }
        
        .alert-metric:nth-child(3n) {
            margin-right: 0;
        }
        
        .alert-metric-label {
            font-size: 10px;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .alert-metric-value {
            font-size: 20px;
            font-weight: 700;
            color: #92400e;
        }
        
        /* Added styles for campaign sections and recommendation boxes */
        .campaign-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .campaign-metrics-row {
            width: 100%;
            margin-bottom: 16px;
            font-size: 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .campaign-metric-card {
            display: inline-block;
            vertical-align: top;
            font-size: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
            box-sizing: border-box;
            margin-bottom: 16px; /* Added margin-bottom for proper spacing when cards wrap to next row */
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .recommendation-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 24px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .training-card,
        .policy-card {
            display: inline-block;
            width: 49%;
            vertical-align: top;
            font-size: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            box-sizing: border-box;
            margin-bottom: 20px; /* Added margin-bottom for proper spacing when cards wrap to next row */
            page-break-inside: avoid;
            break-inside: avoid;
        }
        /* </CHANGE> */
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Updated header to Platform Security Report -->
        <div class="header">
            <div class="header-icon"></div>
            <h1>Platform Security Report</h1>
            <p>{{ $company_name }} - Comprehensive security analysis and risk assessment</p>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Generated on {{ date('F d, Y') }}</p>
        </div>
        
        <!-- Added Risk Score Section -->
        <!-- Reduced padding from 30px to 20px and margin-bottom from 30px to 24px to eliminate large empty space -->
        <div style="background: #810ce8ff; color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 24px; page-break-inside: avoid; break-inside: avoid;">
        <!-- </CHANGE> -->
            <h2 style="font-size: 18px; margin-bottom: 12px; font-weight: 600;">Overall Security Risk Score</h2>
            <div style="font-size: 56px; font-weight: 700; margin: 16px 0;">{{ $riskScore }}/100</div>
            @if($riskScore < 40)
                <!-- Replaced emoji with text for PDF compatibility -->
                <div style="display: inline-block; background: #dc2626; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                    HIGH RISK - IMMEDIATE ACTION REQUIRED
                </div>
                <!-- </CHANGE> -->
            @elseif($riskScore < 70)
                <!-- Replaced emoji with text for PDF compatibility -->
                <div style="display: inline-block; background: #f59e0b; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                    MODERATE RISK - IMPROVEMENT NEEDED
                </div>
                <!-- </CHANGE> -->
            @else
                <!-- Replaced emoji with text for PDF compatibility -->
                <div style="display: inline-block; background: #10b981; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                    LOW RISK - GOOD SECURITY POSTURE
                </div>
                <!-- </CHANGE> -->
            @endif
        </div>

        <!-- Added Alert Boxes -->
        @if($riskScore < 40)
            <!-- Replaced emoji with text for PDF compatibility -->
            <div class="alert-box" style="background: #fee2e2; border: 1px solid #fca5a5; border-left: 4px solid #dc2626;">
                <strong>CRITICAL ALERT:</strong> Your platform's risk score is critically low ({{ $riskScore }}/100). Immediate action is required to protect your organization from potential security breaches.
            </div>
            <!-- </CHANGE> -->
        @endif

        @if($click_rate > 30)
            <!-- Replaced emoji with text for PDF compatibility -->
            <div class="alert-box" style="background: #fee2e2; border: 1px solid #fca5a5; border-left: 4px solid #dc2626;">
                <strong>HIGH PHISHING CLICK RATE:</strong> Your platform has a <strong>{{ number_format($click_rate, 1) }}%</strong> phishing click rate. Immediate security awareness training is strongly recommended.
            </div>
            <!-- </CHANGE> -->
        @elseif($click_rate > 15)
            <!-- Replaced emoji with text for PDF compatibility -->
            <div class="alert-box" style="background: #fef3c7; border: 1px solid #fde047; border-left: 4px solid #f59e0b;">
                <strong>ELEVATED PHISHING CLICK RATE:</strong> Your phishing click rate of <strong>{{ number_format($click_rate, 1) }}%</strong> is above average. Consider implementing additional training programs.
            </div>
            <!-- </CHANGE> -->
        @else
            <!-- Replaced emoji with text for PDF compatibility -->
            <div class="alert-box" style="background: #d1fae5; border: 1px solid #86efac; border-left: 4px solid #10b981;">
                <strong>GOOD PHISHING AWARENESS:</strong> Your phishing click rate of {{ number_format($click_rate, 1) }}% is within acceptable limits.
            </div>
            <!-- </CHANGE> -->
        @endif

        @php
            // Email Threats data
            $email_attempts = $email_camp_data['total_attempts'] ?? 0;
            $email_compromised = $email_camp_data['compromised'] ?? 0;
            $email_risk_score = $email_attempts > 0 ? (($email_attempts - $email_compromised) / $email_attempts) * 100 : 100;
            $email_status = $email_risk_score < 70 ? 'AT RISK' : 'SECURE';
            
            // QR Code Phishing data
            $qr_attempts = $quish_camp_data['total_attempts'] ?? 0;
            $qr_compromised = $quish_camp_data['compromised'] ?? 0;
            $qr_risk_score = $qr_attempts > 0 ? (($qr_attempts - $qr_compromised) / $qr_attempts) * 100 : 100;
            $qr_status = $qr_risk_score < 70 ? 'AT RISK' : 'SECURE';
            
            // WhatsApp Threats data
            $whatsapp_attempts = $wa_camp_data['total_attempts'] ?? 0;
            $whatsapp_compromised = $wa_camp_data['compromised'] ?? 0;
            $whatsapp_risk_score = $whatsapp_attempts > 0 ? (($whatsapp_attempts - $whatsapp_compromised) / $whatsapp_attempts) * 100 : 100;
            $whatsapp_status = $whatsapp_risk_score < 70 ? 'AT RISK' : 'SECURE';
            
            // AI Voice Phishing data
            $ai_attempts = $ai_camp_data['total_attempts'] ?? 0;
            $ai_compromised = $ai_camp_data['compromised'] ?? 0;
            $ai_risk_score = $ai_attempts > 0 ? (($ai_attempts - $ai_compromised) / $ai_attempts) * 100 : 100;
            $ai_status = $ai_risk_score < 70 ? 'AT RISK' : 'SECURE';
            
            // Training and Policy rates
            $training_completion_rate = $training_assigned > 0 ? ($training_completed / $training_assigned) * 100 : 0;
            $policy_acceptance_rate = $assigned_Policies > 0 ? ($acceptance_Policies / $assigned_Policies) * 100 : 0;
        @endphp

        @if($policy_acceptance_rate < 80)
            <!-- Replaced emoji with text for PDF compatibility -->
            <div class="alert-box" style="background: #fef3c7; border: 1px solid #fde047; border-left: 4px solid #f59e0b;">
                <strong>LOW POLICY ACCEPTANCE:</strong> Only <strong>{{ number_format($policy_acceptance_rate, 1) }}%</strong> of assigned policies have been accepted.
            </div>
            <!-- </CHANGE> -->
        @endif
        
        <!-- Combined all 8 KPI metrics into a single container for proper 4-per-row layout -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Key Performance Indicators</h2>
            
            <!-- CHANGE> Increased margins for better visual spacing between cards -->
            <div style="width: 100%;">
                <!-- First Row -->
                <div style="margin-bottom: 30px;">
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Total Users</div>
                            <div class="metric-value">{{ number_format($total_users) }}</div>
                            <div class="metric-subtitle">Registered employees</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Campaigns Sent</div>
                            <div class="metric-value">{{ number_format($campaigns_sent) }}</div>
                            <div class="metric-subtitle">Security campaigns</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; box-sizing: border-box;">
                        <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Emails Sent</div>
                            <div class="metric-value">{{ number_format($emails_sent) }}</div>
                            <div class="metric-subtitle">Phishing simulations</div>
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                
                <!-- Second Row -->
                <div style="margin-bottom: 30px;">
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card red" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Payload Clicked</div>
                            <div class="metric-value">{{ number_format($payload_clicked) }}</div>
                            <div class="metric-subtitle">Malicious links clicked</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card {{ $click_rate > 20 ? 'red' : 'green' }}" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Click Rate</div>
                            <div class="metric-value">{{ number_format($click_rate, 1) }}%</div>
                            <div class="metric-subtitle">Users who clicked</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; box-sizing: border-box;">
                        <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Training Assigned</div>
                            <div class="metric-value">{{ number_format($training_assigned) }}</div>
                            <div class="metric-subtitle">Training modules</div>
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                
                <!-- Third Row -->
                <div style="margin-bottom: 30px;">
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Training Completed</div>
                            <div class="metric-value">{{ number_format($training_completed) }}</div>
                            <div class="metric-subtitle">Modules completed</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; margin-right: 8%; box-sizing: border-box;">
                        <div class="metric-card purple" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                            <div class="metric-label">Blue Collar Employees</div>
                            <div class="metric-value">{{ number_format($blue_collar_employees) }}</div>
                            <div class="metric-subtitle">Blue Collar Employees</div>
                        </div>
                    </div>
                    <div style="float: left; width: 26%; box-sizing: border-box;">
                        <!-- Empty space for visual balance -->
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
             </CHANGE> 
        </div>

        <!-- Updated Training & Policy Compliance Section to display cards horizontally -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Training & Policy Compliance</h2>
            
            <div style="width: 100%; font-size: 0;">
                <div style="display: inline-block; width: 49%; margin-right: 2%; vertical-align: top; font-size: 14px;">
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; page-break-inside: avoid; break-inside: avoid;">
                        <!-- Replaced emoji with checkmark symbol for PDF compatibility -->
                        <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center;">
                            <span style="display: inline-block; width: 32px; height: 32px; background: #10b981; border-radius: 50%; color: white; text-align: center; line-height: 32px; margin-right: 12px; font-size: 18px;">&#10003;</span>
                            Training Progress
                        </h3>
                        <!-- </CHANGE> -->
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; color: #64748b;">Completed</span>
                                <span style="font-size: 13px; font-weight: 600; color: #10b981;">{{ number_format($training_completed) }} ({{ number_format($training_completion_rate, 1) }}%)</span>
                            </div>
                            <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <div style="height: 100%; background: #10b981; width: {{ $training_completion_rate }}%;"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; color: #64748b;">Pending</span>
                                <span style="font-size: 13px; font-weight: 600; color: #ef4444;">{{ number_format($training_assigned - $training_completed) }} ({{ number_format(100 - $training_completion_rate, 1) }}%)</span>
                            </div>
                            <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <div style="height: 100%; background: #ef4444; width: {{ 100 - $training_completion_rate }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: inline-block; width: 49%; vertical-align: top; font-size: 14px;">
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; page-break-inside: avoid; break-inside: avoid;">
                        <!-- Replaced emoji with checkmark symbol for PDF compatibility -->
                        <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center;">
                            <span style="display: inline-block; width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; color: white; text-align: center; line-height: 32px; margin-right: 12px; font-size: 18px;">&#10003;</span>
                            Policy Acceptance
                        </h3>
                        <!-- </CHANGE> -->
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; color: #64748b;">Accepted</span>
                                <span style="font-size: 13px; font-weight: 600; color: #10b981;">{{ number_format($acceptance_Policies) }} ({{ number_format($policy_acceptance_rate, 1) }}%)</span>
                            </div>
                            <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <div style="height: 100%; background: #10b981; width: {{ $policy_acceptance_rate }}%;"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; color: #64748b;">Not Accepted</span>
                                <span style="font-size: 13px; font-weight: 600; color: #ef4444;">{{ number_format($assigned_Policies - $acceptance_Policies) }} ({{ number_format(100 - $policy_acceptance_rate, 1) }}%)</span>
                            </div>
                            <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <div style="height: 100%; background: #ef4444; width: {{ 100 - $policy_acceptance_rate }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


           <!-- Campaign Overview Section with KPI-style cards -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Campaign Overview</h2>
            
            <!-- Email Phishing Campaigns -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #464f64ff; margin-bottom: 16px;">Email Phishing Campaigns</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Simulated email phishing attacks to test employee awareness</p>
                
                <div style="width: 100%;">
                    <div style="margin-bottom: 20px;">
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Total Campaigns</div>
                                <div class="metric-value">{{ number_format($email_camp_data['email_campaign'] ?? 0) }}</div>
                                <div class="metric-subtitle">Email campaigns</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Emails Sent</div>
                                <div class="metric-value">{{ number_format($email_camp_data['email_sent'] ?? 0) }}</div>
                                <div class="metric-subtitle">Total emails sent</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Emails Viewed</div>
                                <div class="metric-value">{{ number_format($email_camp_data['email_viewed'] ?? 0) }}</div>
                                <div class="metric-subtitle">Emails opened</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card red" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Payload Clicked</div>
                                <div class="metric-value">{{ number_format($email_camp_data['payload_clicked'] ?? 0) }}</div>
                                <div class="metric-subtitle">Malicious links clicked</div>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>

            <!-- QR Code Phishing Campaigns -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px;">QR Code Phishing Campaigns</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">QR code-based phishing simulations to test security awareness</p>
                
                <div style="width: 100%;">
                    <div style="margin-bottom: 20px;">
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card purple" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Total Campaigns</div>
                                <div class="metric-value">{{ number_format($quish_camp_data['quishing_campaign'] ?? 0) }}</div>
                                <div class="metric-subtitle">QR campaigns</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Emails Sent</div>
                                <div class="metric-value">{{ number_format($quish_camp_data['email_sent'] ?? 0) }}</div>
                                <div class="metric-subtitle">QR emails sent</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Emails Viewed</div>
                                <div class="metric-value">{{ number_format($quish_camp_data['email_viewed'] ?? 0) }}</div>
                                <div class="metric-subtitle">Emails opened</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card red" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">QR Scanned</div>
                                <div class="metric-value">{{ number_format($quish_camp_data['qr_scanned'] ?? 0) }}</div>
                                <div class="metric-subtitle">QR codes scanned</div>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Phishing Campaigns -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px;">WhatsApp Phishing Campaigns</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Simulated WhatsApp phishing attacks via messaging platform</p>
                
                <div style="width: 100%;">
                    <div style="margin-bottom: 20px;">
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Total Campaigns</div>
                                <div class="metric-value">{{ number_format($wa_camp_data['whatsapp_campaign'] ?? 0) }}</div>
                                <div class="metric-subtitle">WhatsApp campaigns</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Messages Sent</div>
                                <div class="metric-value">{{ number_format($wa_camp_data['message_sent'] ?? 0) }}</div>
                                <div class="metric-subtitle">Total messages</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Messages Viewed</div>
                                <div class="metric-value">{{ number_format($wa_camp_data['message_viewed'] ?? 0) }}</div>
                                <div class="metric-subtitle">Messages read</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; box-sizing: border-box;">
                            <div class="metric-card red" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Links Clicked</div>
                                <div class="metric-value">{{ number_format($wa_camp_data['link_clicked'] ?? 0) }}</div>
                                <div class="metric-subtitle">Malicious links</div>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>

            <!-- AI Voice Phishing (Vishing) Campaigns -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px;">AI Voice Phishing (Vishing) Campaigns</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">AI-powered voice phishing attacks to test phone security awareness</p>
                
                <div style="width: 100%;">
                    <div style="margin-bottom: 20px;">
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card red" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Total Campaigns</div>
                                <div class="metric-value">{{ number_format($ai_camp_data['ai_vishing'] ?? 0) }}</div>
                                <div class="metric-subtitle">AI voice campaigns</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card blue" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Calls Sent</div>
                                <div class="metric-value">{{ number_format($ai_camp_data['calls_sent'] ?? 0) }}</div>
                                <div class="metric-subtitle">Total calls made</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; margin-right: 6.67%; box-sizing: border-box;">
                            <div class="metric-card green" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Calls Received</div>
                                <div class="metric-value">{{ number_format($ai_camp_data['calls_received'] ?? 0) }}</div>
                                <div class="metric-subtitle">Calls answered</div>
                            </div>
                        </div>
                        <div style="float: left; width: 19%; box-sizing: border-box;">
                            <div class="metric-card purple" style="margin: 0; padding: 16px; width: 100%; box-sizing: border-box;">
                                <div class="metric-label">Completed Calls</div>
                                <div class="metric-value">{{ number_format($ai_camp_data['completed_calls'] ?? 0) }}</div>
                                <div class="metric-subtitle">Full conversations</div>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Performance Rankings Section -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Employee Security Performance</h2>
            
            <div style="width: 100%; font-size: 0;">
                <!-- Most Compromised Employees -->
                <div style="display: inline-block; width: 32%; margin-right: 2%; vertical-align: top; font-size: 14px; box-sizing: border-box;">
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; page-break-inside: avoid; break-inside: avoid; min-height: 350px;">
                        <div style="display: flex; align-items: center; margin-bottom: 16px;">
                            <span style="display: inline-block; width: 24px; height: 24px; background: #dc2626; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 12px; font-weight: 700;">!</span>
                            <h3 style="font-size: 16px; font-weight: 600; color: #dc2626; margin: 0;">Most Compromised</h3>
                        </div>
                        
                        @php
                            // Sample data for most compromised employees - replace with actual data
                            $mostCompromised = [
                                ['name' => 'Amith', 'count' => 3, 'label' => 'TIMES'],
                                ['name' => 'Anjali', 'count' => 3, 'label' => 'TIMES'],
                                ['name' => 'tester_', 'count' => 3, 'label' => 'TIMES'],
                                ['name' => 'Hritik', 'count' => 3, 'label' => 'TIMES'],
                                ['name' => 'ujjawal', 'count' => 3, 'label' => 'TIMES'],
                            ];
                        @endphp
                        
                        @foreach($mostCompromised as $employee)
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; font-weight: 500; color: #374151;">{{ $employee['name'] }}</span>
                            <span style="font-size: 12px; font-weight: 700; color: #dc2626;">{{ $employee['count'] }} {{ $employee['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Most Clicked Employees -->
                <div style="display: inline-block; width: 32%; margin-right: 2%; vertical-align: top; font-size: 14px; box-sizing: border-box;">
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; page-break-inside: avoid; break-inside: avoid; min-height: 350px;">
                        <div style="display: flex; align-items: center; margin-bottom: 16px;">
                            <span style="display: inline-block; width: 24px; height: 24px; background: #f59e0b; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 12px; font-weight: 700;">⚡</span>
                            <h3 style="font-size: 16px; font-weight: 600; color: #f59e0b; margin: 0;">Most Clicked</h3>
                        </div>
                        
                        @php
                            // Sample data for most clicked employees - replace with actual data
                            $mostClicked = [
                                ['name' => 'Amith', 'count' => 3, 'label' => 'CLICKS'],
                                ['name' => 'Anjali', 'count' => 3, 'label' => 'CLICKS'],
                                ['name' => 'tester_', 'count' => 3, 'label' => 'CLICKS'],
                                ['name' => 'Hritik', 'count' => 3, 'label' => 'CLICKS'],
                                ['name' => 'ujjawal', 'count' => 3, 'label' => 'CLICKS'],
                            ];
                        @endphp
                        
                        @foreach($mostClicked as $employee)
                        <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 6px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; font-weight: 500; color: #374151;">{{ $employee['name'] }}</span>
                            <span style="font-size: 12px; font-weight: 700; color: #f59e0b;">{{ $employee['count'] }} {{ $employee['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Most Vigilant Employees -->
                <div style="display: inline-block; width: 32%; vertical-align: top; font-size: 14px; box-sizing: border-box;">
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; page-break-inside: avoid; break-inside: avoid; min-height: 350px;">
                        <div style="display: flex; align-items: center; margin-bottom: 16px;">
                            <span style="display: inline-block; width: 24px; height: 24px; background: #10b981; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 12px; font-weight: 700;">✓</span>
                            <h3 style="font-size: 16px; font-weight: 600; color: #10b981; margin: 0;">Most Vigilant</h3>
                        </div>
                        
                        @php
                            // Sample data for most vigilant employees - replace with actual data
                            $mostVigilant = [
                                ['name' => 'Amith', 'count' => 15, 'label' => 'IGNORED'],
                                ['name' => 'Anjali', 'count' => 15, 'label' => 'IGNORED'],
                                ['name' => 'tester_', 'count' => 15, 'label' => 'IGNORED'],
                                ['name' => 'Hritik', 'count' => 15, 'label' => 'IGNORED'],
                                ['name' => 'ujjawal', 'count' => 15, 'label' => 'IGNORED'],
                            ];
                        @endphp
                        
                        @foreach($mostVigilant as $employee)
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; font-weight: 500; color: #374151;">{{ $employee['name'] }}</span>
                            <span style="font-size: 12px; font-weight: 700; color: #10b981;">{{ $employee['count'] }} {{ $employee['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Added Detailed Risk Analysis section with campaign threat cards -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 4px;">Detailed Risk Analysis</h2>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 24px;">Comprehensive breakdown of each threat category with risk assessment</p>
            
            <!-- CHANGE> Changed from 4-column to 3-column layout for better alignment -->
            <table style="width: 98%; margin: 0 auto; border-collapse: collapse;">
                <tr>
                    <td style="width: 33.33%; padding: 0 12px 24px 0; vertical-align: top;">
                         <!-- Email Threats Card -->
                        <!-- Added fixed height inline style to ensure consistent card sizing -->
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-sizing: border-box; page-break-inside: avoid; break-inside: avoid; min-height: 200px; height: 200px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; min-height: 40px;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #0f172a; margin: 0; word-wrap: break-word; overflow-wrap: break-word; max-width: 140px; line-height: 1.3;">Email Threats</h3>
                                <span style="font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; background: {{ $email_status == 'AT RISK' ? '#fee2e2' : '#d1fae5' }}; color: {{ $email_status == 'AT RISK' ? '#dc2626' : '#059669' }}; white-space: nowrap;">{{ $email_status }}</span>
                            </div>
                            <!-- </CHANGE> -->
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Total Attempts:</span> {{ number_format($email_attempts) }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Compromised:</span> <span style="color: {{ $email_compromised > 0 ? '#dc2626' : '#059669' }}; font-weight: 600;">{{ number_format($email_compromised) }}</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
                                <span style="color: #0f172a; font-weight: 600;">Risk Score:</span> <span style="color: #3b82f6; font-weight: 700;">{{ number_format($email_risk_score, 2) }}/100</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #3b82f6; width: {{ $email_risk_score }}%;"></div>
                            </div>
                        </div>
                    </td>
                    <td style="width: 33.33%; padding: 0 12px 24px 12px; vertical-align: top;">
                         <!-- QR Code Phishing Card -->
                        <!-- Added fixed height inline style to ensure consistent card sizing -->
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-sizing: border-box; page-break-inside: avoid; break-inside: avoid; min-height: 200px; height: 200px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; min-height: 40px;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #0f172a; margin: 0; word-wrap: break-word; overflow-wrap: break-word; max-width: 140px; line-height: 1.3;">QR Code Phishing</h3>
                                <span style="font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; background: {{ $qr_status == 'AT RISK' ? '#fee2e2' : '#d1fae5' }}; color: {{ $qr_status == 'AT RISK' ? '#dc2626' : '#059669' }}; white-space: nowrap;">{{ $qr_status }}</span>
                            </div>
                            <!-- </CHANGE> -->
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Total Attempts:</span> {{ number_format($qr_attempts) }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Compromised:</span> <span style="color: {{ $qr_compromised > 0 ? '#dc2626' : '#059669' }}; font-weight: 600;">{{ number_format($qr_compromised) }}</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
                                <span style="color: #0f172a; font-weight: 600;">Risk Score:</span> <span style="color: #3b82f6; font-weight: 700;">{{ number_format($qr_risk_score, 2) }}/100</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #3b82f6; width: {{ $qr_risk_score }}%;"></div>
                            </div>
                        </div>
                    </td>
                    <td style="width: 33.33%; padding: 0 0 24px 12px; vertical-align: top;">
                         <!-- WhatsApp Threats Card -->
                        <!-- Added fixed height inline style to ensure consistent card sizing -->
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-sizing: border-box; page-break-inside: avoid; break-inside: avoid; min-height: 200px; height: 200px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; min-height: 40px;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #0f172a; margin: 0; word-wrap: break-word; overflow-wrap: break-word; max-width: 140px; line-height: 1.3;">WhatsApp Threats</h3>
                                <span style="font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; background: {{ $whatsapp_status == 'AT RISK' ? '#fee2e2' : '#d1fae5' }}; color: {{ $whatsapp_status == 'AT RISK' ? '#dc2626' : '#059669' }}; white-space: nowrap;">{{ $whatsapp_status }}</span>
                            </div>
                            <!-- </CHANGE> -->
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Total Attempts:</span> {{ number_format($whatsapp_attempts) }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Compromised:</span> <span style="color: {{ $whatsapp_compromised > 0 ? '#dc2626' : '#059669' }}; font-weight: 600;">{{ number_format($whatsapp_compromised) }}</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
                                <span style="color: #0f172a; font-weight: 600;">Risk Score:</span> <span style="color: #3b82f6; font-weight: 700;">{{ number_format($whatsapp_risk_score, 2) }}/100</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #3b82f6; width: {{ $whatsapp_risk_score }}%;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 33.33%; padding: 0 12px 0 0; vertical-align: top;">
                         <!-- AI Voice Phishing Card -->
                        <!-- Added fixed height inline style to ensure consistent card sizing -->
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-sizing: border-box; page-break-inside: avoid; break-inside: avoid; min-height: 200px; height: 200px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; min-height: 40px;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #0f172a; margin: 0; word-wrap: break-word; overflow-wrap: break-word; max-width: 140px; line-height: 1.3;">AI Voice Phishing</h3>
                                <span style="font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; background: {{ $ai_status == 'AT RISK' ? '#fee2e2' : '#d1fae5' }}; color: {{ $ai_status == 'AT RISK' ? '#dc2626' : '#059669' }}; white-space: nowrap;">{{ $ai_status }}</span>
                            </div>
                            <!-- </CHANGE> -->
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Total Attempts:</span> {{ number_format($ai_attempts) }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                <span style="color: #0f172a; font-weight: 600;">Compromised:</span> <span style="color: {{ $ai_compromised > 0 ? '#dc2626' : '#059669' }}; font-weight: 600;">{{ number_format($ai_compromised) }}</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
                                <span style="color: #0f172a; font-weight: 600;">Risk Score:</span> <span style="color: #3b82f6; font-weight: 700;">{{ number_format($ai_risk_score, 2) }}/100</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #3b82f6; width: {{ $ai_risk_score }}%;"></div>
                            </div>
                        </div>
                    </td>
            </table>
             </CHANGE> 
        </div>

        @php
            // Calculate total threats and compromised across all campaign types
            $total_threats = ($email_camp_data['total_attempts'] ?? 0) + 
                           ($quish_camp_data['total_attempts'] ?? 0) + 
                           ($wa_camp_data['total_attempts'] ?? 0) + 
                           ($ai_camp_data['total_attempts'] ?? 0);
            
            $total_compromised = ($email_camp_data['compromised'] ?? 0) + 
                               ($quish_camp_data['compromised'] ?? 0) + 
                               ($wa_camp_data['compromised'] ?? 0) + 
                               ($ai_camp_data['compromised'] ?? 0);
            
            // Calculate compromise rate and success rate
            $compromise_rate = $total_threats > 0 ? ($total_compromised / $total_threats) * 100 : 0;
            $success_rate = 100 - $compromise_rate;
            
            // Determine security status based on risk score
            if ($riskScore < 40) {
                $security_status = 'High Risk';
                $status_color = '#dc2626'; // red
                $bg_color = '#fee2e2'; // light red
                $border_color = '#fca5a5'; // red border
            } elseif ($riskScore < 70) {
                $security_status = 'Moderate Risk';
                $status_color = '#ea580c'; // orange
                $bg_color = '#ffedd5'; // light orange/peach
                $border_color = '#fdba74'; // orange border
            } else {
                $security_status = 'Low Risk';
                $status_color = '#059669'; // green
                $bg_color = '#d1fae5'; // light green
                $border_color = '#86efac'; // green border
            }
        @endphp

        <div class="section-wrapper" style="margin-bottom: 40px;">
            <div style="background: {{ $bg_color }}; border: 1px solid {{ $border_color }}; border-radius: 12px; padding: 24px; page-break-inside: avoid; break-inside: avoid;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: {{ $status_color }}; border-radius: 50%; color: white; text-align: center; line-height: 24px; font-weight: 700; font-size: 16px;">!</span>
                    <h2 style="font-size: 18px; font-weight: 600; color: {{ $status_color }}; margin: 0;">Security Status: {{ $security_status }}</h2>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <p style="font-size: 13px; color: {{ $status_color }}; line-height: 1.6; margin: 0;">
                        <strong>Security alert:</strong> {{ number_format($total_compromised) }} out of {{ number_format($total_threats) }} threats resulted in compromised attempts ({{ number_format($compromise_rate, 1) }}% compromise rate). Average risk score: {{ number_format($riskScore, 1) }}/100. Review and enhance security protocols to prevent future compromises.
                    </p>
                </div>
                
                 <!-- CHANGE> Set table width to 98% with auto margins to prevent overflow beyond page boundaries -->
                <table style="width: 98%; margin: 0 auto; border-collapse: collapse;">
                 </CHANGE> 
                    <tr>
                        <td style="width: 33.33%; padding: 0 12px 0 0; vertical-align: top;">
                            <div style="background: white; border: 1px solid {{ $border_color }}; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600;">Success Rate</div>
                                <div style="font-size: 32px; font-weight: 700; color: {{ $status_color }}; margin: 0;">{{ number_format($success_rate, 1) }}%</div>
                            </div>
                        </td>
                        <td style="width: 33.33%; padding: 0 12px 0 12px; vertical-align: top;">
                            <div style="background: white; border: 1px solid {{ $border_color }}; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600;">Avg Risk Score</div>
                                <div style="font-size: 32px; font-weight: 700; color: {{ $status_color }}; margin: 0;">{{ number_format($riskScore, 1) }}/100</div>
                            </div>
                        </td>
                        <td style="width: 33.33%; padding: 0 0 0 12px; vertical-align: top;">
                            <div style="background: white; border: 1px solid {{ $border_color }}; border-radius: 8px; padding: 16px; text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600;">Compromised</div>
                                <div style="font-size: 32px; font-weight: 700; color: {{ $status_color }}; margin: 0;">{{ number_format($total_compromised) }}/{{ number_format($total_threats) }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Added Recommendations Section -->
        <div class="section-wrapper" style="margin-bottom: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Recommendations & Action Items</h2>
            
            <div class="recommendation-box">
                <h3 style="font-size: 16px; font-weight: 600; color: #1e40af; margin-bottom: 16px;">Priority Actions:</h3>
                <ul style="margin-left: 24px; line-height: 1.8; font-size: 13px; color: #334155;">
                    @if($riskScore < 40)
                        <li><strong>URGENT:</strong> Your risk score is critically low. Schedule immediate security awareness training for all employees.</li>
                        <li>Conduct a comprehensive security audit to identify vulnerabilities.</li>
                    @endif
                    
                    @if($click_rate > 20)
                        <li><strong>HIGH PRIORITY:</strong> Implement mandatory phishing awareness training - your click rate of {{ number_format($click_rate, 1) }}% is dangerously high.</li>
                        <li>Consider implementing email filtering and anti-phishing tools.</li>
                    @endif
                    
                    @if($training_completion_rate < 70)
                        <li>Increase training completion rate from {{ number_format($training_completion_rate, 1) }}% to at least 90% through reminders and incentives.</li>
                    @endif
                    
                    @if($policy_acceptance_rate < 80)
                        <li>Ensure all employees review and accept security policies - current acceptance rate is only {{ number_format($policy_acceptance_rate, 1) }}%.</li>
                    @endif
                    
                    <li>Continue regular phishing simulations to maintain security awareness.</li>
                    <li>Review and update security policies quarterly to address emerging threats.</li>
                    <li>Implement multi-factor authentication (MFA) across all critical systems.</li>
                </ul>
            </div>

            @if($riskScore >= 70)
                <!-- Replaced emoji with checkmark symbol for PDF compatibility -->
                <div style="background: #d1fae5; border: 1px solid #86efac; border-left: 4px solid #10b981; border-radius: 8px; padding: 16px; margin-top: 20px; font-size: 13px; color: #065f46; page-break-inside: avoid; break-inside: avoid;">
                    <strong>EXCELLENT WORK:</strong> Your platform maintains a strong security posture with a risk score of {{ $riskScore }}/100. Continue your current security practices and stay vigilant.
                </div>
                <!-- </CHANGE> -->
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $company_name }}</strong> - Platform Security Report</p>
            <p>Generated on {{ date('F d, Y') }} | Confidential Information</p>
        </div>
    </div>
</body>
</html>
