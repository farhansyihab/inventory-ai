#!/bin/bash
echo "menuju root project"
cd ..
echo "posisi root project"
echo "=== Inventory AI Test Script ==="
echo ""

# Check PHP version
echo "1. PHP Version:"
php --version
echo ""

# Check MongoDB extension
echo "2. MongoDB Extension:"
if php -m | grep -q mongodb; then
    echo "✅ MongoDB extension loaded"
else
    echo "❌ MongoDB extension NOT loaded"
fi
echo ""

# Test basic autoload
echo "3. Basic Autoload:"
php -r "
if (@require 'vendor/autoload.php') {
    echo '✅ Vendor autoload working';
} else {
    echo '❌ Vendor autoload failed';
}
"
echo ""
echo ""

# Test our classes
echo "4. Our Classes:"
php -r "
if (!@require 'vendor/autoload.php') {
    echo '❌ Autoload failed';
    exit;
}

\$items = [
    'App\Config\MongoDBManager' => 'class',
    'App\Repository\UserRepository' => 'class',
    'App\Model\User' => 'class', 
    'App\Repository\IRepository' => 'interface',
    'MongoDB\Client' => 'class'
];

\$allGood = true;
foreach (\$items as \$name => \$type) {
    if (\$type === 'class') {
        \$exists = class_exists(\$name);
    } else {
        \$exists = interface_exists(\$name);
    }
    
    echo \$name . ': ' . (\$exists ? '✅' : '❌') . \"\n\";
    if (!\$exists) \$allGood = false;
}

echo \$allGood ? '✅ All classes/interfaces found' : '❌ Some items missing';
"
echo ""

echo "=== Test completed ==="