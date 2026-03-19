<?php
/**
 * ASCA Selecção - System Constants
 */

// Application
define('APP_NAME', 'ASCA Selecção');
define('APP_VERSION', '1.0.0');
define('APP_CURRENCY', 'MT');
define('APP_CURRENCY_FULL', 'MZN');
define('APP_LOCALE', 'pt_MZ');

// Timezone
date_default_timezone_set('Africa/Maputo');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('PAGES_PATH', BASE_PATH . '/pages');
define('SERVICES_PATH', BASE_PATH . '/services');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Base URL (auto-detect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
// Navigate up if we're in a subdirectory like /config or /api
$baseDir = $scriptDir;
$subDirs = ['config', 'api', 'pages', 'pages/member', 'services', 'includes'];
foreach ($subDirs as $sub) {
    if (str_ends_with($baseDir, '/' . $sub)) {
        $baseDir = substr($baseDir, 0, -strlen('/' . $sub));
        break;
    }
}
define('BASE_URL', $protocol . '://' . $host . $baseDir);

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
define('ROLE_MEMBER', 'member');

// Business defaults (overridden by cycle settings)
define('DEFAULT_JOIA_AMOUNT', 1000.00);
define('DEFAULT_MIN_MONTHLY', 2000.00);
define('DEFAULT_MIN_LOAN_AMOUNT', 500.00);
define('DEFAULT_MAX_MONTHLY', 5000.00);

define('WHATSAPP_TEMPLATE_LOAN_CREATED', 'loan_created_template');
define('WHATSAPP_TEMPLATE_REPAYMENT_REMINDER', 'repayment_reminder_template');
define('DEFAULT_MONTHLY_DEADLINE_DAY', 10);
define('DEFAULT_LATE_FEE_PCT', 15.00);
define('DEFAULT_LOAN_INTEREST_PCT', 15.00);
define('DEFAULT_LOAN_REPAYMENT_DAYS', 30);
define('DEFAULT_MIN_LOAN_MOVEMENT', 50000.00);
define('DEFAULT_FIXED_INTEREST_ENTITLEMENT', 7500.00);
