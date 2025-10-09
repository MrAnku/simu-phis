<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Security Report - {{ $company_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.4;
            color: #1e293b;
            background: white;
            font-size: 14px; /* INCREASED FROM 11PX TO 14PX FOR BETTER READABILITY */
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            padding: 40px; /* Increased from 15px to 40px for better spacing from all edges */
        }
        
        /* COMPACT HEADER WITH BETTER SPACING */
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        
        .header h1 {
            font-size: 24px; /* INCREASED FROM 20PX TO 24PX */
            color: #1e40af;
            margin-bottom: 3px;
        }
        
        .header .company-name {
            font-size: 16px; /* INCREASED FROM 14PX TO 16PX */
            color: #64748b;
            font-weight: 600;
        }
        
        .header .report-date {
            font-size: 11px; /* INCREASED FROM 9PX TO 11PX */
            color: #94a3b8;
            margin-top: 3px;
        }
        
        /* MORE COMPACT RISK SCORE SECTION */
        .risk-score-section {
            background: #5b21b6; /* Solid purple instead of gradient */
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            text-align: center;
            page-break-inside: avoid;
        }
        
        .risk-score-section h2 {
            font-size: 16px; /* INCREASED FROM 14PX TO 16PX */
            margin-bottom: 4px;
            color: white; /* Explicitly set color to white */
        }
        
        .risk-score-value {
            font-size: 42px; /* INCREASED FROM 36PX TO 42PX */
            font-weight: bold;
            margin: 4px 0;
            color: white; /* Explicitly set color to white */
        }
        
        .risk-indicator {
            display: inline-block;
            padding: 8px 16px; /* INCREASED PADDING */
            border-radius: 5px;
            font-size: 13px; /* INCREASED FROM 10PX TO 13PX */
            font-weight: bold;
            margin-top: 4px;
        }
        
        .risk-high {
            background: #dc2626;
            color: white;
        }
        
        .risk-medium {
            background: #f59e0b;
            color: white;
        }
        
        .risk-low {
            background: #16a34a;
            color: white;
        }
        
        /* COMPACT ALERT BOXES */
        .alert-box {
            padding: 10px 12px; /* INCREASED PADDING */
            border-radius: 5px;
            margin: 8px 0;
            border-left: 3px solid;
            font-size: 13px; /* INCREASED FROM 10PX TO 13PX */
            page-break-inside: avoid;
        }
        
        .alert-danger {
            background: #fee2e2;
            border-color: #dc2626;
            color: #991b1b;
        }
        
        .alert-warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        
        .alert-success {
            background: #d1fae5;
            border-color: #16a34a;
            color: #065f46;
        }
        
        /* CHANGED FROM FLEXBOX TO INLINE-BLOCK FOR BETTER PDF COMPATIBILITY */
        .metrics-grid {
            margin: 12px 0;
            font-size: 0; /* Remove whitespace between inline-block elements */
        }
        
        .metric-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            /* Changed from flex to inline-block for PDF compatibility */
            display: inline-block;
            width: 24%;
            margin-right: 1%;
            margin-bottom: 12px;
            vertical-align: top;
            font-size: 14px; /* Reset font size */
        }
        
        .metric-card:nth-child(4n) {
            margin-right: 0; /* Remove right margin on every 4th card */
        }
        
        .section {
            margin: 15px 0; /* INCREASED MARGIN */
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px; /* INCREASED FROM 13PX TO 16PX */
            color: #1e40af;
            margin-top: 10px; /* Reduced from 20px to 10px to prevent page breaks */
            margin-bottom: 10px; /* INCREASED MARGIN */
            padding-bottom: 5px; /* INCREASED PADDING */
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600; /* ADDED FONT WEIGHT */
        }
        
        /* REMOVED CSS CHART STYLES - NO LONGER USING CHARTS */
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            background: white;
            font-size: 10px;
            page-break-inside: avoid;
        }
        
        table thead {
            background: #2563eb;
            color: white;
        }
        
        table th {
            padding: 6px 4px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        
        table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .campaign-section {
            background: #f8fafc;
            padding: 10px;
            border-radius: 5px;
            margin: 8px 0;
            page-break-inside: avoid;
        }
        
        .campaign-header {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        
        .campaign-icon {
            width: 18px;
            height: 18px;
            margin-right: 5px;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
        }
        
        /* MADE PROGRESS BARS DARKER AND MORE VISIBLE */
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
            border: 1px solid #94a3b8;
        }
        
        .progress-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        
        /* Green for good progress (>70%) */
        .progress-fill.success {
            background: #15803d;
        }
        
        /* Orange/Yellow for medium progress (30-70%) */
        .progress-fill.warning {
            background: #f59e0b;
        }
        
        /* Red for low progress (<30%) */
        .progress-fill.danger {
            background: #b91c1c;
        }
        
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 9px;
            page-break-inside: avoid;
        }
        
        .recommendation-box {
            background: #eff6ff;
            border: 2px solid #2563eb;
            border-radius: 8px; /* INCREASED FROM 5PX TO 8PX */
            padding: 15px; /* INCREASED FROM 10PX TO 15PX */
            margin: 12px 0;
            font-size: 13px; /* INCREASED FROM 10PX TO 13PX */
            page-break-inside: avoid;
        }
        
        .recommendation-box h3 {
            color: #1e40af;
            margin-top: 0; /* Remove top margin from h3 inside recommendation box */
            margin-bottom: 8px; /* INCREASED MARGIN */
            font-size: 14px; /* INCREASED FROM 11PX TO 14PX */
        }
        
        .recommendation-box ul {
            margin-left: 20px; /* INCREASED FROM 16PX TO 20PX */
        }
        
        .recommendation-box li {
            margin: 6px 0; /* INCREASED MARGIN */
            color: #334155;
            line-height: 1.5; /* ADDED LINE HEIGHT */
        }
        
        .stat-highlight {
            display: inline-block;
            background: #fef3c7;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            color: #92400e;
        }

        /* CAMPAIGN SUMMARY CARDS IN 4-COLUMN GRID USING INLINE-BLOCK */
        .campaign-summary {
            margin: 12px 0;
            font-size: 0; /* Remove whitespace between inline-block elements */
        }

        .campaign-summary-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            /* Changed from flex to inline-block for PDF compatibility */
            display: inline-block;
            width: 24%;
            margin-right: 1%;
            margin-bottom: 12px;
            vertical-align: top;
            font-size: 14px; /* Reset font size */
        }

        .campaign-summary-card:nth-child(4n) {
            margin-right: 0; /* Remove right margin on every 4th card */
        }

        /* ADDED NEW STYLES FOR HORIZONTAL METRIC CARDS */
        .metric-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin: 10px 0;
        }

        .metric-row-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 10px;
            page-break-inside: avoid;
        }

        .metric-row-card .card-title {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .metric-row-card .card-value {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
        }

        .metric-row-card .card-subtitle {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* CHANGED FROM 3-COLUMN TO 2-COLUMN LAYOUT FOR TRAINING & POLICY */
        .comparison-grid {
            margin: 12px 0;
            font-size: 0; /* Remove whitespace between inline-block elements */
        }

        .comparison-card {
            background: white; /* CHANGED FROM #f8fafc TO WHITE */
            padding: 15px; /* INCREASED FROM 10PX TO 15PX */
            border-radius: 8px; /* INCREASED FROM 5PX TO 8PX */
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* ADDED SUBTLE SHADOW */
            /* Changed from flex to inline-block for PDF compatibility */
            display: inline-block;
            /* Changed from 32% to 49% for 2-column layout */
            width: 49%;
            margin-right: 2%;
            margin-bottom: 12px;
            vertical-align: top;
            font-size: 14px; /* Reset font size */
        }

        .comparison-card:nth-child(2n) {
            /* Changed from 3n to 2n for 2-column layout */
            margin-right: 0; /* Remove right margin on every 2nd card */
        }

        /* Added missing metric card label and value styles */
        .metric-label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
        }

        /* Added missing campaign card styles */
        .campaign-type {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .campaign-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 6px 0;
            font-size: 12px;
        }

        .campaign-stat .label {
            color: #64748b;
            font-weight: 500;
        }

        .campaign-stat .value {
            font-weight: bold;
            color: #1e293b;
            font-size: 14px;
        }

        /* Added metric description style for small helper text below values */
        .metric-description {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            line-height: 1.3;
        }

        /* Updated insights card to be full-width and more compact */
        .insights-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 15px;
            page-break-inside: avoid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            /* Made full width instead of inline-block */
            width: 100%;
            margin-top: 12px;
        }

        .insights-card h3 {
            color: #1e40af;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        /* Made insight items display in a row for more compact layout */
        .insights-card .insight-item {
            background: white;
            padding: 10px 12px;
            border-radius: 5px;
            border-left: 3px solid #2563eb;
            font-size: 11px;
            line-height: 1.4;
            /* Display inline-block for horizontal layout */
            display: inline-block;
            width: 32%;
            margin-right: 2%;
            margin-bottom: 8px;
            vertical-align: top;
        }

        .insights-card .insight-item:nth-child(3n) {
            margin-right: 0;
        }

        .insights-card .insight-label {
            font-weight: bold;
            color: #1e40af;
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .insights-card .insight-value {
            color: #334155;
        }

        /* Added CSS classes for status summary cards to display horizontally */
        .status-summary-grid {
            margin: 15px 0;
            display: table;
            width: 100%;
            border-spacing: 10px 0; /* Space between cells */
        }

        .status-summary-card {
            display: table-cell; /* Changed from float to table-cell */
            width: 50%;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid;
            font-size: 14px;
            page-break-inside: avoid;
        }

        .status-summary-card:nth-child(2n) {
            margin-right: 0; /* Keep this for compatibility */
        }

        .status-summary-card.success {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .status-summary-card.danger {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .status-summary-card .card-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 20px;
            margin-right: 8px;
            font-size: 12px;
        }

        .status-summary-card.success .card-icon {
            background: #16a34a;
        }

        .status-summary-card.danger .card-icon {
            background: #dc2626;
        }

        .status-summary-card .card-label {
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }

        .status-summary-card.success .card-label {
            color: #166534;
        }

        .status-summary-card.danger .card-label {
            color: #991b1b;
        }

        .status-summary-card .card-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .status-summary-card.success .card-number {
            color: #166534;
        }

        .status-summary-card.danger .card-number {
            color: #991b1b;
        }

        .status-summary-card .card-percentage {
            font-size: 12px;
        }

        .status-summary-card.success .card-percentage {
            color: #166534;
        }

        .status-summary-card.danger .card-percentage {
            color: #991b1b;
        }
        /* ADDED NEW STYLES FOR HORIZONTAL METRIC CARDS */
        .metric-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin: 10px 0;
        }

        .metric-row-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 10px;
            page-break-inside: avoid;
        }

        .metric-row-card .card-title {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .metric-row-card .card-value {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
        }

        .metric-row-card .card-subtitle {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* CHANGED FROM 3-COLUMN TO 2-COLUMN LAYOUT FOR TRAINING & POLICY */
        .comparison-grid {
            margin: 12px 0;
            font-size: 0; /* Remove whitespace between inline-block elements */
        }

        .comparison-card {
            background: white; /* CHANGED FROM #f8fafc TO WHITE */
            padding: 15px; /* INCREASED FROM 10PX TO 15PX */
            border-radius: 8px; /* INCREASED FROM 5PX TO 8PX */
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* ADDED SUBTLE SHADOW */
            /* Changed from flex to inline-block for PDF compatibility */
            display: inline-block;
            /* Changed from 32% to 49% for 2-column layout */
            width: 49%;
            margin-right: 2%;
            margin-bottom: 12px;
            vertical-align: top;
            font-size: 14px; /* Reset font size */
        }

        .comparison-card:nth-child(2n) {
            /* Changed from 3n to 2n for 2-column layout */
            margin-right: 0; /* Remove right margin on every 2nd card */
        }

        /* Added missing metric card label and value styles */
        .metric-label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
        }

        /* Added missing campaign card styles */
        .campaign-type {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .campaign-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 6px 0;
            font-size: 12px;
        }

        .campaign-stat .label {
            color: #64748b;
            font-weight: 500;
        }

        .campaign-stat .value {
            font-weight: bold;
            color: #1e293b;
            font-size: 14px;
        }

        /* Added metric description style for small helper text below values */
        .metric-description {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            line-height: 1.3;
        }

        /* Updated insights card to be full-width and more compact */
        .insights-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 15px;
            page-break-inside: avoid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            /* Made full width instead of inline-block */
            width: 100%;
            margin-top: 12px;
        }

        .insights-card h3 {
            color: #1e40af;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        /* Made insight items display in a row for more compact layout */
        .insights-card .insight-item {
            background: white;
            padding: 10px 12px;
            border-radius: 5px;
            border-left: 3px solid #2563eb;
            font-size: 11px;
            line-height: 1.4;
            /* Display inline-block for horizontal layout */
            display: inline-block;
            width: 32%;
            margin-right: 2%;
            margin-bottom: 8px;
            vertical-align: top;
        }

        .insights-card .insight-item:nth-child(3n) {
            margin-right: 0;
        }

        .insights-card .insight-label {
            font-weight: bold;
            color: #1e40af;
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .insights-card .insight-value {
            color: #334155;
        }

        /* Added CSS classes for status summary cards to display horizontally */
        .status-summary-grid {
            margin: 15px 0;
            display: table;
            width: 100%;
            border-spacing: 10px 0; /* Space between cells */
        }

        .status-summary-card {
            display: table-cell; /* Changed from float to table-cell */
            width: 50%;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid;
            font-size: 14px;
            page-break-inside: avoid;
        }

        .status-summary-card:nth-child(2n) {
            margin-right: 0; /* Keep this for compatibility */
        }

        .status-summary-card.success {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .status-summary-card.danger {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .status-summary-card .card-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 20px;
            margin-right: 8px;
            font-size: 12px;
        }

        .status-summary-card.success .card-icon {
            background: #16a34a;
        }

        .status-summary-card.danger .card-icon {
            background: #dc2626;
        }

        .status-summary-card .card-label {
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }

        .status-summary-card.success .card-label {
            color: #166534;
        }

        .status-summary-card.danger .card-label {
            color: #991b1b;
        }

        .status-summary-card .card-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .status-summary-card.success .card-number {
            color: #166534;
        }

        .status-summary-card.danger .card-number {
            color: #991b1b;
        }

        .status-summary-card .card-percentage {
            font-size: 12px;
        }

        .status-summary-card.success .card-percentage {
            color: #166534;
        }

        .status-summary-card.danger .card-percentage {
            color: #991b1b;
        }
        /* Added CSS class to prevent campaign sections from breaking across pages */
        .campaign-type-section {
            margin-bottom: 20px; /* Reduced from 30px to 20px to save space */
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Removed emoji and question mark from header -->
        <div class="header no-break">
            <h1>Platform Security Report</h1>
            <div class="company-name">{{ $company_name }}</div>
            <div class="report-date">Generated on: {{ date('F d, Y') }}</div>
        </div>

        <div class="risk-score-section no-break">
            <h2>Overall Security Risk Score</h2>
            <div class="risk-score-value">{{ $riskScore }}/100</div>
            
            @if($riskScore < 40)
                <div class="risk-indicator risk-high">
                    ⚠️ HIGH RISK - IMMEDIATE ACTION REQUIRED
                </div>
            @elseif($riskScore < 70)
                <div class="risk-indicator risk-medium">
                    ⚡ MODERATE RISK - IMPROVEMENT NEEDED
                </div>
            @else
                <div class="risk-indicator risk-low">
                    ✓ LOW RISK - GOOD SECURITY POSTURE
                </div>
            @endif
        </div>

        @if($riskScore < 40)
            <div class="alert-box alert-danger no-break">
                <strong>🚨 CRITICAL ALERT:</strong> Your platform's risk score is critically low ({{ $riskScore }}/100). Immediate action is required to protect your organization from potential security breaches.
            </div>
        @endif

        @if($click_rate > 30)
            <div class="alert-box alert-danger no-break">
                <strong>⚠️ HIGH PHISHING CLICK RATE:</strong> Your platform has a <span class="stat-highlight">{{ number_format($click_rate, 1) }}%</span> phishing click rate. Immediate security awareness training is strongly recommended.
            </div>
        @elseif($click_rate > 15)
            <div class="alert-box alert-warning no-break">
                <strong>⚡ ELEVATED PHISHING CLICK RATE:</strong> Your phishing click rate of <span class="stat-highlight">{{ number_format($click_rate, 1) }}%</span> is above average. Consider implementing additional training programs.
            </div>
        @else
            <div class="alert-box alert-success no-break">
                <strong>✓ GOOD PHISHING AWARENESS:</strong> Your phishing click rate of {{ number_format($click_rate, 1) }}% is within acceptable limits.
            </div>
        @endif

        @php
            $training_completion_rate = $training_assigned > 0 ? ($training_completed / $training_assigned) * 100 : 0;
        @endphp

        @if($training_completion_rate < 50)
            <div class="alert-box alert-warning no-break">
                <strong>📚 LOW TRAINING COMPLETION:</strong> Only <span class="stat-highlight">{{ number_format($training_completion_rate, 1) }}%</span> of assigned training has been completed. Encourage employees to complete their training modules.
            </div>
        @endif

        @php
            $policy_acceptance_rate = $assigned_Policies > 0 ? ($acceptance_Policies / $assigned_Policies) * 100 : 0;
        @endphp

        @if($policy_acceptance_rate < 80)
            <div class="alert-box alert-warning no-break">
                <strong>📋 LOW POLICY ACCEPTANCE:</strong> Only <span class="stat-highlight">{{ number_format($policy_acceptance_rate, 1) }}%</span> of assigned policies have been accepted.
            </div>
        @endif

        <!-- CHANGED FROM 5-COLUMN TO 4-COLUMN GRID FOR BETTER SPACING -->
        <div class="section no-break">
            <!-- Removed question mark and emoji from section title -->
            <h2 class="section-title">Key Performance Indicators</h2>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Users</div>
                    <div class="metric-value">{{ number_format($total_users) }}</div>
                    <div class="metric-description">Registered employees on platform</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Campaigns Sent</div>
                    <div class="metric-value">{{ number_format($campaigns_sent) }}</div>
                    <div class="metric-description">Total security awareness campaigns</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Emails Sent</div>
                    <div class="metric-value">{{ number_format($emails_sent) }}</div>
                    <div class="metric-description">Phishing simulation emails delivered</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Payload Clicked</div>
                    <div class="metric-value" style="color: #dc2626;">{{ number_format($payload_clicked) }}</div>
                    <div class="metric-description">Users who clicked malicious links</div>
                </div>
            </div>
            
            <!-- SECOND ROW OF KPIS IN 4-COLUMN LAYOUT -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Click Rate</div>
                    <div class="metric-value" style="color: {{ $click_rate > 20 ? '#dc2626' : '#16a34a' }};">
                        {{ number_format($click_rate, 1) }}%
                    </div>
                    <div class="metric-description">Percentage of users who clicked</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Training Assigned</div>
                    <div class="metric-value">{{ number_format($training_assigned) }}</div>
                    <div class="metric-description">Security training modules assigned</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Training Completed</div>
                    <div class="metric-value" style="color: #16a34a;">{{ number_format($training_completed) }}</div>
                    <div class="metric-description">Training modules completed by users</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Blue Collar Employees</div>
                    <div class="metric-value">{{ number_format($blue_collar_employees) }}</div>
                    <div class="metric-description">High-risk employee category</div>
                </div>
            </div>
        </div>

        <!-- Updated Training & Policy section with 2-column layout and separate insights card -->
        <div class="section no-break">
            <h2 class="section-title">Training & Policy Compliance</h2>
            
            <!-- Training Progress Section -->
            <div style="margin-bottom: 20px;">  
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #16a34a; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">✓</span>
                    Training Progress Status
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Current status of security training completion across all employees</p>
                
                <!-- Progress bars with labels -->
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Completed</span>
                        <span style="font-size: 13px; font-weight: 600; color: #16a34a;">{{ number_format($training_completed) }} ({{ number_format($training_completion_rate, 1) }}%)</span>
                    </div>
                    <div style="width: 100%; height: 24px; background: #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <div style="height: 100%; background: #16a34a; width: {{ $training_completion_rate }}%;"></div>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Pending</span>
                        <span style="font-size: 13px; font-weight: 600; color: #dc2626;">{{ number_format($training_assigned - $training_completed) }} ({{ number_format(100 - $training_completion_rate, 1) }}%)</span>
                    </div>
                    <div style="width: 100%; height: 24px; background: #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <div style="height: 100%; background: #dc2626; width: {{ 100 - $training_completion_rate }}%;"></div>
                    </div>
                </div>
                
                <!-- Replaced inline styles with CSS classes for horizontal layout -->
                <div class="status-summary-grid">
                    <div class="status-summary-card success">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✓</span>
                            <span class="card-label">Completed</span>
                        </div>
                        <div class="card-number">{{ number_format($training_completed) }}</div>
                        <div class="card-percentage">{{ number_format($training_completion_rate, 1) }}%</div>
                    </div>
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✕</span>
                            <span class="card-label">Pending</span>
                        </div>
                        <div class="card-number">{{ number_format($training_assigned - $training_completed) }}</div>
                        <div class="card-percentage">{{ number_format(100 - $training_completion_rate, 1) }}%</div>
                    </div>
                </div>
            </div>
            
            <!-- Policy Acceptance Section -->
            <div style="margin-bottom: 20px;">  
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #2563eb; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">✓</span>
                    Policy Acceptance Status
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Current status of policy acceptance across all employees</p>
                
                <!-- Progress bars with labels -->
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Accepted</span>
                        <span style="font-size: 13px; font-weight: 600; color: #16a34a;">{{ number_format($acceptance_Policies) }} ({{ number_format($policy_acceptance_rate, 1) }}%)</span>
                    </div>
                    <div style="width: 100%; height: 24px; background: #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <div style="height: 100%; background: #16a34a; width: {{ $policy_acceptance_rate }}%;"></div>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Not Accepted</span>
                        <span style="font-size: 13px; font-weight: 600; color: #dc2626;">{{ number_format($assigned_Policies - $acceptance_Policies) }} ({{ number_format(100 - $policy_acceptance_rate, 1) }}%)</span>
                    </div>
                    <div style="width: 100%; height: 24px; background: #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <div style="height: 100%; background: #dc2626; width: {{ 100 - $policy_acceptance_rate }}%;"></div>
                    </div>
                </div>
                
                <!-- Replaced inline styles with CSS classes for horizontal layout -->
                <div class="status-summary-grid">
                    <div class="status-summary-card success">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✓</span>
                            <span class="card-label">Accepted</span>
                        </div>
                        <div class="card-number">{{ number_format($acceptance_Policies) }}</div>
                        <div class="card-percentage">{{ number_format($policy_acceptance_rate, 1) }}%</div>
                    </div>
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✕</span>
                            <span class="card-label">Not Accepted</span>
                        </div>
                        <div class="card-number">{{ number_format($assigned_Policies - $acceptance_Policies) }}</div>
                        <div class="card-percentage">{{ number_format(100 - $policy_acceptance_rate, 1) }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADDED NEW CAMPAIGN OVERVIEW SECTION -->
        <div class="section no-break">
            <h2 class="section-title">Campaign Overview</h2>
            
            <!-- Email Campaigns Section -->
            <div class="campaign-type-section">
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #2563eb; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">✉</span>
                    Email Phishing Campaigns
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Simulated email phishing attacks to test employee awareness</p>
                
                <!-- Email Campaign Metrics in small cards -->
                <div style="display: table; width: 100%; border-spacing: 8px 0; margin-bottom: 10px;">
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Total Campaigns</div>
                        <div style="font-size: 24px; font-weight: bold; color: #2563eb;">{{ $email_camp_data['email_campaign'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Emails Sent</div>
                        <div style="font-size: 24px; font-weight: bold; color: #2563eb;">{{ $email_camp_data['email_sent'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Emails Viewed</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $email_camp_data['email_viewed'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Payload Clicked</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $email_camp_data['payload_clicked'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Reported</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $email_camp_data['email_reported'] ?? 0 }}</div>
                    </div>
                </div>
                
                <!-- Summary cards for Email Campaigns -->
                <div class="status-summary-grid">
                    <div class="status-summary-card success">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✓</span>
                            <span class="card-label">Reported Phishing</span>
                        </div>
                        <div class="card-number">{{ $email_camp_data['email_reported'] ?? 0 }}</div>
                        <div class="card-percentage">Users who reported suspicious emails</div>
                    </div>
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">⚠</span>
                            <span class="card-label">Compromised</span>
                        </div>
                        <div class="card-number">{{ $email_camp_data['compromised'] ?? 0 }}</div>
                        <div class="card-percentage">Users who fell for the phishing attempt</div>
                    </div>
                </div>
            </div>
            
            <!-- Quishing Campaigns Section -->
            <div class="campaign-type-section">
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #7c3aed; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">⊞</span>
                    Quishing Campaigns
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">QR code-based phishing attacks to test employee vigilance</p>
                
                <!-- Quishing Campaign Metrics in small cards -->
                <div style="display: table; width: 100%; border-spacing: 8px 0; margin-bottom: 10px;">
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Total Campaigns</div>
                        <div style="font-size: 24px; font-weight: bold; color: #7c3aed;">{{ $quish_camp_data['quishing_campaign'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Emails Sent</div>
                        <div style="font-size: 24px; font-weight: bold; color: #7c3aed;">{{ $quish_camp_data['email_sent'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Emails Viewed</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $quish_camp_data['email_viewed'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">QR Scanned</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $quish_camp_data['qr_scanned'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Reported</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $quish_camp_data['email_reported'] ?? 0 }}</div>
                    </div>
                </div>
                
                <!-- Summary cards for Quishing Campaigns -->
                <div class="status-summary-grid">
                    <div class="status-summary-card success">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✓</span>
                            <span class="card-label">Reported Quishing</span>
                        </div>
                        <div class="card-number">{{ $quish_camp_data['email_reported'] ?? 0 }}</div>
                        <div class="card-percentage">Users who reported suspicious QR codes</div>
                    </div>
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">⚠</span>
                            <span class="card-label">Compromised</span>
                        </div>
                        <div class="card-number">{{ $quish_camp_data['compromised'] ?? 0 }}</div>
                        <div class="card-percentage">Users who scanned malicious QR codes</div>
                    </div>
                </div>
            </div>
            
            <!-- WhatsApp Campaigns Section -->
            <div class="campaign-type-section">
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #16a34a; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">💬</span>
                    WhatsApp Campaigns
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Simulated WhatsApp phishing attacks via messaging platform</p>
                
                <!-- WhatsApp Campaign Metrics in small cards -->
                <div style="display: table; width: 100%; border-spacing: 8px 0; margin-bottom: 10px;">
                    <div style="display: table-cell; width: 25%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Total Campaigns</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $wa_camp_data['whatsapp_campaign'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 25%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Messages Sent</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $wa_camp_data['message_sent'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 25%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Messages Viewed</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $wa_camp_data['message_viewed'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 25%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Links Clicked</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $wa_camp_data['link_clicked'] ?? 0 }}</div>
                    </div>
                </div>
                
                <!-- Summary card for WhatsApp Campaigns -->
                <div class="status-summary-grid">
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">⚠</span>
                            <span class="card-label">Compromised</span>
                        </div>
                        <div class="card-number">{{ $wa_camp_data['compromised'] ?? 0 }}</div>
                        <div class="card-percentage">Users who clicked malicious WhatsApp links</div>
                    </div>
                    <div style="display: table-cell; width: 50%; background: #eff6ff; border: 2px solid #2563eb; border-radius: 8px; padding: 15px;">
                        <div style="font-size: 13px; color: #1e40af; font-weight: 600; margin-bottom: 8px;">Campaign Effectiveness</div>
                        <div style="font-size: 11px; color: #334155; line-height: 1.5;">
                            @php
                                $wa_click_rate = $wa_camp_data['message_sent'] > 0 ? ($wa_camp_data['link_clicked'] / $wa_camp_data['message_sent']) * 100 : 0;
                            @endphp
                            <strong>{{ number_format($wa_click_rate, 1) }}%</strong> of recipients clicked on malicious links. 
                            @if($wa_click_rate > 20)
                                <span style="color: #dc2626; font-weight: 600;">High risk - immediate training needed.</span>
                            @elseif($wa_click_rate > 10)
                                <span style="color: #f59e0b; font-weight: 600;">Moderate risk - additional awareness recommended.</span>
                            @else
                                <span style="color: #16a34a; font-weight: 600;">Good awareness level maintained.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Vishing Campaigns Section -->
            <div class="campaign-type-section">
                <h3 style="font-size: 16px; color: #1e293b; margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="display: inline-block; width: 24px; height: 24px; background: #dc2626; border-radius: 50%; color: white; text-align: center; line-height: 24px; margin-right: 8px; font-size: 14px;">☎</span>
                    AI Vishing Campaigns
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">AI-powered voice phishing attacks to test phone security awareness</p>
                
                <!-- AI Vishing Campaign Metrics in small cards -->
                <div style="display: table; width: 100%; border-spacing: 8px 0; margin-bottom: 10px;">
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Total Campaigns</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $ai_camp_data['ai_vishing'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Calls Sent</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $ai_camp_data['calls_sent'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Calls Received</div>
                        <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $ai_camp_data['calls_received'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Completed Calls</div>
                        <div style="font-size: 24px; font-weight: bold; color: #2563eb;">{{ $ai_camp_data['completed_calls'] ?? 0 }}</div>
                    </div>
                    <div style="display: table-cell; width: 20%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Compromised</div>
                        <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $ai_camp_data['compromised'] ?? 0 }}</div>
                    </div>
                </div>
                
                <!-- Summary cards for AI Vishing Campaigns -->
                <div class="status-summary-grid">
                    <div class="status-summary-card success">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">✓</span>
                            <span class="card-label">Completed Calls</span>
                        </div>
                        <div class="card-number">{{ $ai_camp_data['completed_calls'] ?? 0 }}</div>
                        <div class="card-percentage">Full conversation simulations completed</div>
                    </div>
                    <div class="status-summary-card danger">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="card-icon">⚠</span>
                            <span class="card-label">Compromised</span>
                        </div>
                        <div class="card-number">{{ $ai_camp_data['compromised'] ?? 0 }}</div>
                        <div class="card-percentage">Users who fell for voice phishing</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECOMMENDATIONS -->
        <div class="section no-break">
            <!-- Removed question mark and emoji from section title -->
            <h2 class="section-title">Recommendations & Action Items</h2>
            <div class="recommendation-box">
                <h3>Priority Actions:</h3>
                <ul>
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
                <div class="alert-box alert-success no-break">
                    <strong>✓ EXCELLENT WORK:</strong> Your platform maintains a strong security posture with a risk score of {{ $riskScore }}/100. Continue your current security practices and stay vigilant.
                </div>
            @endif
        </div>

        <!-- FOOTER -->
        <div class="footer no-break">
            <p><strong>{{ $company_name }}</strong> - Platform Security Report</p>
            <p>This report is confidential and intended for internal use only.</p>
            <p>Generated on {{ date('F d, Y \a\t H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
