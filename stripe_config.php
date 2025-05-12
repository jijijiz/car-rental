<?php
// Stripe Configuration
require 'vendor/autoload.php';

// Stripe API Keys
// 测试模式密钥 - 开发时使用
define('STRIPE_SECRET_KEY', 'sk_test_51QyF5CRoQoSQBnvcUj9L5OtS59Aw0e9vDIrNrpGHYDrY8nBt4iCKjjasG74em5hfUiFBXkmqrhzetPUfIWOAspZy00SZhcYRrt'); // 从 Stripe Dashboard 复制
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51QyF5CRoQoSQBnvcV61yFYde45qDdIyHbE516iTPbx2zbMbklVEGVMGKNQ9feygIy2DQbSsdBVcw1IAQPJ8G6QWp000LdBUJY7'); // 从 Stripe Dashboard 复制

// 生产模式密钥 - 上线时使用
// define('STRIPE_SECRET_KEY', 'sk_live_...');
// define('STRIPE_PUBLISHABLE_KEY', 'pk_live_...');

// 支付货币设置
define('STRIPE_CURRENCY', 'SGD');

// 测试环境 webhook secret (学校项目用，实际项目中不要这样做)
define('STRIPE_WEBHOOK_SECRET', 'test_webhook_secret123');

// 初始化 Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// 金额格式化函数 (转换为分)
function formatStripeAmount($amount) {
    return (int)($amount * 100);
}

// 显示金额格式化函数 (从分转换回元)
function formatDisplayAmount($stripeAmount) {
    return number_format($stripeAmount / 100, 2);
}

// 测试用的卡号信息
define('TEST_CARD_SUCCESS', '4242424242424242'); // 测试成功支付
define('TEST_CARD_AUTHENTICATION', '4000002500003155'); // 需要验证
define('TEST_CARD_DECLINE', '4000000000009995'); // 支付失败 