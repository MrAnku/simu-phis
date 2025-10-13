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
            padding: 10px;
            line-height: 1.3;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 3px;
        }
        
        .header .date {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 8px;
        }
        
        /* Risk Score Section */
        .risk-score-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            min-height: 100px;
        }
        
        .risk-score-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding-right: 15px;
        }
        
        .alert-box {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: 15px 18px;
            height: 100px;
        }
        
        .risk-score-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }
        
        .circular-progress {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 15px auto;
            border-radius: 50%;
            background: #e2e8f0;
            border: 10px solid #ef4444;
            border-top-color: #e2e8f0;
            border-right-color: #e2e8f0;
            transform: rotate(-90deg);
        }
        
        .circular-progress::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            bottom: 12px;
            background: white;
            border-radius: 50%;
            z-index: 2;
        }
        
        .circular-progress svg {
            display: none;
        }
        
        .progress-circle {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 12;
        }
        
        .progress-circle.active {
            stroke: #ef4444;
            stroke-width: 12;
            stroke-linecap: round;
            fill: none;
        }
        
        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(90deg);
            text-align: center;
            z-index: 10;
        }
        
        .score-number {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }
        
        .score-total {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }
        
        /* Alert Box */
        .alert-title {
            font-size: 16px;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .alert-message {
            font-size: 13px;
            color: #78350f;
            line-height: 1.4;
        }
        
        /* Section Title */
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* KPI Grid */
        .kpi-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .kpi-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px 12px;
            text-align: left;
            position: relative;
            overflow: hidden;
            width: 100%;
            height: auto;
            min-height: 100px;
            max-height: 120px;
            box-sizing: border-box;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin: 0;
        }
        
        .kpi-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .kpi-value {
            font-size: 32px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .kpi-subtitle {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 6px;
        }
        
        .kpi-percentage {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            margin-top: 6px;
            display: inline-block;
        }
        
        .kpi-badge {
            background: #fef2f2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 6px;
            display: inline-block;
        }
        
        .kpi-badge.green { background: #d1fae5; color: #059669; }
        
        /* Two Column Layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .column-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
        }
        
        .column-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-secure {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-risk {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .metric-row:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            font-size: 13px;
            color: #64748b;
        }
        
        .metric-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.blue { background: #3b82f6; }
        .progress-fill.green { background: #10b981; }
        .progress-fill.red { background: #ef4444; }
        
        /* Recommendations Section */
        .recommendations {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
        }
        
        .recommendations h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
        }
        
        .recommendations ul {
            list-style: none;
            padding: 0;
        }
        
        .recommendations li {
            padding: 8px 0;
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            position: relative;
            padding-left: 20px;
        }
        
        .recommendations li::before {
            content: "•";
            color: #ef4444;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        
        /* Security Status */
        .security-status {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
        }
        
        .status-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .status-icon {
            width: 24px;
            height: 24px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        .status-card {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        
        .status-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .status-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
            font-size: 11px;
            color: #94a3b8;
        }
        
        /* Print Styles */
        @media print {
            body { padding: 0; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Platform Security Report</h1>
            <p>{{ $company_name ?? 'Company Name' }}</p>
            <p>Comprehensive security analysis and risk assessment</p>
            <div class="date">{{ date('F d, Y') }}</div>
        </div>

        <!-- Overall Security Risk Score -->
        <div class="risk-score-section">
            <div class="risk-score-left">
                <h2 class="risk-score-title">Overall Security Risk Score</h2>
                <div class="circular-progress">
                    <div class="score-text">
                        <div class="score-number">{{ $riskScore}}</div>
                        <div class="score-total">/100</div>
                    </div>
                </div>
            </div>
            
            @php
                $clickRate = $click_rate ?? 0;
                $alertType = $clickRate > 20 ? 'HIGH PHISHING CLICK RATE DETECTED' : 'ELEVATED SECURITY AWARENESS';
                $alertMessage = $clickRate > 20 ? 'Immediate employee training recommended' : 'Continue current security practices';
            @endphp
            
            <div class="alert-box">
                <div class="alert-title">{{ $alertType }}</div>
                <div class="alert-message">{{ $alertMessage }}</div>
                
                <!-- Security Summary -->
                <div style="margin-top: 18px; padding-top: 12px; border-top: 1px solid rgba(251, 146, 60, 0.3);">
                    <div style="font-size: 12px; color: #92400e; line-height: 1.5; margin-bottom: 12px;">
                        <strong>Current Status:</strong><br>
                        • Click Rate: {{ number_format($click_rate ?? 0, 1) }}% <br>
                        • High Risk Users: {{ number_format($payload_clicked ?? 0) }} employees
                    </div>
                    
                    <div style="background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 4px;">
                        <div style="font-size: 11px; color: #dc2626; font-weight: 600;">
                            Priority: {{ $clickRate > 20 ? 'HIGH' : 'MEDIUM' }} • Act within {{ $clickRate > 20 ? '3 days' : '1 week' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="section-title">Key Performance Indicators</div>
        
        <!-- First Row of KPI Cards -->
        <table style="width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 0;">
            <tr>
                <td style="width: 25%; padding: 0 8px 0 15px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">TOTAL USERS</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($total_users ?? 13) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Registered Employees</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">CAMPAIGNS SENT</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($campaigns_sent ?? 8) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Campaigns</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">PAYLOAD CLICKED</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($payload_clicked ?? 4) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Click rate</div>
                        <div style="font-size: 14px; color: #ef4444; font-weight: 600;">{{ number_format($click_rate ?? 22.2, 1) }}%</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 15px 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">TRAINING ASSIGNED</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($training_assigned ?? 10) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Total Assigned

</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Second Row of KPI Cards -->
        <table style="width: 100%; margin-bottom: 30px; border-collapse: separate; border-spacing: 0;">
            <tr>
                <td style="width: 25%; padding: 0 8px 0 15px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">POLICY ASSIGNED</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($assigned_Policies) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Total Assigned</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">TRAINING COMPLETED</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($training_completed) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Completed Trainings</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">AT RISK</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;"> {{ $riskScore }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Risk Score {{ $riskScore }}/100</div>
                        <div style="height: 16px;"></div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 15px 0 8px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; height: 120px; box-sizing: border-box;">
                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">Overall compromised</div>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ $totalCompromised}}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">Compromised users</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Training & Policy Compliance -->
        <div class="section-title">Training & Policy Compliance</div>
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- Training Progress Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        Training Progress
                        <span class="status-badge status-secure">SECURE</span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <span style="font-size: 13px; color: #64748b;">Completed <span style="color: #10b981; font-weight: 600;">2 (20.0%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill green" style="width: 20%;"></div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Pending <span style="color: #ef4444; font-weight: 600;">8 (80.0%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill red" style="width: 80%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Policy Acceptance Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        Policy Acceptance
                        <span class="status-badge status-secure">SECURE</span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <span style="font-size: 13px; color: #64748b;">Accepted <span style="color: #10b981; font-weight: 600;">2 (40.0%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill green" style="width: 40%;"></div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Not Accepted <span style="color: #ef4444; font-weight: 600;">3 (60.0%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill red" style="width: 60%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Threat Assessment Dashboard -->
        <div class="section-title" style="margin-top: 30px; margin-bottom: 20px;">Threat Assessment Dashboard</div>
        
        <!-- Threat Simulations Grid -->
        <!-- Row 1: WhatsApp Threats and AI Voice Phishing -->
        <div style="display: table; width: 100%; margin-bottom: 15px;">
            <!-- WhatsApp Threats Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        WhatsApp Threats
                        <span class="status-badge status-secure">SECURE</span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Simulations</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($wa_camp_data['total_attempts'] ?? 4) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($wa_camp_data['compromised'] ?? 0) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Risk Score</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format(20) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
            
            <!-- AI Voice Phishing Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        AI Voice Phishing
                        <span class="status-badge status-risk">AT RISK</span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Simulations</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($ai_camp_data['total_attempts'] ?? 7) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($ai_camp_data['compromised'] ?? 3) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Risk Score</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format(20) }}/10</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Email Threats and QR Code Threats -->
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- Email Threats Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        Email Threats
                        <span class="status-badge status-risk">AT RISK</span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Simulations</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($campaigns_sent ?? 8) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($payload_clicked ?? 4) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Risk Score</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($click_rate ?? 22.2, 1) }}%</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
            
            <!-- QR Code Threats Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        QR Code Threats
                        <span class="status-badge status-secure">SECURE</span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Simulations</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($quish_camp_data['total_attempts'] ?? 800) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format($quish_camp_data['compromised'] ?? 0) }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Risk Score</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ number_format(100) }}/100</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations & Action Items -->
        <div class="section-title">Recommendations & Action Items</div>
        <div style="display: table; width: 100%; margin-bottom: 30px; min-height: 250px;">
            <!-- Recommendations Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="recommendations" style="min-height: 250px; display: flex; flex-direction: column;">
                    <h3>Priority Actions:</h3>
                    <ul style="flex-grow: 1; margin: 0; padding-left: 20px;">
                        @php
                            $riskScore = $riskScore ?? 61.1;
                            $clickRate = $click_rate ?? 4.0;
                            $trainingCompleted = $training_completed ?? 0;
                            $trainingAssigned = $training_assigned ?? 8;
                            $compromised = $totalCompromised ?? 3;
                        @endphp
                        
                        @if($riskScore > 70)
                            <li><strong>CRITICAL:</strong> Immediate security review and incident response</li>
                            <li><strong>URGENT:</strong> Conduct mandatory security awareness training for all users</li>
                            <li>Implement additional security controls and monitoring</li>
                            <li>Review and update security policies immediately</li>
                        @elseif($riskScore > 50)
                            <li><strong>URGENT:</strong> Conduct mandatory phishing awareness training</li>
                            @if($clickRate > 10)
                                <li>Investigate and address the causes of high click rates</li>
                            @endif
                            @if($trainingCompleted < $trainingAssigned)
                                <li>Complete pending security training assignments</li>
                            @endif
                            <li>Improve policy acceptance through targeted communication</li>
                        @elseif($riskScore > 30)
                            <li>Review and update training content quarterly</li>
                            @if($compromised > 0)
                                <li>Monitor users who clicked on simulated phishing attempts</li>
                            @endif
                            <li>Enhance security policy communication</li>
                        @else
                            <li>Maintain current security training schedule</li>
                            <li>Continue quarterly security assessments</li>
                            <li>Monitor for emerging threats and update protocols</li>
                            <li>Recognize and reward security-conscious behavior</li>
                        @endif
                    </ul>
                </div>
            </div>
            
            <!-- Security Status Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="security-status" style="min-height: 250px; display: flex; flex-direction: column;">
                    <div class="status-header">
                        <div class="status-icon">!</div>
                        <h3>Security Status</h3>
                    </div>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">
                        Security alert: {{ number_format($totalCompromised ?? 3) }} of {{ number_format(5300) }} total
                        Reduce risk flag
                    </p>
                    
                    <!-- Horizontal Security Metrics -->
                    <div style="display: table; width: 100%; margin-top: auto;">
                        <div style="display: table-cell; width: 50%; text-align: center; vertical-align: top;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">AVG RISK</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($riskScore ?? 61.1, 1) }}</div>
                        </div>
                        <div style="display: table-cell; width: 50%; text-align: center; vertical-align: top;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">COMPROMISED</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($totalCompromised) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Report Section -->
        <div style="page-break-before: auto; margin-top: 40px;">
            <div class="section-title">Employee Report</div>
            
            <!-- First row: Training Analysis and Average Training Scores -->
            <div style="display: table; width: 100%; margin-bottom: 30px;">
                <!-- Training Analysis Section -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 20px;">
                    <div class="column-card" style="min-height: 300px; text-align: center;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                            <span style="margin-right: 10px;">🎯</span>Training Analysis
                        </h3>
                        
                        <!-- Simple Border-based Donut Chart -->
                        <div style="margin: 20px auto; text-align: center;">
                            @php
                                $trainingAssigned = $training_assigned ?? 10;
                                $trainingStarted = $totalTrainingStarted ?? 5;
                                $trainingCompleted = $training_completed ?? 2;
                                
                                $total = max($trainingAssigned, 1);
                                $remaining = $trainingAssigned - $trainingStarted - $trainingCompleted;
                            @endphp
                            
                            <!-- Donut using individual border segments -->
                            <div style="width: 90px; height: 90px; margin: 0 auto; position: relative;">
                                <!-- Base circle with light border -->
                                <div style="width: 90px; height: 90px; border: 15px solid #f1f5f9; border-radius: 50%; background: white;"></div>
                                
                                <!-- Overlay colored segments -->
                                <div style="position: absolute; top: 0; left: 0; width: 90px; height: 90px;">
                                    @if($trainingCompleted > 0)
                                    <!-- Green segment for completed (top quarter) -->
                                    <div style="width: 90px; height: 90px; border: 15px solid transparent; border-top: 15px solid #10b981; border-radius: 50%; position: absolute;"></div>
                                    @endif
                                    
                                    @if($trainingStarted > 0)
                                    <!-- Orange segment for started (right quarter) -->
                                    <div style="width: 90px; height: 90px; border: 15px solid transparent; border-right: 15px solid #fb923c; border-radius: 50%; position: absolute;"></div>
                                    @endif
                                    
                                    @if($remaining > 0)
                                    <!-- Blue segments for remaining (bottom and left quarters) -->
                                    <div style="width: 90px; height: 90px; border: 15px solid transparent; border-bottom: 15px solid #3b82f6; border-left: 15px solid #3b82f6; border-radius: 50%; position: absolute;"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Legend -->
                        <div style="margin: 20px 0; text-align: left;">
                            <div style="display: table; width: 100%; margin-bottom: 8px;">
                                <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                    <div style="width: 12px; height: 12px; background: #3b82f6; border-radius: 50%;"></div>
                                </div>
                                <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                    <span style="font-size: 12px; color: #64748b;">Assigned: {{ number_format($trainingAssigned) }}</span>
                                </div>
                            </div>
                            <div style="display: table; width: 100%; margin-bottom: 8px;">
                                <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                    <div style="width: 12px; height: 12px; background: #fb923c; border-radius: 50%;"></div>
                                </div>
                                <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                    <span style="font-size: 12px; color: #64748b;">Started: {{ number_format($trainingStarted) }}</span>
                                </div>
                            </div>
                            <div style="display: table; width: 100%; margin-bottom: 8px;">
                                <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                    <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></div>
                                </div>
                                <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                    <span style="font-size: 12px; color: #64748b;">Completed: {{ number_format($trainingCompleted) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bottom Metrics -->
                        <div style="display: table; width: 100%; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <div style="display: table-cell; width: 50%; text-align: center;">
                                <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Badges Earned</div>
                                <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($totalBadgesAssigned ?? 37) }}</div>
                            </div>
                            <div style="display: table-cell; width: 50%; text-align: center;">
                                <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Certified</div>
                                <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($certifiedUsers ?? 28) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Average Training Scores Section -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 20px;">
                    <div class="column-card" style="min-height: 300px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Average Training Scores</h3>
                        
                        @php
                            $avgScores = $avg_scores ?? [];
                            $staticScore = $avgScores['static'] ?? 24;
                            $conversationalScore = $avgScores['conversational'] ?? 2;
                            $gamifiedScore = $avgScores['gamified'] ?? 6;
                            $aiScore = $avgScores['ai'] ?? 30;
                            
                            $maxScore = max($staticScore, $conversationalScore, $gamifiedScore, $aiScore);
                            $maxScore = $maxScore > 0 ? $maxScore : 32; // Prevent division by zero
                        @endphp
                        
                        <!-- Chart Container -->
                        <div style="position: relative; height: 180px; margin: 20px 0;">
                            <!-- Y-axis labels -->
                            <div style="position: absolute; left: 0; top: 0; width: 30px; height: 150px;">
                                <div style="position: relative; height: 100%;">
                                    <div style="position: absolute; top: 0; right: 5px; font-size: 10px; color: #64748b;">32</div>
                                    <div style="position: absolute; top: 25%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">24</div>
                                    <div style="position: absolute; top: 50%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">16</div>
                                    <div style="position: absolute; top: 75%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">8</div>
                                    <div style="position: absolute; bottom: 0; right: 5px; font-size: 10px; color: #64748b;">0</div>
                                </div>
                            </div>
                            
                            <!-- Chart area -->
                            <div style="position: absolute; left: 35px; top: 0; right: 0; height: 150px;">
                                <div style="display: table; width: 100%; height: 150px; table-layout: fixed;">
                                    <!-- Static -->
                                    <div style="display: table-cell; width: 25%; vertical-align: bottom; text-align: center; padding: 0 5px;">
                                        @php $barHeight = max(($staticScore / 32) * 150, 2); @endphp
                                        <div style="background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative;">
                                            @if($staticScore > 0)
                                                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $staticScore }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Conversational -->
                                    <div style="display: table-cell; width: 25%; vertical-align: bottom; text-align: center; padding: 0 5px;">
                                        @php $barHeight = max(($conversationalScore / 32) * 150, 2); @endphp
                                        <div style="background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative;">
                                            @if($conversationalScore > 0)
                                                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $conversationalScore }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Gamified -->
                                    <div style="display: table-cell; width: 25%; vertical-align: bottom; text-align: center; padding: 0 5px;">
                                        @php $barHeight = max(($gamifiedScore / 32) * 150, 2); @endphp
                                        <div style="background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative;">
                                            @if($gamifiedScore > 0)
                                                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $gamifiedScore }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- AI -->
                                    <div style="display: table-cell; width: 25%; vertical-align: bottom; text-align: center; padding: 0 5px;">
                                        @php $barHeight = max(($aiScore / 32) * 150, 2); @endphp
                                        <div style="background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative;">
                                            @if($aiScore > 0)
                                                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $aiScore }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- X-axis labels -->
                            <div style="position: absolute; left: 35px; bottom: 0; right: 0; height: 20px;">
                                <div style="display: table; width: 100%; table-layout: fixed;">
                                    <div style="display: table-cell; width: 25%; text-align: center;">
                                        <span style="font-size: 10px; color: #64748b; font-weight: 500;">Static</span>
                                    </div>
                                    <div style="display: table-cell; width: 25%; text-align: center;">
                                        <span style="font-size: 10px; color: #64748b; font-weight: 500;">Conversational</span>
                                    </div>
                                    <div style="display: table-cell; width: 25%; text-align: center;">
                                        <span style="font-size: 10px; color: #64748b; font-weight: 500;">Gamified</span>
                                    </div>
                                    <div style="display: table-cell; width: 25%; text-align: center;">
                                        <span style="font-size: 10px; color: #64748b; font-weight: 500;">AI</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second row: Risk Distribution -->
            <div style="display: table; width: 100%; margin-bottom: 30px;">
                <!-- Risk Chart Section -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                    <div class="column-card" style="min-height: 350px; text-align: center;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                            <span style="margin-right: 10px;">🎯</span>Risk Distribution
                        </h3>
                            
                            @php
                                $riskData = $riskAnalysis ?? ['high_risk' => 0, 'moderate_risk' => 14, 'low_risk' => 1];
                                $highRisk = $riskData['high_risk'] ?? 0;
                                $moderateRisk = $riskData['moderate_risk'] ?? 14;
                                $lowRisk = $riskData['low_risk'] ?? 1;
                                $totalRisk = max(($highRisk + $moderateRisk + $lowRisk), 1);
                            @endphp
                            
                            <!-- Risk Distribution Donut Chart -->
                            <div style="margin: 20px auto; text-align: center;">
                                <!-- Donut using individual border segments -->
                                <div style="width: 90px; height: 90px; margin: 0 auto; position: relative;">
                                    <!-- Base circle with light border -->
                                    <div style="width: 90px; height: 90px; border: 15px solid #f1f5f9; border-radius: 50%; background: white;"></div>
                                    
                                    <!-- Overlay colored segments based on proportions -->
                                    <div style="position: absolute; top: 0; left: 0; width: 90px; height: 90px;">
                                        @php
                                            // Calculate proportions for proper donut representation
                                            $totalRiskCalc = max(($highRisk + $moderateRisk + $lowRisk), 1);
                                            $moderatePercentage = ($moderateRisk / $totalRiskCalc) * 100;
                                            $lowPercentage = ($lowRisk / $totalRiskCalc) * 100;
                                            $highPercentage = ($highRisk / $totalRiskCalc) * 100;
                                        @endphp
                                        
                                        @if($moderateRisk > 0)
                                        <!-- Orange segment for moderate risk - largest portion (about 93%) -->
                                        <div style="width: 90px; height: 90px; border: 15px solid transparent; border-top: 15px solid #fb923c; border-right: 15px solid #fb923c; border-bottom: 15px solid #fb923c; border-left: 15px solid #fb923c; border-radius: 50%; position: absolute;"></div>
                                        @endif
                                        
                                        @if($lowRisk > 0)
                                        <!-- Green segment for low risk - small portion (about 7%) -->
                                        <div style="width: 90px; height: 90px; border: 15px solid transparent; border-right: 15px solid #10b981; border-radius: 50%; position: absolute; transform: rotate(45deg);"></div>
                                        @endif
                                        
                                        @if($highRisk > 0)
                                        <!-- Red segment for high risk -->
                                        <div style="width: 90px; height: 90px; border: 15px solid transparent; border-top: 15px solid #ef4444; border-radius: 50%; position: absolute;"></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Risk Legend -->
                            <div style="margin: 20px 0; text-align: left;">
                                <div style="display: table; width: 100%; margin-bottom: 8px;">
                                    <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                        <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%;"></div>
                                    </div>
                                    <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                        <span style="font-size: 12px; color: #64748b;">High Risk: {{ number_format($highRisk) }}</span>
                                    </div>
                                </div>
                                <div style="display: table; width: 100%; margin-bottom: 8px;">
                                    <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                        <div style="width: 12px; height: 12px; background: #fb923c; border-radius: 50%;"></div>
                                    </div>
                                    <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                        <span style="font-size: 12px; color: #64748b;">Moderate Risk: {{ number_format($moderateRisk) }}</span>
                                    </div>
                                </div>
                                <div style="display: table; width: 100%; margin-bottom: 8px;">
                                    <div style="display: table-cell; width: 20px; vertical-align: middle;">
                                        <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></div>
                                    </div>
                                    <div style="display: table-cell; vertical-align: middle; padding-left: 8px;">
                                        <span style="font-size: 12px; color: #64748b;">Low Risk: {{ number_format($lowRisk) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <!-- Risk Analysis Text -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                    <div class="column-card" style="min-height: 350px;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">Employee Risk Analysis</h4>
                        
                        <p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 15px;">
                            Risk distribution categorizes employees based on their susceptibility to phishing attacks and security threats. This analysis helps identify training priorities and security focus areas.
                        </p>
                        
                        <div style="margin-bottom: 15px;">
                            <h5 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Risk Categories:</h5>
                            <ul style="font-size: 12px; color: #475569; line-height: 1.5; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 5px;"><strong style="color: #ef4444;">High Risk:</strong> Frequent security incidents, multiple compromises</li>
                                <li style="margin-bottom: 5px;"><strong style="color: #fb923c;">Moderate Risk:</strong> Occasional incidents, needs targeted training</li>
                                <li style="margin-bottom: 5px;"><strong style="color: #10b981;">Low Risk:</strong> Excellent security awareness, rare incidents</li>
                            </ul>
                        </div>
                        
                        <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 4px solid #3b82f6;">
                            <p style="font-size: 12px; color: #1e293b; margin: 0; font-weight: 500;">
                                <strong>Security Insight:</strong> Focus training resources on high and moderate risk employees to maximize security improvement across the organization.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Third row: Most Compromised and Most Clicked -->
            <div style="display: table; width: 100%; margin-bottom: 30px;">
                <!-- Most Compromised Section -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 20px;">
                    <div class="column-card" style="min-height: 300px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">
                            <span style="margin-right: 10px; color: #ef4444;">⚠</span>Most Compromised
                        </h3>
                        
                        @php
                            $mostCompromised = $most_compromised_employees ?? [
                                ['employee_name' => 'Amith', 'compromised' => 4],
                                ['employee_name' => 'Anjali', 'compromised' => 4],
                                ['employee_name' => 'tester_', 'compromised' => 4],
                                ['employee_name' => 'Hritik', 'compromised' => 4],
                                ['employee_name' => 'ujjawal', 'compromised' => 4]
                            ];
                        @endphp
                        
                        <div style="margin: 15px 0;">
                            @foreach($mostCompromised as $index => $employee)
                                @if($index < 5)
                                <div style="display: table; width: 100%; margin-bottom: 12px; padding: 12px; background: #fef2f2; border-radius: 6px; border-left: 3px solid #ef4444;">
                                    <div style="display: table-cell; width: 70%; vertical-align: middle;">
                                        <span style="font-size: 14px; font-weight: 600; color: #1e293b;">{{ $employee['employee_name'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div style="display: table-cell; width: 30%; vertical-align: middle; text-align: right;">
                                        <span style="font-size: 12px; font-weight: 600; color: #ef4444;">{{ $employee['compromised'] ?? 0 }} TIMES</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Most Clicked Section -->
                <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 20px;">
                    <div class="column-card" style="min-height: 300px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">
                            <span style="margin-right: 10px; color: #f59e0b;">👆</span>Most Clicked
                        </h3>
                        
                        @php
                            // For Most Clicked, we'll use the same data structure
                            $mostClicked = $most_compromised_employees ?? [
                                ['employee_name' => 'Amith', 'compromised' => 4],
                                ['employee_name' => 'Anjali', 'compromised' => 4],
                                ['employee_name' => 'tester_', 'compromised' => 4],
                                ['employee_name' => 'Hritik', 'compromised' => 4],
                                ['employee_name' => 'ujjawal', 'compromised' => 4]
                            ];
                        @endphp
                        
                        <div style="margin: 15px 0;">
                            @foreach($mostClicked as $index => $employee)
                                @if($index < 5)
                                <div style="display: table; width: 100%; margin-bottom: 12px; padding: 12px; background: #fffbeb; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                    <div style="display: table-cell; width: 70%; vertical-align: middle;">
                                        <span style="font-size: 14px; font-weight: 600; color: #1e293b;">{{ $employee['employee_name'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div style="display: table-cell; width: 30%; vertical-align: middle; text-align: right;">
                                        <span style="font-size: 12px; font-weight: 600; color: #f59e0b;">{{ $employee['compromised'] ?? 0 }} CLICKS</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Email Simulation Analytics -->
        <div style="page-break-before: auto; margin-top: 40px;">
            <div class="section-title">Weekly Email Simulation Analytics</div>
            
            <div style="display: table; width: 100%; margin-bottom: 30px;">
                <!-- Overview Section -->
                <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 20px;">
                    <div class="column-card" style="min-height: 200px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">Overview</h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 15px;">
                            Comprehensive phishing simulation conducted across all departments to assess employee security awareness and response to phishing attempts. This report provides insights into employee behavior patterns, identifies high-risk areas, and recommends targeted training initiatives.
                        </p>
                        
                        <!-- Phishing Rate Chart -->
                        <div style="margin-top: 25px;">
                            <h4 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">Email Click Rate Over Time</h4>
                            <div style="position: relative; height: 200px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; background: #ffffff;">
                                @php
                                    $weeklyClicks = $phish_clicks_weekly ?? [];
                                    // Ensure weeklyClicks is an array and extract percentage values
                                    if (!is_array($weeklyClicks)) {
                                        $weeklyClicks = [];
                                    }
                                    
                                    // Extract percentage values from the array structure
                                    $weeklyClicksArray = [];
                                    for ($i = 0; $i < 7; $i++) {
                                        if (isset($weeklyClicks[$i]['percentage'])) {
                                            $weeklyClicksArray[$i] = (float)$weeklyClicks[$i]['percentage'];
                                        } else {
                                            $weeklyClicksArray[$i] = 0;
                                        }
                                    }
                                    
                                    $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                @endphp
                                
                                <!-- Chart container -->
                                <div style="position: relative; height: 160px;">
                                    <!-- Y-axis labels -->
                                    <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                        <div style="position: relative; height: 100%;">
                                            <div style="position: absolute; top: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">100%</div>
                                            <div style="position: absolute; top: 25%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">75%</div>
                                            <div style="position: absolute; top: 50%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">50%</div>
                                            <div style="position: absolute; top: 75%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">25%</div>
                                            <div style="position: absolute; bottom: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">0%</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chart area -->
                                    <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                        <!-- Grid lines -->
                                        <div style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;">
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 0;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 25%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 50%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 75%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 100%;"></div>
                                        </div>
                                        
                                        <!-- Chart bars container -->
                                        <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                            @foreach($weekDays as $index => $day)
                                                @php
                                                    $percentage = $weeklyClicksArray[$index] ?? 0;
                                                    $barHeight = ($percentage / 100) * 140;
                                                @endphp
                                                <div style="display: table-cell; width: 14.28%; vertical-align: bottom; text-align: center;">
                                                    @if($percentage > 0)
                                                        <div style="background: #fb923c; width: 24px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative;">
                                                            <div style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #1e293b; font-weight: 600; white-space: nowrap;">{{ $percentage }}%</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <!-- X-axis labels -->
                                    <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                        <div style="display: table; width: 100%; table-layout: fixed;">
                                            @foreach($weekDays as $day)
                                                <div style="display: table-cell; width: 14.28%; text-align: center;">
                                                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $day }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Section -->
                <div style="display: table-cell; width: 40%; vertical-align: top; padding-left: 20px;">
                    <div class="column-card" style="min-height: 200px; text-align: center;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Summary</h3>
                        
                        <!-- Click Rate -->
                        <div style="margin-bottom: 25px;">
                            <div style="font-size: 36px; font-weight: 700; color: #ef4444; line-height: 1;">{{ number_format($click_rate) }}%</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 5px;">Click Rate</div>
                        </div>
                        
                        <!-- Total Simulations -->
                        <div style="margin-bottom: 25px;">
                            <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1;">{{ number_format($campaigns_sent) }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 5px;">Total Simulations</div>
                        </div>
                        
                        <!-- High Risk Users -->
                        <div>
                            <div style="font-size: 36px; font-weight: 700; color: #f59e0b; line-height: 1;">{{ number_format($payload_clicked) }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 5px;">High-risk Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Vishing Report -->
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- AI Chart Section -->
            <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 15px;">
                <div class="column-card" style="min-height: 350px; text-align: center;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                        <span style="margin-right: 10px;">🤖</span>AI Vishing Fell For Simulation Rate Over Time
                    </h3>
                        
                        @php
                            $aiData = $ai_events_over_time ?? [];
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            $aiSuccessRates = array_fill(0, 12, 0);
                            $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            
                            // Process AI data to extract monthly success rates
                            if (!empty($aiData)) {
                                foreach ($aiData as $monthData) {
                                    if (isset($monthData['month']) && isset($monthData['fellForSimulationRate'])) {
                                        // Extract month name from "Month YYYY" format
                                        $monthName = explode(' ', $monthData['month'])[0] ?? '';
                                        $monthIndex = array_search($monthName, $fullMonths);
                                        if ($monthIndex !== false) {
                                            $aiSuccessRates[$monthIndex] = floatval($monthData['fellForSimulationRate']);
                                        }
                                    }
                                }
                            }
                            
                            $maxAiRate = max($aiSuccessRates) ?: 10; // Minimum scale of 10%
                        @endphp
                        
                        <!-- AI Chart -->
                        <div style="margin: 30px auto 20px; text-align: center;">
                            <div style="position: relative; height: 200px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; background: #ffffff;">
                                
                                <!-- Chart container -->
                                <div style="position: relative; height: 160px;">
                                    <!-- Y-axis labels -->
                                    <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                        <div style="position: relative; height: 100%;">
                                            <div style="position: absolute; top: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">100%</div>
                                            <div style="position: absolute; top: 25%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">75%</div>
                                            <div style="position: absolute; top: 50%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">50%</div>
                                            <div style="position: absolute; top: 75%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">25%</div>
                                            <div style="position: absolute; bottom: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">0%</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chart area -->
                                    <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                        <!-- Grid lines -->
                                        <div style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;">
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 0;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 25%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 50%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 75%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 100%;"></div>
                                        </div>
                                        
                                        <!-- Chart bars container -->
                                        <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                            @foreach($months as $index => $month)
                                                @php
                                                    $percentage = $aiSuccessRates[$index] ?? 0;
                                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                                @endphp
                                                <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                                    @if($percentage > 0)
                                                        <div style="background: #ef4444; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative; vertical-align: bottom;">
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <!-- X-axis labels -->
                                    <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                        <div style="display: table; width: 100%; table-layout: fixed;">
                                            @foreach($months as $month)
                                                <div style="display: table-cell; width: 8.33%; text-align: center;">
                                                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $month }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: center;">
                            <div style="font-size: 12px; color: #64748b;">Monthly AI Vishing Success Rate Trends</div>
                        </div>
                    </div>
                </div>
            
            <!-- AI Analysis Text -->
            <div style="display: table-cell; width: 40%; vertical-align: top; padding-left: 15px;">
                <div class="column-card" style="min-height: 350px;">
                    <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">AI Vishing Threat Analysis</h4>
                    
                    <p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 15px;">
                        AI-powered voice phishing uses synthetic voice technology to impersonate trusted contacts. This chart tracks monthly success rates of AI vishing simulations testing employee awareness.
                    </p>
                    
                    <div style="margin-bottom: 15px;">
                        <h5 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Key Metrics:</h5>
                        <ul style="font-size: 12px; color: #475569; line-height: 1.5; margin: 0; padding-left: 20px;">
                            <li style="margin-bottom: 5px;">Monthly simulation success rate</li>
                            <li style="margin-bottom: 5px;">Voice authentication bypass attempts</li>
                            <li style="margin-bottom: 5px;">Employee response patterns</li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 4px solid #ef4444;">
                        <p style="font-size: 12px; color: #1e293b; margin: 0; font-weight: 500;">
                            <strong>Security Insight:</strong> Higher success rates indicate need for enhanced voice verification protocols and AI detection training.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp Click Report -->
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- WhatsApp Chart Section -->
            <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 15px;">
                <div class="column-card" style="min-height: 350px; text-align: center;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                        <span style="margin-right: 10px;">📱</span>WhatsApp Click Rate Over Time
                    </h3>
                        
                        @php
                            $waData = $wa_events_over_time ?? [];
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            $currentMonth = date('n') - 1; // 0-based index
                            $waClickRates = array_fill(0, 12, 0);
                            
                            // Process WhatsApp data to extract monthly click rates
                            if (!empty($waData)) {
                                foreach ($waData as $monthData) {
                                    if (isset($monthData['month']) && isset($monthData['clickRate'])) {
                                        // Extract month name from "Month YYYY" format
                                        $monthName = explode(' ', $monthData['month'])[0] ?? '';
                                        $monthIndex = array_search($monthName, $fullMonths);
                                        if ($monthIndex !== false) {
                                            $waClickRates[$monthIndex] = floatval($monthData['clickRate']);
                                        }
                                    }
                                }
                            }
                            
                            $maxWaRate = max($waClickRates) ?: 10; // Minimum scale of 10%
                        @endphp
                        
                        <!-- WhatsApp Chart -->
                        <div style="margin: 30px auto 20px; text-align: center;">
                            <div style="position: relative; height: 200px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; background: #ffffff;">
                                
                                <!-- Chart container -->
                                <div style="position: relative; height: 160px;">
                                    <!-- Y-axis labels -->
                                    <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                        <div style="position: relative; height: 100%;">
                                            <div style="position: absolute; top: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">100%</div>
                                            <div style="position: absolute; top: 25%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">75%</div>
                                            <div style="position: absolute; top: 50%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">50%</div>
                                            <div style="position: absolute; top: 75%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">25%</div>
                                            <div style="position: absolute; bottom: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">0%</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chart area -->
                                    <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                        <!-- Grid lines -->
                                        <div style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;">
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 0;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 25%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 50%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 75%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 100%;"></div>
                                        </div>
                                        
                                        <!-- Chart bars container -->
                                        <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                            @foreach($months as $index => $month)
                                                @php
                                                    $percentage = $waClickRates[$index] ?? 0;
                                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                                @endphp
                                                <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                                    @if($percentage > 0)
                                                        <div style="background: #f59e0b; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative; vertical-align: bottom;">
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <!-- X-axis labels -->
                                    <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                        <div style="display: table; width: 100%; table-layout: fixed;">
                                            @foreach($months as $month)
                                                <div style="display: table-cell; width: 8.33%; text-align: center;">
                                                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $month }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: center;">
                            <div style="font-size: 12px; color: #64748b;">Monthly WhatsApp Click Rate Trends</div>
                        </div>
                    </div>
                </div>
            
            <!-- WhatsApp Analysis Text -->
            <div style="display: table-cell; width: 40%; vertical-align: top; padding-left: 15px;">
                <div class="column-card" style="min-height: 350px;">
                    <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">WhatsApp Threat Analysis</h4>
                    
                    <p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 15px;">
                        WhatsApp phishing attacks exploit the platform's trusted environment to deceive employees. This chart tracks monthly click rates on malicious WhatsApp links.
                    </p>
                    
                    <div style="margin-bottom: 15px;">
                        <h5 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Key Metrics:</h5>
                        <ul style="font-size: 12px; color: #475569; line-height: 1.5; margin: 0; padding-left: 20px;">
                            <li style="margin-bottom: 5px;">Monthly click rate percentage</li>
                            <li style="margin-bottom: 5px;">12-month trend analysis</li>
                            <li style="margin-bottom: 5px;">Employee susceptibility patterns</li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 4px solid #f59e0b;">
                        <p style="font-size: 12px; color: #1e293b; margin: 0; font-weight: 500;">
                            <strong>Security Insight:</strong> Higher click rates indicate increased vulnerability. Regular training helps reduce these rates over time.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Scan Report -->
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- QR Chart Section -->
            <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 15px;">
                <div class="column-card" style="min-height: 350px; text-align: center;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                        <span style="margin-right: 10px;">📷</span>QR Code Scan Rate Over Time
                    </h3>
                        
                        @php
                            $qrData = $qr_events_over_time ?? [];
                            $qrScanRates = array_fill(0, 12, 0);
                            $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            
                            // Process QR data to extract monthly scan rates
                            if (!empty($qrData)) {
                                foreach ($qrData as $monthData) {
                                    if (isset($monthData['month']) && isset($monthData['scanRate'])) {
                                        // Extract month name from "Month YYYY" format
                                        $monthName = explode(' ', $monthData['month'])[0] ?? '';
                                        $monthIndex = array_search($monthName, $fullMonths);
                                        if ($monthIndex !== false) {
                                            $qrScanRates[$monthIndex] = floatval($monthData['scanRate']);
                                        }
                                    }
                                }
                            }
                            
                            $maxQrRate = max($qrScanRates) ?: 10; // Minimum scale of 10%
                        @endphp
                        
                        <!-- QR Chart -->
                        <div style="margin: 30px auto 20px; text-align: center;">
                            <div style="position: relative; height: 200px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; background: #ffffff;">
                                
                                <!-- Chart container -->
                                <div style="position: relative; height: 160px;">
                                    <!-- Y-axis labels -->
                                    <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                        <div style="position: relative; height: 100%;">
                                            <div style="position: absolute; top: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">100%</div>
                                            <div style="position: absolute; top: 25%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">75%</div>
                                            <div style="position: absolute; top: 50%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">50%</div>
                                            <div style="position: absolute; top: 75%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500; transform: translateY(-50%);">25%</div>
                                            <div style="position: absolute; bottom: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">0%</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Chart area -->
                                    <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                        <!-- Grid lines -->
                                        <div style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;">
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 0;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 25%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 50%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 75%;"></div>
                                            <div style="position: absolute; width: 100%; height: 1px; background: #f8fafc; top: 100%;"></div>
                                        </div>
                                        
                                        <!-- Chart bars container -->
                                        <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                            @foreach($months as $index => $month)
                                                @php
                                                    $percentage = $qrScanRates[$index] ?? 0;
                                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                                @endphp
                                                <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                                    @if($percentage > 0)
                                                        <div style="background: #8b5cf6; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative; vertical-align: bottom;">
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <!-- X-axis labels -->
                                    <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                        <div style="display: table; width: 100%; table-layout: fixed;">
                                            @foreach($months as $month)
                                                <div style="display: table-cell; width: 8.33%; text-align: center;">
                                                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $month }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: center;">
                            <div style="font-size: 12px; color: #64748b;">Monthly QR Code Scan Rate Trends</div>
                        </div>
                    </div>
                </div>
            
            <!-- QR Analysis Text -->
            <div style="display: table-cell; width: 40%; vertical-align: top; padding-left: 15px;">
                <div class="column-card" style="min-height: 350px;">
                    <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">QR Code Threat Analysis</h4>
                    
                    <p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 15px;">
                        QR code attacks exploit users' trust in scan technology. Malicious codes can redirect to phishing sites or download malware. This chart monitors employee interaction rates with suspicious QR codes.
                    </p>
                    
                    <div style="margin-bottom: 15px;">
                        <h5 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Key Metrics:</h5>
                        <ul style="font-size: 12px; color: #475569; line-height: 1.5; margin: 0; padding-left: 20px;">
                            <li style="margin-bottom: 5px;">Monthly QR scan rate percentage</li>
                            <li style="margin-bottom: 5px;">Device interaction patterns</li>
                            <li style="margin-bottom: 5px;">Vulnerability assessment data</li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 4px solid #8b5cf6;">
                        <p style="font-size: 12px; color: #1e293b; margin: 0; font-weight: 500;">
                            <strong>Security Insight:</strong> Mobile-first security training is essential for reducing scan rates on malicious QR codes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Report Section -->

        <div style="page-break-before: auto; margin-top: 40px;">
            <div class="section-title">Assigned Training Analytics</div>
            
            <!-- Training Status Distribution -->
            <div style="display: table; width: 100%; margin-bottom: 30px;">
                <div style="display: table-cell; width: 100%; text-align: center;">
                    <div style="width: 60%; margin: 0 auto;">
                        <div class="column-card" style="min-height: 400px; text-align: center;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 5px; text-align: left;">
                                <span style="margin-right: 10px;">📊</span>Assigned Training Status Distribution
                            </h3>
                            @php
                                $trainingData = $trainingStatusDistribution ?? [
                                    'total_trainings' => 253,
                                    'completed' => 35,
                                    'in_progress' => 93,
                                    'not_started' => 126,
                                    'overdue' => 167,
                                    'completed_percentage' => 8,
                                    'in_progress_percentage' => 22,
                                    'not_started_percentage' => 30,
                                    'overdue_percentage' => 40
                                ];
                            @endphp
                            <!-- Training Status Horizontal Bar Chart -->
                            <div style="margin: 5px auto; text-align: left;">
                                <!-- Total Trainings Summary -->
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <div style="font-size: 12px; color: #64748b;">{{ number_format($trainingData['total_trainings']) }} (Total Assigned Trainings)</div>
                                </div>
                                
                                <!-- Horizontal Bar Chart -->
                                <div style="width: 100%; max-width: 400px; margin: 0 auto;">
                                    
                                    <!-- Completed Bar -->
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                            <span style="font-size: 12px; color: #64748b;">Completed</span>
                                            <span style="font-size: 12px; color: #1e293b; font-weight: 600;">{{ number_format($trainingData['completed']) }} ({{ number_format($trainingData['completed_rate'] ?? 0, 1) }}%)</span>
                                        </div>
                                        <div style="width: 100%; height: 18px; background: #f1f5f9; border-radius: 9px; overflow: hidden;">
                                            <div style="width: {{ ($trainingData['completed_rate'] ?? 0) }}%; height: 100%; background: #10b981; border-radius: 9px;"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- In Progress Bar -->
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                            <span style="font-size: 12px; color: #64748b;">In Progress</span>
                                            <span style="font-size: 12px; color: #1e293b; font-weight: 600;">{{ number_format($trainingData['in_progress']) }} ({{ number_format($trainingData['in_progress_rate'] ?? 0, 1) }}%)</span>
                                        </div>
                                        <div style="width: 100%; height: 18px; background: #f1f5f9; border-radius: 9px; overflow: hidden;">
                                            <div style="width: {{ ($trainingData['in_progress_rate'] ?? 0) }}%; height: 100%; background: #3b82f6; border-radius: 9px;"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Not Started Bar -->
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                            <span style="font-size: 12px; color: #64748b;">Not Started</span>
                                            <span style="font-size: 12px; color: #1e293b; font-weight: 600;">{{ number_format($trainingData['not_started']) }} ({{ number_format($trainingData['not_started_rate'] ?? 0, 1) }}%)</span>
                                        </div>
                                        <div style="width: 100%; height: 18px; background: #f1f5f9; border-radius: 9px; overflow: hidden;">
                                            <div style="width: {{ ($trainingData['not_started_rate'] ?? 0) }}%; height: 100%; background: #64748b; border-radius: 9px;"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Overdue Bar -->
                                    <div style="margin-bottom: 15px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                            <span style="font-size: 12px; color: #64748b;">Overdue</span>
                                            <span style="font-size: 12px; color: #1e293b; font-weight: 600;">{{ number_format($trainingData['overdue']) }} ({{ number_format($trainingData['overdue_rate'] ?? 0, 1) }}%)</span>
                                        </div>
                                        <div style="width: 100%; height: 18px; background: #f1f5f9; border-radius: 9px; overflow: hidden;">
                                            <div style="width: {{ ($trainingData['overdue_rate'] ?? 0) }}%; height: 100%; background: #ef4444; border-radius: 9px;"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Color Legend (Horizontal Layout at Bottom) -->
                                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 10px; height: 10px; background: #10b981; margin-right: 5px;"></div>
                                            <span style="font-size: 11px; color: #64748b;">Completed</span>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 10px; height: 10px; background: #3b82f6; margin-right: 5px;"></div>
                                            <span style="font-size: 11px; color: #64748b;">In Progress</span>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 10px; height: 10px; background: #64748b; margin-right: 5px;"></div>
                                            <span style="font-size: 11px; color: #64748b;">Not Started</span>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 10px; height: 10px; background: #ef4444; margin-right: 5px;"></div>
                                            <span style="font-size: 11px; color: #64748b;">Overdue</span>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $company_name ?? 'Company Name' }}</strong> - Platform Security Report</p>
            <p>Generated on {{ date('F d, Y') }} | Confidential Information</p>
        </div>
    </div>
</body>
</html>