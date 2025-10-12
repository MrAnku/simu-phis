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
                        <div class="score-number">{{ $riskScore ?? 61.1 }}</div>
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
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="section-title">Key Performance Indicators</div>
        <table style="width: 95%; margin-bottom: 30px; border-collapse: separate; border-spacing: 25px 20px; margin-left: 15px; margin-right: auto;">
            <tr>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Users</div>
                        <div class="kpi-value">{{ number_format($total_users ?? 150) }}</div>
                        <div class="kpi-subtitle">Phishing campaigns</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Campaigns Sent</div>
                        <div class="kpi-value">{{ number_format($campaigns_sent ?? 10) }}</div>
                        <div class="kpi-subtitle">Phishing campaigns</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Payload Clicked</div>
                        <div class="kpi-value">{{ number_format($payload_clicked ?? 200) }}</div>
                        <div class="kpi-subtitle">Click rate</div>
                        <div class="kpi-percentage">{{ number_format($click_rate ?? 4.0, 1) }}%</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Training Assigned</div>
                        <div class="kpi-value">{{ number_format($training_assigned ?? 8) }}</div>
                        <div class="kpi-subtitle">Training campaigns</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Training Assigned</div>
                        <div class="kpi-value">{{ number_format($training_assigned ?? 8) }}</div>
                        <div class="kpi-subtitle">High risk users</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">Training Completed</div>
                        <div class="kpi-value">{{ number_format($training_completed ?? 8) }}</div>
                        <div class="kpi-subtitle">High risk users</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">At Risk</div>
                        <div class="kpi-value">{{ number_format(20) }}</div>
                        <div class="kpi-subtitle">Risk Score 20/100</div>
                        <div class="kpi-badge">Cut</div>
                    </div>
                </td>
                <td style="width: 25%; vertical-align: top; height: 120px; padding: 0;">
                    <div class="kpi-card">
                        <div class="kpi-label">High-risk users</div>
                        <div class="kpi-value">{{ number_format(58.3, 1) }}</div>
                        <div class="kpi-subtitle">High-risk users</div>
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
                        <div style="display: table-cell; width: 33.33%; text-align: center; vertical-align: top;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">SUCCESS</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format(96.8, 1) }}%</div>
                        </div>
                        <div style="display: table-cell; width: 33.33%; text-align: center; vertical-align: top;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">AVG RISK</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($riskScore ?? 61.1, 1) }}</div>
                        </div>
                        <div style="display: table-cell; width: 33.33%; text-align: center; vertical-align: top;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">COMPROMISED</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($totalCompromised ?? 3) }}/5.20</div>
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