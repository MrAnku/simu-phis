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
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 10px;
            line-height: 1.5;
            margin: 0;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
        }

        .header p {
            font-size: 14px;
            color: #64748b;
            font-style: italic;
            font-weight: 300;
            margin-bottom: 3px;
        }

        .header .date {
            font-size: 12px;
            color: #94a3b8;
            text-align: right;
            margin-top: 10px;
            font-style: italic;
            font-weight: 300;
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            letter-spacing: 0.5px;
        }

        /* Additional elegant typography */
        h3,
        h4,
        h5 {
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            letter-spacing: 0.5px;
            line-height: 1.3;
            font-weight: 700;
        }

        h3 {
            font-size: 20px;
            font-weight: 700;
        }

        h4 {
            font-size: 18px;
            font-weight: 700;
        }

        h5 {
            font-size: 16px;
            font-weight: 700;
        }

        .recommendations h3,
        .security-status h3 {
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            letter-spacing: 0.8px;
            font-weight: 700;
            font-size: 20px;
        }

        /* Enhance readability */
        p {
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            line-height: 1.6;
            letter-spacing: 0.2px;
        }

        /* Make numbers more elegant */
        .score-number,
        .kpi-value {
            font-family: 'Arial', 'Calibri', 'Times New Roman', sans-serif;
            font-weight: 700;
            letter-spacing: 0.5px;
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
            background: transparent;
            transform: rotate(0deg);
            /* SVG handles rotation internally */
        }

        /* Helper class to push the chart down a bit so small indicator bars don't overlap the title */
        .mt-chart {
            margin-top: 22px !important;
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

        /* When we embed a raster donut image (QuickChart), the image already contains
           the inner hole. Disable the pseudo-element overlay to avoid double masking
           and alignment issues in PDF (dompdf). */
        .circular-progress.no-overlay::before {
            display: none;
            content: none;
        }

        .circular-progress svg {
            display: block;
            width: 100%;
            height: 100%;
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
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            text-transform: capitalize;
            letter-spacing: 1px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
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

        .kpi-badge.green {
            background: #d1fae5;
            color: #059669;
        }

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
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Times New Roman', 'Arial', 'Calibri', serif;
            letter-spacing: 0.8px;
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

        .progress-fill.blue {
            background: #3b82f6;
        }

        .progress-fill.green {
            background: #10b981;
        }

        .progress-fill.red {
            background: #ef4444;
        }

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
            body {
                padding: 0;
            }

            .container {
                box-shadow: none;
            }
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
        <table class="risk-score-section" style="width: 100%; margin-bottom: 20px; min-height: 100px; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; padding-right: 15px; vertical-align: top; text-align: center;">
                    <h2 class="risk-score-title">Overall Security Risk Score</h2>
                    @php
                    // Ensure risk score is numeric and clamped 0..100
                    $scoreRaw = $riskScore ?? 0;
                    $scoreVal = is_numeric($scoreRaw) ? (float) $scoreRaw : 0.0;
                    $scoreVal = max(0, min(100, $scoreVal));
                    // SVG gauge parameters
                    $svgSize = 140; // overall square size
                    $cx = $cy = $svgSize / 2;
                    $radius = 56; // radius of circle
                    $stroke = 12; // stroke width
                    $circumference = 2 * pi() * $radius;
                    $dash = $circumference;
                    $dashOffset = $circumference * (1 - ($scoreVal / 100));
                    @endphp

                    @php
                    // Prefer server-generated raster (base64) for dompdf. Fallback order: base64 -> local file:// -> public url -> inline SVG
                    $gBase = $riskDonutImageBase64 ?? $riskGaugeImageBase64 ?? null;
                    $gLocal = $riskDonutImageLocal ?? $riskGaugeImageLocal ?? null;
                    $gPublic = $riskDonutImage ?? $riskGaugeImage ?? null;

                    // If a raster image exists, disable the inner overlay so the image's own cutout shows through
                    $hasRaster = !empty($gBase) || !empty($gLocal) || !empty($gPublic);
                    // Always add a small top margin class to prevent the small indicator color bar from overlapping the title
                    $divClass = $hasRaster ? 'circular-progress no-overlay mt-chart' : 'circular-progress mt-chart';
                    @endphp

                    @if($scoreVal == 0)
                    {{-- When there's no score, show a compact centered message (smaller padding to avoid large gaps). --}}
                    <div style="width: {{ $svgSize }}px; height: auto; padding: 8px 0; margin: 6px auto; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:14px;">
                        No Data Available
                    </div>
                    @else
                    <div class="{{ $divClass }}" style="width: {{ $svgSize }}px; height: {{ $svgSize }}px; margin: 15px auto; position: relative;">

                        @if(!empty($gBase))
                        <img src="data:image/png;base64,{{ $gBase }}" alt="Risk gauge" style="width:100%; height:100%; display:block;" />
                        @elseif(!empty($gLocal))
                        <img src="{{ $gLocal }}" alt="Risk gauge" style="width:100%; height:100%; display:block;" />
                        @elseif(!empty($gPublic))
                        <img src="{{ $gPublic }}" alt="Risk gauge" style="width:100%; height:100%; display:block;" />
                        @else
                        {{-- Inline SVG fallback for browsers or when image generation failed --}}
                        @php
                        // When score is exactly (or numerically very close to) 100,
                        // use a butt linecap and a single dasharray so the arc closes perfectly
                        // (round linecaps can leave a small visible gap at 100%).
                        $isFullCircle = $scoreVal >= 100.0 - 1e-9;
                        $fgLinecap = $isFullCircle ? 'butt' : 'round';
                        $fgDasharray = $isFullCircle ? $dash : ($dash . ' ' . $dash);
                        $fgDashoffset = $isFullCircle ? 0 : $dashOffset;
                        @endphp

                        <svg width="{{ $svgSize }}" height="{{ $svgSize }}" viewBox="0 0 {{ $svgSize }} {{ $svgSize }}" role="img" aria-label="Overall Security Risk Score">
                            <g transform="rotate(-90 {{ $cx }} {{ $cy }})">
                                <!-- Background ring -->
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" fill="none" stroke="#e2e8f0" stroke-width="{{ $stroke }}" />
                                <!-- Foreground (value) ring -->
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" fill="none" stroke="#ef4444" stroke-width="{{ $stroke }}" stroke-linecap="{{ $fgLinecap }}"
                                    stroke-dasharray="{{ $fgDasharray }}" stroke-dashoffset="{{ $fgDashoffset }}" />
                            </g>
                        </svg>
                        @endif

                    </div>
                    @endif

                    {{-- Numeric score shown below the chart (PDF-friendly). Always render the score so it's visible even when the donut is unavailable. --}}
                    <div style="text-align: center; margin-top: 6px;">
                        <span style="font-size: 13px; color: #64748b; margin-right: 6px;">Risk Score :</span>
                        <span class="score-number" style="font-size: 22px; display: inline-block; vertical-align: middle;">{{ number_format($scoreVal, 2) }}</span>
                        <span class="score-total" style="font-size: 12px; color: #64748b; display: inline-block; vertical-align: middle; margin-left: 6px;">/100</span>
                    </div>
                </td>
                @php
                // Safe defaults for values that may be undefined
                $clickPercent = isset($click_rate) ? (float) $click_rate : 0.0;
                $highRiskUsers = isset($payload_clicked) ? (int) $payload_clicked : 0;
                $riskScoreVal = isset($riskScore) ? (float) $riskScore : (isset($risk_score) ? (float)$risk_score : 0);
                $alertType = $clickPercent > 20 ? 'HIGH PHISHING CLICK RATE DETECTED' : 'ELEVATED SECURITY AWARENESS';
                $alertMessage = $clickPercent > 20 ? 'Immediate employee training recommended' : 'Continue current security practices';
                @endphp

                <td style="width: 50%; vertical-align: middle; padding-left: 15px;">
                    <div style="background: #fffaf0; border: 1px solid #f6d365; border-radius: 8px; padding: 14px; text-align: left;">
                        <div style="color: #92400e; font-weight: 700; font-size: 15px; margin-bottom: 6px;">{{ $alertType }}</div>
                        <div style="color: #7a4b2a; font-size: 12px; margin-bottom: 10px;">{{ $alertMessage }}</div>

                        <div style="border-top: 1px solid rgba(246, 214, 101, 0.4); padding-top: 10px; margin-top: 8px; font-size: 12px; color: #475569;">
                            <strong>Current Status:</strong><br>
                            • Click Rate: {{ number_format($clickPercent, 1) }}%<br>
                            • High Risk Users: {{ number_format($highRiskUsers) }} employees<br>
                            • Risk Score: {{ number_format($riskScoreVal, 1) }}/100
                        </div>

                        <div style="margin-top: 10px;">
                            <div style="font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Risk Score Guide</div>
                            <table style="width:100%; border-collapse: collapse; font-size:11px; color:#475569;">
                                <tr>
                                    <td style="width:33%; padding:4px 6px; vertical-align: top;">
                                        <div style="background:#10b981; height:10px; border-radius:3px; margin-bottom:6px;"></div>
                                        0 - 39: Low risk
                                    </td>
                                    <td style="width:33%; padding:4px 6px; vertical-align: top;">
                                        <div style="background:#fb923c; height:10px; border-radius:3px; margin-bottom:6px;"></div>
                                        40 - 69: Medium risk
                                    </td>
                                    <td style="width:34%; padding:4px 6px; vertical-align: top;">
                                        <div style="background:#ef4444; height:10px; border-radius:3px; margin-bottom:6px;"></div>
                                        70 - 100: High risk
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

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
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; margin-bottom: 4px;">{{ number_format($assigned_policies) }}</div>
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
        {{-- Ensure this major section starts on a fresh page in PDFs to avoid orphans --}}
    <div style="page-break-before: always; -webkit-region-break-before: always; margin-top: 8px;">
            <div class="section-title" style="margin-top: 8px;">Training & Policy Compliance</div>
        </div>
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- Training Progress Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        Training Progress
                    </div>
                    <div style="margin-bottom: 15px;">
                        <span style="font-size: 13px; color: #64748b;">Completed <span style="font-size: 13px; font-weight: 600; color: #10b981;">{{ number_format($training_completed) }} ({{ number_format($training_completion_rate, 1) }}%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill green" style="width: {{ $training_completion_rate }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Pending <span style="color: #ef4444; font-weight: 600;">{{ number_format($pending_training) }} ({{ $training_pending_rate }}%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill red" style="width: {{ $training_pending_rate }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Policy Acceptance Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        Policy Acceptance
                    </div>
                    <div style="margin-bottom: 15px;">
                        <span style="font-size: 13px; color: #64748b;">Accepted <span style="color: #10b981; font-weight: 600;">{{ number_format($accepted_policies) }} ({{ number_format($accepted_policies_rate, 1) }}%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill green" style="width: {{ $accepted_policies_rate }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Not Accepted <span style="color: #ef4444; font-weight: 600;">{{ number_format($not_accepted_policies) }} ({{ $not_accepted_policies_rate }}%)</span></span>
                        <div class="progress-bar" style="margin-top: 5px;">
                            <div class="progress-fill red" style="width: {{ $not_accepted_policies_rate }}%;"></div>
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

                        @if($wa_camp_data['compromised'] > $wa_camp_data['total_attempts'] / 2)
                        <span class="status-badge status-risk">AT RISK</span>
                        @else
                        <span class="status-badge status-secure">SECURE</span>
                        @endif
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Attempts</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $wa_camp_data['total_attempts'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $wa_camp_data['compromised'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Message Viewed</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $wa_camp_data['message_viewed'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>

            <!-- AI Voice Phishing Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        AI Voice Phishing
                        @if($ai_camp_data['compromised'] > $ai_camp_data['total_attempts'] / 2)
                        <span class="status-badge status-risk">AT RISK</span>
                        @else
                        <span class="status-badge status-secure">SECURE</span>
                        @endif
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Attempts</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $ai_camp_data['total_attempts'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $ai_camp_data['compromised'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Call Reported</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $ai_camp_data['reported_calls'] }}</span>
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
                        @if($email_camp_data['compromised'] > $email_camp_data['total_attempts'] / 2)
                        <span class="status-badge status-risk">AT RISK</span>
                        @else
                        <span class="status-badge status-secure">SECURE</span>
                        @endif
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Attempts</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $email_camp_data['total_attempts'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $email_camp_data['compromised'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Email Reported</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $email_camp_data['email_reported'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>

            <!-- QR Code Threats Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 15px;">
                <div class="column-card">
                    <div class="column-title">
                        QR Code Threats

                        @if($quish_camp_data['compromised'] > $quish_camp_data['total_attempts'] / 2)
                        <span class="status-badge status-risk">AT RISK</span>
                        @else
                        <span class="status-badge status-secure">SECURE</span>
                        @endif
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Total Attempts</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $quish_camp_data['total_attempts'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 13px; color: #64748b;">Compromised</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $quish_camp_data['compromised'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: #64748b;">Email Reported</span>
                        <span style="font-size: 16px; font-weight: 600; color: #1e293b; float: right;">{{ $quish_camp_data['email_reported'] }}</span>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations & Action Items -->
    <div style="page-break-before: always; -webkit-region-break-before: always; margin-top: 8px;">
            <div class="section-title" style="margin-top: 8px;">Recommendations & Action Items</div>
        </div>
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
                        <h3>Security Status</h3>
                    </div>
                    @php
                    $compromise_rate = $total_threats > 0 ? ($totalCompromised / $total_threats) * 100 : 0;
                    @endphp

                    @if($compromise_rate > 30)
                    <p style="font-size: 13px; color: #dc2626; margin-bottom: 15px;">
                        <strong>Critical Security Alert:</strong> {{ number_format($totalCompromised ?? 0) }} security incidents detected with a {{ number_format($compromise_rate, 1) }}% compromise rate across {{ number_format($total_users ?? 0) }} users. Immediate action required to strengthen security protocols and reduce vulnerability exposure through enhanced training and awareness programs.
                    </p>
                    @elseif($compromise_rate > 15)
                    <p style="font-size: 13px; color: #f59e0b; margin-bottom: 15px;">
                        <strong> Security Warning:</strong> {{ number_format($totalCompromised ?? 0) }} security incidents recorded with a {{ number_format($compromise_rate, 1) }}% compromise rate. Consider implementing additional security measures and training to reduce risk exposure across your {{ number_format($total_users ?? 0) }} users.
                    </p>
                    @elseif($compromise_rate > 0)
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">
                        <strong> Security Notice:</strong> {{ number_format($totalCompromised ?? 0) }} minor security incidents recorded with a {{ number_format($compromise_rate, 1) }}% compromise rate. Your security posture is generally good across {{ number_format($total_users ?? 0) }} users, but continue monitoring and maintain regular training schedules.
                    </p>
                    @else
                    <p style="font-size: 13px; color: #10b981; margin-bottom: 15px;">
                        <strong> Excellent Security Status:</strong> No security compromises detected across your {{ number_format($total_users ?? 0) }} users. Your organization demonstrates strong security awareness and effective training programs. Continue current security practices to maintain this excellent standard.
                    </p>
                    @endif

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
                            Training Analysis
                        </h3>

                        <!-- Simple Border-based Donut Chart or server-generated image -->
                        <div style="margin: 20px auto; text-align: center;">
                            @php
                            // Ensure fallback values exist
                            $trainingAssigned = isset($training_assigned) ? (int) $training_assigned : 0;
                            $trainingStarted = isset($totalTrainingStarted) ? (int) $totalTrainingStarted : 0; // Total who started (includes completed)
                            $trainingCompleted = isset($training_completed) ? (int) $training_completed : 0;

                            // Prefer base64 (embedded) for dompdf, then local file path, then public URL
                            $donutBase64 = $donutChartImageBase64 ?? null;
                            $donutLocal = $donutChartImageLocal ?? null;
                            $donutPublic = $donutChartImage ?? null;

                            $allZero = ($trainingAssigned === 0 && $trainingStarted === 0 && $trainingCompleted === 0);
                            @endphp

                            @if($allZero)
                            <div style="width:220px; height:160px; margin:20px auto; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:14px;">
                                No Data Available
                            </div>
                            @else
                            @if(!empty($donutBase64))
                            <div style="width:220px; height:160px; margin:0 auto;">
                                <img src="data:image/png;base64,{{ $donutBase64 }}" alt="Training status donut" style="max-width:100%; height:auto; display:block; margin:0 auto;" />
                            </div>
                            @elseif(!empty($donutLocal) || !empty($donutPublic))
                            @php $donutSrc = $donutLocal ?? $donutPublic; @endphp
                            <div style="width: 220px; height: 160px; margin: 0 auto;">
                                <img src="{{ $donutSrc }}" alt="Training status donut" style="max-width:100%; height:auto; display:block; margin:0 auto;" />
                            </div>
                            @else
                            @php
                            $total = $trainingAssigned + $trainingStarted + $trainingCompleted;
                            // Prevent division by zero
                            if ($total == 0) {
                            $total = 1;
                            }
                            $assignedPercentage = ($trainingAssigned / $total) * 100;
                            $startedPercentage = ($trainingStarted / $total) * 100;
                            $completedPercentage = ($trainingCompleted / $total) * 100;
                            @endphp

                            <!-- Simple stacked circular progress representation (browser fallback) -->
                            <div style="width: 90px; height: 90px; margin: 0 auto; position: relative;">
                                <div style="width: 90px; height: 90px; border-radius: 50%; background: #3b82f6; position: relative;">
                                    @if($startedPercentage > 0)
                                    <div style="position: absolute; top: 10%; left: 10%; width: 80%; height: 80%; border-radius: 50%; background: #fb923c;"></div>
                                    @endif
                                    @if($completedPercentage > 0)
                                    <div style="position: absolute; top: 20%; left: 20%; width: 60%; height: 60%; border-radius: 50%; background: #10b981;"></div>
                                    @endif
                                    <div style="position: absolute; top: 30%; left: 30%; width: 40%; height: 40%; border-radius: 50%; background: white;"></div>
                                </div>
                            </div>
                            @endif
                            @endif
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
                        $staticScore = $avg_scores['static_training'];
                        $conversationalScore = $avg_scores['conversational_training'];
                        $gamifiedScore = $avg_scores['gamified_training'];
                        $aiScore = $avg_scores['ai_training'];
                        @endphp

                        <!-- Chart Container -->
                        <div style="position: relative; height: 180px; margin: 20px 0;">
                            <!-- Y-axis labels -->
                            <div style="position: absolute; left: 0; top: 0; width: 30px; height: 150px;">
                                <div style="position: relative; height: 100%;">
                                    <div style="position: absolute; top: 0; right: 5px; font-size: 10px; color: #64748b;">100</div>
                                    <div style="position: absolute; top: 25%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">75</div>
                                    <div style="position: absolute; top: 50%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">50</div>
                                    <div style="position: absolute; top: 75%; right: 5px; font-size: 10px; color: #64748b; transform: translateY(-50%);">25</div>
                                    <div style="position: absolute; bottom: 0; right: 5px; font-size: 10px; color: #64748b;">0</div>
                                </div>
                            </div>

                            <!-- Chart area -->
                            <div style="position: absolute; left: 0px; top: 0; right: 0; height: 150px;">
                                <!-- Y-axis grid lines -->
                                <div style="position: absolute; left: 0; top: 20px; right: 0; height: 120px;">
                                    <!-- 100% line -->
                                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 1px; background: #e2e8f0; opacity: 0.5;"></div>
                                    <!-- 75% line -->
                                    <div style="position: absolute; top: 30px; left: 0; right: 0; height: 1px; background: #e2e8f0; opacity: 0.5;"></div>
                                    <!-- 50% line -->
                                    <div style="position: absolute; top: 60px; left: 0; right: 0; height: 1px; background: #e2e8f0; opacity: 0.5;"></div>
                                    <!-- 25% line -->
                                    <div style="position: absolute; top: 90px; left: 0; right: 0; height: 1px; background: #e2e8f0; opacity: 0.5;"></div>
                                    <!-- 0% line (baseline) -->
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: #e2e8f0; opacity: 0.8;"></div>
                                </div>

                                <!-- Chart bars container with absolute positioning -->
                                <div style="position: relative; width: 100%; height: 140px; top: 20px;">
                                    <!-- Static -->
                                    <div style="position: absolute; left: 12.5%; width: 25%; height: 120px; text-align: center;">
                                        @php $barHeight = max(($staticScore / 100) * 120, 2); @endphp
                                        <div style="position: absolute; bottom: 0; left: 45%; transform: translateX(-50%); background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0;">
                                            <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $staticScore }}</div>
                                        </div>
                                    </div>
                                    <!-- Conversational -->
                                    <div style="position: absolute; left: 37.5%; width: 25%; height: 120px; text-align: center;">
                                        @php $barHeight = max(($conversationalScore / 100) * 120, 2); @endphp
                                        <div style="position: absolute; bottom: 0; left: 35%; transform: translateX(-50%); background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0;">
                                            <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $conversationalScore }}</div>
                                        </div>
                                    </div>
                                    <!-- Gamified -->
                                    <div style="position: absolute; left: 62.5%; width: 25%; height: 120px; text-align: center;">
                                        @php $barHeight = max(($gamifiedScore / 100) * 120, 2); @endphp
                                        <div style="position: absolute; bottom: 0; left: 15%; transform: translateX(-50%); background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0;">
                                            <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $gamifiedScore }}</div>
                                        </div>
                                    </div>
                                    <!-- AI -->
                                    <div style="position: absolute; left: 87.5%; width: 20%; height: 120px; text-align: center;">
                                        @php $barHeight = max(($aiScore / 100) * 120, 2); @endphp
                                        <div style="position: absolute; bottom: 0; left: 15%; transform: translateX(-50%); background: #fb923c; width: 30px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0;">
                                            <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 9px; color: #1e293b; font-weight: 600;">{{ $aiScore }}</div>
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
                            Risk Distribution
                        </h3>

                        @php
                        $riskData = $riskAnalysis ?? ['in_high_risk' => 0, 'in_moderate_risk' => 14, 'in_low_risk' => 1];
                        $highRisk = $riskData['in_high_risk'];
                        $moderateRisk = $riskData['in_moderate_risk'];
                        $lowRisk = $riskData['in_low_risk'];
                        $totalRisk = max(($highRisk + $moderateRisk + $lowRisk), 1);
                        @endphp

                        <!-- Risk Distribution Pie Chart (prefer server-generated image for PDFs) -->
                        <div style="margin: 20px auto; text-align: center;">
                            @php
                            // Prefer base64 (embedded) for dompdf, then local file path, then public URL
                            $riskBase64 = $riskChartImageBase64 ?? null;
                            $riskLocal = $riskChartImageLocal ?? null;
                            $riskPublic = $riskChartImage ?? null;
                            @endphp

                            @php
                            $riskAllZero = ($highRisk === 0 && $moderateRisk === 0 && $lowRisk === 0);
                            @endphp

                            @if($riskAllZero)
                            <div style="width:220px; height:160px; margin:20px auto; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:14px;">
                                No Data Available
                            </div>
                            @elseif(!empty($riskBase64))
                            <div style="width:220px; height:160px; margin:0 auto;">
                                <img src="data:image/png;base64,{{ $riskBase64 }}" alt="Risk distribution donut" style="max-width:100%; height:auto; display:block; margin:0 auto;" />
                            </div>
                            @elseif(!empty($riskLocal) || !empty($riskPublic))
                            @php $riskSrc = $riskLocal ?? $riskPublic; @endphp
                            <div style="width: 220px; height: 160px; margin: 0 auto;">
                                <img src="{{ $riskSrc }}" alt="Risk distribution donut" style="max-width:100%; height:auto; display:block; margin:0 auto;" />
                            </div>
                            @else
                            {{-- Fallback: simple pie/donut built with inline divs (browser-only) --}}
                            @php
                            // Calculate risk percentages for visualization
                            $totalRiskCalc = max(($highRisk + $moderateRisk + $lowRisk), 1);
                            $moderatePercentage = ($moderateRisk / $totalRiskCalc) * 100;
                            $lowPercentage = ($lowRisk / $totalRiskCalc) * 100;
                            $highPercentage = ($highRisk / $totalRiskCalc) * 100;
                            @endphp

                            <div style="width: 90px; height: 90px; margin: 0 auto; position: relative;">
                                <div style="width: 90px; height: 90px; border-radius: 50%; background: #f1f5f9; position: relative;">
                                    @if($lowRisk > 0 && $lowPercentage >= 50)
                                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: #10b981;"></div>
                                    @endif

                                    @if($moderateRisk > 0 && $moderatePercentage >= 50)
                                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: #fb923c;"></div>
                                    @endif

                                    @if($highRisk > 0 && $highPercentage >= 50)
                                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: #ef4444;"></div>
                                    @endif

                                    @if($moderateRisk > 0 && $moderatePercentage < 50 && $moderatePercentage> 0)
                                        <div style="position: absolute; top: 15%; left: 15%; width: 70%; height: 70%; border-radius: 50%; background: #fb923c;"></div>
                                        @endif

                                        @if($highRisk > 0 && $highPercentage < 50 && $highPercentage> 0)
                                            <div style="position: absolute; top: 25%; left: 25%; width: 50%; height: 50%; border-radius: 50%; background: #ef4444;"></div>
                                            @endif

                                            <div style="position: absolute; top: 30%; left: 30%; width: 40%; height: 40%; border-radius: 50%; background: white;"></div>
                                </div>
                            </div>
                            @endif
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
                            Most Compromised
                        </h3>
                        <div style="margin: 15px 0;">
                            @if(empty($most_compromised_employees) || count($most_compromised_employees) == 0)
                            <div style="padding: 10px 0 10px 20px; color:#64748b; font-size:15px;">No Data Available</div>

                            @else
                            @foreach($most_compromised_employees as $index => $employee)
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
                        @endif
                    </div>
                </div>
            </div>

            <!-- Most Clicked Section -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 20px;">
                <div class="column-card" style="min-height: 300px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">
                        Most Clicked
                    </h3>
                    <div style="margin: 15px 0;">
                        @if(empty($most_clicked_emp) || count($most_clicked_emp) == 0)
                        <div style="padding: 10px 0 10px 20px; color:#64748b; font-size:15px;">No Data Available</div>

                        @else
                        @foreach($most_clicked_emp as $index => $employee)
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
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>


    <!-- Weekly Email Simulation Analytics -->
    {{-- Ensure this heading doesn't get orphaned at bottom of a page in PDF output. --}}
    <div style="page-break-before: always; -webkit-region-break-before: always; margin-top: 8px;">
        <div class="section-title">Simulation Analytics</div>

        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <!-- Overview Section -->
            <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 20px;">
                <div class="column-card" style="min-height: 200px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">Overview</h3>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 15px;">
                        Comprehensive phishing simulation conducted across all departments to assess employee security awareness and response to phishing attempts. This report provides insights into employee behavior patterns, identifies high-risk areas, and recommends targeted training initiatives.
                    </p>

                    @php
                    $weeklyClicks = $phish_clicks_weekly ?? [];
                    if (!is_array($weeklyClicks)) $weeklyClicks = [];

                    // Extract percentage values (ensure numeric)
                    $weeklyClicksArray = [];
                    for ($i = 0; $i < 7; $i++) {
                        $weeklyClicksArray[$i]=isset($weeklyClicks[$i]['percentage'])
                        ? (float) $weeklyClicks[$i]['percentage']
                        : 0;
                        }

                        $weekDays=['Sun', 'Mon' , 'Tue' , 'Wed' , 'Thu' , 'Fri' , 'Sat' ];
                        $hasWeeklyData=collect($weeklyClicksArray)->contains(fn($v) => (float) $v > 0);
                        @endphp

                        <!-- Always Visible Title -->
                        <h4 style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 10px; text-align: center;">
                            Email Click Rate Over Time
                        </h4>

                        <!-- Chart or No Data -->
                        @if($hasWeeklyData)
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; background: #ffffff; min-height: 220px; display: flex; flex-direction: column;">
                            <div style="position: relative; height: 180px;">
                                <!-- Y-axis labels -->
                                <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                    @foreach([100,75,50,25,0] as $label)
                                    <div style="position: absolute; top: {{ 140 - ($label * 1.4) }}px; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">
                                        {{ $label }}%
                                    </div>
                                    @endforeach
                                </div>

                                <!-- Grid lines (drawn behind bars) -->
                                <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px; z-index: 1;">
                                    @foreach([0,25,50,75,100] as $line)
                                    <div style="position: absolute; width: 100%; height: 1px; background: rgba(242,246,249,0.95); top: {{ $line }}%;"></div>
                                    @endforeach

                                    <!-- Bars (ensure bars render above the grid lines) -->
                                    <div style="display: table; width: 100%; height: 100%; table-layout: fixed; position: relative; z-index: 2;">
                                        @foreach($weekDays as $index => $day)
                                        @php
                                        $percentage = $weeklyClicksArray[$index] ?? 0;
                                        // Only render a bar when percentage > 0. No numeric label on top per design.
                                        $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                        @endphp
                                        <div style="display: table-cell; vertical-align: bottom; text-align: center;">
                                            @if($percentage > 0)
                                            <div style="background: #fb923c; width: 24px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto; position: relative; z-index: 3;"></div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- X-axis labels -->
                                <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                    <div style="display: table; width: 100%; table-layout: fixed;">
                                        @foreach($weekDays as $day)
                                        <div style="display: table-cell; text-align: center;">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $day }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; min-height: 200px; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px;">
                            No Data Available
                        </div>
                        @endif
                </div>
            </div>

            <!-- Summary Section -->
            <div style="display: table-cell; width: 40%; vertical-align: top; padding-left: 20px;">
                <div class="column-card" style="min-height: 200px; text-align: center;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Summary</h3>

                    <div style="margin-bottom: 25px;">
                        <div style="font-size: 36px; font-weight: 700; color: #ef4444;">{{ number_format($click_rate) }}%</div>
                        <div style="font-size: 12px; color: #64748b;">Click Rate</div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b;">{{ number_format($campaigns_sent) }}</div>
                        <div style="font-size: 12px; color: #64748b;">Total Simulations</div>
                    </div>

                    <div>
                        <div style="font-size: 36px; font-weight: 700; color: #f59e0b;">{{ number_format($payload_clicked) }}</div>
                        <div style="font-size: 12px; color: #64748b;">High-risk Users</div>
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
                <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 10px; text-align: left;">AI Vishing Fell For Simulation Rate Over Time</h3>

                @php
                $aiData = $ai_events_over_time ?? [];
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $aiSuccessRates = array_fill(0, 12, 0);

                if (!empty($aiData)) {
                foreach ($aiData as $monthData) {
                if (isset($monthData['month']) && isset($monthData['fellForSimulationRate'])) {
                $monthName = explode(' ', $monthData['month'])[0] ?? '';
                $monthIndex = array_search($monthName, $fullMonths);
                if ($monthIndex !== false) {
                $aiSuccessRates[$monthIndex] = floatval($monthData['fellForSimulationRate']);
                }
                }
                }
                }

                $hasAiData = array_sum($aiSuccessRates) > 0;
                @endphp

                @if($hasAiData)
                <!-- Chart -->
                <div style="margin: 30px auto 20px; text-align: center;">
                    <div style="position: relative; height: 200px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; background: #ffffff;">
                        <!-- Chart container -->
                        <div style="position: relative; height: 160px;">
                            <!-- Y-axis labels -->
                            <div style="position: absolute; left: 0; top: 0; width: 40px; height: 140px;">
                                <div style="position: relative; height: 100%;">
                                    <div style="position: absolute; top: 0; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">100%</div>
                                    <div style="position: absolute; top: 25%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">75%</div>
                                    <div style="position: absolute; top: 50%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">50%</div>
                                    <div style="position: absolute; top: 75%; right: 5px; font-size: 11px; color: #64748b; font-weight: 500;">25%</div>
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

                                <!-- Bars -->
                                <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                    @foreach($months as $index => $month)
                                    @php
                                    $percentage = $aiSuccessRates[$index] ?? 0;
                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                    @endphp
                                    <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                        @if($percentage > 0)
                                        <div style="background: #ef4444; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto;"></div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- X-axis labels -->
                            <div style="position: absolute; left: 45px; bottom: 0; right: 0; height: 20px;">
                                <div style="display: table; width: 100%; table-layout: fixed;">
                                    @foreach($months as $month)
                                    <div style="display: table-cell; text-align: center;">
                                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $month }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 15px; text-align: center;">
                    <div style="font-size: 12px; color: #64748b;">Monthly AI Vishing Fell For Simulation Rate Trends</div>
                </div>
                @else
                <!-- No Data Available -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; min-height: 200px; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px;">
                    No Data Available
                </div>
                @endif
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
                    <ul style="font-size: 12px; color: #475569; line-height: 1.5; padding-left: 20px;">
                        <li style="margin-bottom: 5px;">Monthly simulation success rate</li>
                        <li style="margin-bottom: 5px;">Voice authentication bypass attempts</li>
                        <li style="margin-bottom: 5px;">Employee response patterns</li>
                    </ul>
                </div>

                <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 4px solid #ef4444;">
                    <p style="font-size: 12px; color: #1e293b; margin: 0;">
                        <strong>Security Insight:</strong> Higher success rates indicate need for enhanced voice verification protocols and AI detection training.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- AI Vishing Report -->


    <!-- WhatsApp Click Report -->
    <div style="display: table; width: 100%; margin-bottom: 30px;">
        <!-- WhatsApp Chart Section -->
        <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 15px;">
            <div class="column-card" style="min-height: 350px; text-align: center;">
                <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; text-align: left;">
                    WhatsApp Click Rate Over Time
                </h3>

                @php
                $waData = $wa_events_over_time ?? [];
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $waClickRates = array_fill(0, 12, 0);

                // Process WhatsApp data to extract monthly click rates
                if (!empty($waData)) {
                foreach ($waData as $monthData) {
                if (isset($monthData['month']) && isset($monthData['clickRate'])) {
                $monthName = explode(' ', $monthData['month'])[0] ?? '';
                $monthIndex = array_search($monthName, $fullMonths);
                if ($monthIndex !== false) {
                $waClickRates[$monthIndex] = floatval($monthData['clickRate']);
                }
                }
                }
                }

                $hasWaData = array_sum($waClickRates) > 0; // Check if there is any non-zero data
                @endphp

                @if($hasWaData)
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

                            <!-- Chart bars -->
                            <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                    @foreach($months as $index => $month)
                                    @php
                                    $percentage = $waClickRates[$index] ?? 0;
                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                    @endphp
                                    <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                        @if($percentage > 0)
                                        <div style="background: #f59e0b; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto;"></div>
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

                @else
                <!-- No Data Available -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; min-height: 200px; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px;">
                    No Data Available
                </div>
                @endif
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
                    QR Code Scan Rate Over Time
                </h3>

                @php
                $qrData = $qr_events_over_time ?? [];
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $fullMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $qrScanRates = array_fill(0, 12, 0);

                // Process QR data to extract monthly scan rates
                if (!empty($qrData)) {
                foreach ($qrData as $monthData) {
                if (isset($monthData['month']) && isset($monthData['scanRate'])) {
                $monthName = explode(' ', $monthData['month'])[0] ?? '';
                $monthIndex = array_search($monthName, $fullMonths);
                if ($monthIndex !== false) {
                $qrScanRates[$monthIndex] = floatval($monthData['scanRate']);
                }
                }
                }
                }

                $hasQrData = array_sum($qrScanRates) > 0; // Check if there is any non-zero data
                @endphp

                @if($hasQrData)
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

                            <!-- Chart bars -->
                            <div style="position: absolute; left: 45px; top: 0; right: 0; height: 140px;">
                                <div style="position: relative; width: 100%; height: 100%; display: table; table-layout: fixed;">
                                    @foreach($months as $index => $month)
                                    @php
                                    $percentage = $qrScanRates[$index] ?? 0;
                                    $barHeight = $percentage > 0 ? max(($percentage / 100) * 140, 3) : 0;
                                    @endphp
                                    <div style="display: table-cell; width: 8.33%; vertical-align: bottom; text-align: center; height: 140px;">
                                        @if($percentage > 0)
                                        <div style="background: #8b5cf6; width: 16px; height: {{ $barHeight }}px; border-radius: 2px 2px 0 0; margin: 0 auto;"></div>
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

                @else
                <!-- No Data Available -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; min-height: 200px; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px;">
                    No Data Available
                </div>
                @endif
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

    <div style="page-break-before: always; -webkit-region-break-before: always; margin-top: 8px;">
        <div class="section-title">Assigned Training Analytics</div>

        <!-- Training Status Distribution -->
        <div style="display: table; width: 100%; margin-bottom: 30px;">
            <div style="display: table-cell; width: 100%; text-align: center;">
                <div style="width: 60%; margin: 0 auto;">
                    <div class="column-card" style="min-height: 400px; text-align: center;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 5px; text-align: left;">
                            Assigned Training Status Distribution
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