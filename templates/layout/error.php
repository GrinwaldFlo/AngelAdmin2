<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

// Get support email from configuration
$supportEmail = 'support@example.com'; // Default fallback
try {
    // Try to get from Email configuration first (CakePHP standard)
    $configEmail = Configure::read('Email.default.from');
    if (!empty($configEmail)) {
        $supportEmail = is_array($configEmail) ? array_keys($configEmail)[0] : $configEmail;
    } else {
        // Try to get from application's configurations table
        $configurationsTable = TableRegistry::getTableLocator()->get('Configurations');
        $senderEmailConfig = $configurationsTable->find()
            ->where(['label' => 'email'])
            ->first();
        
        if (!empty($senderEmailConfig) && !empty($senderEmailConfig->value)) {
            $supportEmail = $senderEmailConfig->value;
        }
    }
} catch (Exception $e) {
    // Keep default fallback email if database or configuration is not available
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>
        <?= $this->fetch('title') ?> | <?= __('Error') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <!-- Bootstrap CSS -->
    <?= $this->Html->css('BootstrapUI.bootstrap.min') ?>
    <?= $this->Html->css(['BootstrapUI./font/bootstrap-icons', 'BootstrapUI./font/bootstrap-icon-sizes']) ?>
    
    <!-- Custom Error Styles -->
    <?= $this->Html->css('error-modern') ?>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Inline Styles for Enhanced Layout -->
    <style>
        :root {
            --error-primary: #e74c3c;
            --error-secondary: #f8f9fa;
            --error-accent: #3498db;
            --error-warning: #f39c12;
            --error-success: #27ae60;
            --error-dark: #2c3e50;
            --error-light: #ecf0f1;
            --error-max-width: 800px;
            --error-padding: 2rem;
            --error-margin: 1.5rem;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding: var(--error-margin);
            box-sizing: border-box;
        }

        /* Main container with proper framing */
        .error-container {
            min-height: calc(100vh - 3rem);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            max-width: var(--error-max-width);
            margin: 0 auto;
            padding: var(--error-padding);
            box-sizing: border-box;
        }

        /* Error card with improved framing */
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            padding: var(--error-padding);
            width: 100%;
            max-width: 100%;
            text-align: center;
            position: relative;
            animation: slideInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: var(--error-margin) 0;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            font-size: 4rem;
            color: var(--error-primary);
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--error-dark);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .error-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .error-message {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: var(--error-primary);
            font-weight: 500;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--error-accent), #5dade2);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: transparent;
            color: var(--error-dark);
            border: 2px solid var(--error-dark);
        }

        .btn-secondary:hover {
            background: var(--error-dark);
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }

        /* Floating shapes for visual appeal */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 70%;
            left: 80%;
            animation-delay: -2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 40%;
            left: 85%;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.8;
            }
        }

        .flash-messages {
            margin-bottom: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            :root {
                --error-padding: 1.5rem;
                --error-margin: 1rem;
                --error-max-width: calc(100vw - 2rem);
            }
            
            body {
                padding: 0.5rem;
            }
            
            .error-container {
                padding: 1rem;
                min-height: calc(100vh - 1rem);
            }
            
            .error-card {
                padding: 1.5rem 1rem;
                margin: 0.5rem 0;
                border-radius: 16px;
            }
            
            .error-title {
                font-size: 2rem;
            }
            
            .error-subtitle {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
            
            .error-icon {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            :root {
                --error-padding: 1rem;
            }
            
            .error-card {
                padding: 1rem;
            }
            
            .error-title {
                font-size: 1.75rem;
            }
            
            .error-icon {
                font-size: 2.5rem;
            }
        }

        /* Large screens */
        @media (min-width: 1200px) {
            :root {
                --error-max-width: 900px;
                --error-padding: 3rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .error-card {
                background: rgba(26, 32, 46, 0.95);
                color: #e2e8f0;
            }
            
            .error-title {
                color: #e2e8f0;
            }
            
            .btn-secondary {
                color: #e2e8f0;
                border-color: #e2e8f0;
            }
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            .error-card {
                border: 3px solid #000;
                background: #fff;
            }
            
            .error-title {
                color: #000;
            }
            
            .btn {
                border: 2px solid #000;
            }
        }

        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .error-card,
            .shape,
            .error-icon,
            .btn {
                animation: none;
                transition: none;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white !important;
                padding: 1rem !important;
            }
            
            .error-container {
                min-height: auto !important;
                padding: 1rem !important;
            }
            
            .error-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                background: white !important;
            }
            
            .floating-shapes {
                display: none !important;
            }
        }
    </style>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-emoji-frown"></i>
            </div>
           
            <h1 class="error-title">
                <?= __('Oops! Something went wrong') ?>
            </h1>
            
            <p class="error-subtitle">
                <?= __('Don\'t worry, it happens to the best of us!') ?>
            </p>

            <div class="flash-messages">
                <?= $this->Flash->render() ?>
            </div>

            <div class="error-message">
                <?= $this->fetch('content') ?>
            </div>

            <div class="error-actions">
                <?= $this->Html->link(
                    '<i class="bi bi-house-door me-2"></i>' . __('Back to Home'),
                    '/',
                    [
                        'class' => 'btn btn-primary',
                        'escape' => false
                    ]
                ) ?>
                
                <a href="javascript:history.back()" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i><?= __('Previous Page') ?>
                </a>
            </div>

            <div class="mt-4">
                <small class="text-muted">
                    <?= __('If the problem persists,') ?>
                    <?= $this->Html->link(
                        __('contact our support'),
                        'mailto:' . h($supportEmail),
                        ['class' => 'btn btn-secondary btn-sm ms-1']
                    ) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <?= $this->Html->script('BootstrapUI.bootstrap.bundle.min') ?>
    
    <!-- Custom JS for enhanced interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add ripple effect to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.6)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple-animation 600ms linear';
                    ripple.style.pointerEvents = 'none';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>

    <?= $this->fetch('script') ?>
</body>
</html>
