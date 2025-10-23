<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole POWER Analytics - AI-Driven Insights</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* ADHD-Optimized Power Colors */
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            
            /* Performance Colors */
            --excellent: #059669;
            --good: #65A30D;
            --average: #D97706;
            --poor: #DC2626;
            --critical: #7C2D12;
            
            /* AI Insight Colors */
            --ai-primary: #7C3AED;
            --ai-secondary: #A855F7;
            --prediction: #EC4899;
            --optimization: #06B6D4;
            
            /* Neutrals */
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-500: #64748B;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0F172A;
            
            --bg-primary: #FAFAFB;
            --bg-secondary: #FFFFFF;
            --bg-glass: rgba(255, 255, 255, 0.1);
            
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-glow: 0 0 20px rgba(79, 70, 229, 0.3);
            
            --radius-xl: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-900);
            overflow-x: hidden;
        }

        .analytics-container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* ==================== HEADER SECTION ==================== */

        .power-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--shadow-xl);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-left p {
            font-size: 18px;
            opacity: 0.9;
        }

        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
            padding: 12px 24px;
            border-radius: 30px;
            color: white;
            font-weight: 700;
            animation: pulse-glow 3s infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(124, 58, 237, 0.5); }
            50% { box-shadow: 0 0 30px rgba(124, 58, 237, 0.8); }
        }

        .header-actions {
            display: flex;
            gap: 16px;
        }

        .power-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .power-btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .power-btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* ==================== AI INSIGHTS GRID ==================== */

        .ai-insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 2rem;
        }

        .insight-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-200);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .insight-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--ai-primary), var(--ai-secondary));
        }

        .insight-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-glow);
        }

        .insight-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 1.5rem;
        }

        .insight-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
        }

        .insight-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--gray-900);
        }

        .insight-subtitle {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .insight-metric {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 12px;
            line-height: 1;
        }

        .insight-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .trend-up { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .trend-down { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .trend-stable { background: rgba(59, 130, 246, 0.1); color: var(--info); }

        /* ==================== PERFORMANCE MATRIX ==================== */

        .performance-matrix {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-200);
        }

        .matrix-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .matrix-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .employee-matrix {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .employee-performance-card {
            background: var(--bg-secondary);
            border: 2px solid var(--gray-200);
            border-radius: 20px;
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
        }

        .employee-performance-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .performance-excellent { border-left: 6px solid var(--excellent); }
        .performance-good { border-left: 6px solid var(--good); }
        .performance-average { border-left: 6px solid var(--average); }
        .performance-poor { border-left: 6px solid var(--poor); }

        .employee-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 1rem;
        }

        .employee-avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), #6366F1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 18px;
        }

        .employee-info h4 {
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .employee-info p {
            color: var(--gray-600);
            font-size: 14px;
        }

        .performance-score {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 18px;
            color: white;
        }

        .score-excellent { background: var(--excellent); }
        .score-good { background: var(--good); }
        .score-average { background: var(--average); }
        .score-poor { background: var(--poor); }

        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 1rem;
        }

        .metric-mini {
            text-align: center;
            padding: 12px 8px;
            background: var(--gray-50);
            border-radius: 12px;
        }

        .metric-mini-value {
            font-weight: 800;
            font-size: 16px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .metric-mini-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            font-weight: 600;
        }

        /* ==================== PREDICTIONS PANEL ==================== */

        .predictions-panel {
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .predictions-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .predictions-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .predictions-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .predictions-title {
            font-size: 28px;
            font-weight: 900;
        }

        .predictions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .prediction-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
        }

        .prediction-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .prediction-value {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .prediction-confidence {
            font-size: 12px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ==================== OPTIMIZATION RECOMMENDATIONS ==================== */

        .optimization-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-200);
        }

        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        .recommendation-card {
            background: linear-gradient(135deg, var(--optimization), #0891B2);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .recommendation-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .recommendation-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .recommendation-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .recommendation-title {
            font-size: 18px;
            font-weight: 800;
        }

        .recommendation-impact {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 1rem;
            display: inline-block;
        }

        /* ==================== REAL-TIME ALERTS ==================== */

        .alerts-sidebar {
            position: fixed;
            top: 2rem;
            right: 2rem;
            width: 350px;
            max-height: 600px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-200);
            z-index: 1000;
            overflow: hidden;
        }

        .alerts-header {
            background: linear-gradient(135deg, var(--danger), #F87171);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alerts-list {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
        }

        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid var(--warning);
            background: var(--gray-50);
        }

        .alert-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--warning);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .alert-description {
            font-size: 12px;
            color: var(--gray-600);
        }

        .alert-time {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* ==================== RESPONSIVE DESIGN ==================== */

        @media (max-width: 1200px) {
            .ai-insights-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
            
            .employee-matrix {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .alerts-sidebar {
                position: relative;
                width: 100%;
                margin: 2rem 0;
            }
        }

        @media (max-width: 768px) {
            .analytics-container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .ai-insights-grid,
            .employee-matrix,
            .predictions-grid,
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ==================== LOADING ANIMATIONS ==================== */

        .loading-shimmer {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* ==================== CHARTS PLACEHOLDER ==================== */

        .chart-container {
            height: 300px;
            background: var(--gray-50);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
            border: 2px dashed var(--gray-300);
            color: var(--gray-500);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <!-- Power Header -->
        <div class="power-header">
            <div class="header-content">
                <div class="header-left">
                    <h1>🧠 AI-Powered Analytics</h1>
                    <p>Real-time insights and performance optimization for your team</p>
                </div>
                <div class="header-actions">
                    <div class="ai-badge">
                        <i class="fas fa-robot"></i>
                        AI Active
                    </div>
                    <button class="power-btn power-btn-primary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </button>
                </div>
            </div>
        </div>

        <!-- AI Insights Grid -->
        <div class="ai-insights-grid">
            <div class="insight-card">
                <div class="insight-header">
                    <div class="insight-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="insight-title">Productivity Trend</div>
                        <div class="insight-subtitle">Team performance over last 30 days</div>
                    </div>
                </div>
                <div class="insight-metric" style="color: var(--success);">+18%</div>
                <div class="insight-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    Above average performance
                </div>
            </div>

            <div class="insight-card">
                <div class="insight-header">
                    <div class="insight-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="insight-title">Burnout Risk</div>
                        <div class="insight-subtitle">Employees at risk of burnout</div>
                    </div>
                </div>
                <div class="insight-metric" style="color: var(--warning);">3</div>
                <div class="insight-trend trend-down">
                    <i class="fas fa-arrow-down"></i>
                    2 fewer than last week
                </div>
            </div>

            <div class="insight-card">
                <div class="insight-header">
                    <div class="insight-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <div class="insight-title">Revenue Impact</div>
                        <div class="insight-subtitle">Projected monthly revenue</div>
                    </div>
                </div>
                <div class="insight-metric" style="color: var(--success);">$47.2K</div>
                <div class="insight-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    12% above target
                </div>
            </div>

            <div class="insight-card">
                <div class="insight-header">
                    <div class="insight-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="insight-title">Delivery Speed</div>
                        <div class="insight-subtitle">Average project completion time</div>
                    </div>
                </div>
                <div class="insight-metric" style="color: var(--info);">8.3d</div>
                <div class="insight-trend trend-stable">
                    <i class="fas fa-minus"></i>
                    Consistent with targets
                </div>
            </div>
        </div>

        <!-- Team Performance Matrix -->
        <div class="performance-matrix">
            <div class="matrix-header">
                <h2 class="matrix-title">
                    <i class="fas fa-users"></i>
                    Team Performance Matrix
                </h2>
                <button class="power-btn power-btn-primary" onclick="generateDetailedReport()">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
            
            <div class="employee-matrix" id="performance-matrix">
                <!-- Performance cards will be loaded here -->
                <div class="employee-performance-card performance-excellent">
                    <div class="performance-score score-excellent">95</div>
                    <div class="employee-header">
                        <div class="employee-avatar">JS</div>
                        <div class="employee-info">
                            <h4>John Smith</h4>
                            <p>Creative Director</p>
                        </div>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-mini">
                            <div class="metric-mini-value">12</div>
                            <div class="metric-mini-label">Tasks Done</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">98%</div>
                            <div class="metric-mini-label">On Time</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">4.8</div>
                            <div class="metric-mini-label">Quality</div>
                        </div>
                    </div>
                </div>

                <div class="employee-performance-card performance-good">
                    <div class="performance-score score-good">82</div>
                    <div class="employee-header">
                        <div class="employee-avatar">SJ</div>
                        <div class="employee-info">
                            <h4>Sarah Johnson</h4>
                            <p>Video Editor</p>
                        </div>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-mini">
                            <div class="metric-mini-value">9</div>
                            <div class="metric-mini-label">Tasks Done</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">89%</div>
                            <div class="metric-mini-label">On Time</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">4.5</div>
                            <div class="metric-mini-label">Quality</div>
                        </div>
                    </div>
                </div>

                <div class="employee-performance-card performance-average">
                    <div class="performance-score score-average">67</div>
                    <div class="employee-header">
                        <div class="employee-avatar">MC</div>
                        <div class="employee-info">
                            <h4>Mike Chen</h4>
                            <p>Motion Designer</p>
                        </div>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-mini">
                            <div class="metric-mini-value">6</div>
                            <div class="metric-mini-label">Tasks Done</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">75%</div>
                            <div class="metric-mini-label">On Time</div>
                        </div>
                        <div class="metric-mini">
                            <div class="metric-mini-value">4.1</div>
                            <div class="metric-mini-label">Quality</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Predictions Panel -->
        <div class="predictions-panel">
            <div class="predictions-header">
                <div class="predictions-icon">
                    <i class="fas fa-crystal-ball"></i>
                </div>
                <div>
                    <h2 class="predictions-title">AI Predictions & Forecasts</h2>
                    <p style="opacity: 0.9;">Machine learning insights for strategic planning</p>
                </div>
            </div>
            
            <div class="predictions-grid">
                <div class="prediction-item">
                    <div class="prediction-label">Next Month Revenue</div>
                    <div class="prediction-value">$52.4K</div>
                    <div class="prediction-confidence">
                        <i class="fas fa-check-circle"></i>
                        87% confidence
                    </div>
                </div>

                <div class="prediction-item">
                    <div class="prediction-label">Project Completion</div>
                    <div class="prediction-value">14 days</div>
                    <div class="prediction-confidence">
                        <i class="fas fa-chart-line"></i>
                        Current projects avg
                    </div>
                </div>

                <div class="prediction-item">
                    <div class="prediction-label">Team Capacity</div>
                    <div class="prediction-value">78%</div>
                    <div class="prediction-confidence">
                        <i class="fas fa-battery-three-quarters"></i>
                        Optimal utilization
                    </div>
                </div>

                <div class="prediction-item">
                    <div class="prediction-label">Client Satisfaction</div>
                    <div class="prediction-value">4.6/5</div>
                    <div class="prediction-confidence">
                        <i class="fas fa-star"></i>
                        Based on feedback trends
                    </div>
                </div>
            </div>
        </div>

        <!-- Optimization Recommendations -->
        <div class="optimization-section">
            <div class="matrix-header">
                <h2 class="matrix-title">
                    <i class="fas fa-lightbulb"></i>
                    AI Optimization Recommendations
                </h2>
            </div>
            
            <div class="recommendations-grid">
                <div class="recommendation-card">
                    <div class="recommendation-header">
                        <div class="recommendation-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="recommendation-title">Redistribute Workload</div>
                    </div>
                    <p>Mike Chen is currently overloaded while Emily Davis has 20% available capacity. Redistributing 2-3 tasks could improve overall team efficiency by 15%.</p>
                    <div class="recommendation-impact">High Impact • +15% Efficiency</div>
                </div>

                <div class="recommendation-card">
                    <div class="recommendation-header">
                        <div class="recommendation-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="recommendation-title">Optimize Meeting Times</div>
                    </div>
                    <p>Team meetings scheduled during peak productivity hours (10-11 AM) are reducing output by 8%. Consider moving to 2-3 PM slot for better focus.</p>
                    <div class="recommendation-impact">Medium Impact • +8% Productivity</div>
                </div>

                <div class="recommendation-card">
                    <div class="recommendation-header">
                        <div class="recommendation-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="recommendation-title">Skill Development</div>
                    </div>
                    <p>Investing in advanced Motion Graphics training for Sarah Johnson could unlock $12K additional revenue potential in next quarter.</p>
                    <div class="recommendation-impact">High Impact • +$12K Revenue</div>
                </div>

                <div class="recommendation-card">
                    <div class="recommendation-header">
                        <div class="recommendation-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="recommendation-title">Tool Optimization</div>
                    </div>
                    <p>Upgrading to collaborative design tools could reduce revision cycles by 30% and improve client satisfaction scores.</p>
                    <div class="recommendation-impact">Medium Impact • +30% Efficiency</div>
                </div>
            </div>
        </div>

        <!-- Chart Placeholders -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div class="performance-matrix">
                <div class="matrix-header">
                    <h3 class="matrix-title">Productivity Trends</h3>
                </div>
                <div class="chart-container">
                    📈 Interactive productivity trend chart would display here
                </div>
            </div>

            <div class="performance-matrix">
                <div class="matrix-header">
                    <h3 class="matrix-title">Revenue Forecast</h3>
                </div>
                <div class="chart-container">
                    💰 Revenue prediction chart with confidence intervals
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Alerts Sidebar -->
    <div class="alerts-sidebar">
        <div class="alerts-header">
            <i class="fas fa-bell"></i>
            Real-time Alerts
        </div>
        <div class="alerts-list">
            <div class="alert-item">
                <div class="alert-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Deadline Risk Detected</div>
                    <div class="alert-description">Nike Campaign project may miss deadline. Consider resource reallocation.</div>
                    <div class="alert-time">2 minutes ago</div>
                </div>
            </div>

            <div class="alert-item">
                <div class="alert-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Productivity Spike</div>
                    <div class="alert-description">Team productivity up 23% this afternoon. Great momentum!</div>
                    <div class="alert-time">15 minutes ago</div>
                </div>
            </div>

            <div class="alert-item">
                <div class="alert-icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Overtime Alert</div>
                    <div class="alert-description">Mike Chen approaching 45h this week. Consider workload adjustment.</div>
                    <div class="alert-time">1 hour ago</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==================== REAL-TIME DATA SIMULATION ====================
        
        function simulateRealTimeUpdates() {
            // Simulate AI insights updating
            setInterval(() => {
                updateInsightMetrics();
                updateAlerts();
            }, 30000); // Update every 30 seconds
        }

        function updateInsightMetrics() {
            const metrics = document.querySelectorAll('.insight-metric');
            metrics.forEach(metric => {
                // Add subtle animation to show data is live
                metric.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    metric.style.transform = 'scale(1)';
                }, 200);
            });
        }

        function updateAlerts() {
            const alertsList = document.querySelector('.alerts-list');
            // Simulate new alert
            if (Math.random() > 0.7) {
                const newAlert = createAlertItem();
                alertsList.insertBefore(newAlert, alertsList.firstChild);
                
                // Remove old alerts to keep list manageable
                if (alertsList.children.length > 10) {
                    alertsList.removeChild(alertsList.lastChild);
                }
            }
        }

        function createAlertItem() {
            const alerts = [
                {
                    icon: 'fas fa-star',
                    title: 'High Client Rating',
                    description: 'TechCorp project received 5-star rating from client.',
                    time: 'Just now'
                },
                {
                    icon: 'fas fa-check-circle',
                    title: 'Task Completed',
                    description: 'Sarah Johnson completed video editing ahead of schedule.',
                    time: 'Just now'
                },
                {
                    icon: 'fas fa-trending-up',
                    title: 'Efficiency Gain',
                    description: 'Team efficiency increased by 5% in the last hour.',
                    time: 'Just now'
                }
            ];
            
            const randomAlert = alerts[Math.floor(Math.random() * alerts.length)];
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-item';
            alertDiv.innerHTML = `
                <div class="alert-icon">
                    <i class="${randomAlert.icon}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">${randomAlert.title}</div>
                    <div class="alert-description">${randomAlert.description}</div>
                    <div class="alert-time">${randomAlert.time}</div>
                </div>
            `;
            
            return alertDiv;
        }

        // ==================== INTERACTIVE FEATURES ====================
        
        function generateDetailedReport() {
            alert('🤖 AI is generating a comprehensive performance report...\n\n📊 This would include:\n• Individual performance analytics\n• Predictive insights\n• Optimization recommendations\n• Risk assessments\n• Revenue forecasts\n• Resource allocation suggestions\n\n⏱️ Report will be ready in 2-3 minutes and emailed to you.');
        }

        function optimizeWorkload() {
            alert('🧠 AI Workload Optimizer activated!\n\n🔄 Analyzing current team capacity...\n📈 Calculating optimal task distribution...\n⚡ Generating reallocation suggestions...\n\n✅ Optimization complete! Implementing changes will improve team efficiency by 18%.');
        }

        // ==================== KEYBOARD SHORTCUTS ====================
        
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r': e.preventDefault(); generateDetailedReport(); break;
                    case 'o': e.preventDefault(); optimizeWorkload(); break;
                    case 'h': e.preventDefault(); showKeyboardHelp(); break;
                }
            }
        });

        function showKeyboardHelp() {
            alert('⌨️ Keyboard Shortcuts:\n\nCtrl+R - Generate Report\nCtrl+O - Optimize Workload\nCtrl+H - Show this help\n\n💡 Pro tip: Use these shortcuts for faster navigation!');
        }

        // ==================== INITIALIZATION ====================
        
        document.addEventListener('DOMContentLoaded', function() {
            simulateRealTimeUpdates();
            
            // Add loading animations on first load
            setTimeout(() => {
                document.querySelectorAll('.loading-shimmer').forEach(el => {
                    el.classList.remove('loading-shimmer');
                });
            }, 1500);
        });

        // ==================== RESPONSIVE BEHAVIOR ====================
        
        window.addEventListener('resize', function() {
            // Hide alerts sidebar on mobile
            const alertsSidebar = document.querySelector('.alerts-sidebar');
            if (window.innerWidth < 1200) {
                alertsSidebar.style.position = 'relative';
                alertsSidebar.style.width = '100%';
                alertsSidebar.style.margin = '2rem 0';
            } else {
                alertsSidebar.style.position = 'fixed';
                alertsSidebar.style.width = '350px';
                alertsSidebar.style.margin = '0';
            }
        });
    </script>
</body>
</html>