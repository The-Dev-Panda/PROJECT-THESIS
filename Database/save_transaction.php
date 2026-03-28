<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

function requireStaffSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sessionStaffId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if (empty($sessionStaffId) || empty($_SESSION['user_type']) || strtolower((string)$_SESSION['user_type']) !== 'staff') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login as staff.']);
        exit();
    }
    return (int)$sessionStaffId;
}

try {
    $staffId = requireStaffSession();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $customerType  = isset($input['customer_type'])  ? trim((string)$input['customer_type'])  : '';
    $memberRef     = isset($input['member_ref'])      ? trim((string)$input['member_ref'])      : '';
    $customerName  = isset($input['customer_name'])   ? trim((string)$input['customer_name'])   : '';
    $amount        = isset($input['amount'])          ? (float)$input['amount']                 : 0;
    $paymentMethod = isset($input['payment_method'])  ? trim((string)$input['payment_method'])  : '';
    $paidFor       = isset($input['paid_for'])        ? trim((string)$input['paid_for'])        : '';
    $notes         = isset($input['notes'])           ? trim((string)$input['notes'])           : '';

    if ($customerType !== 'member' && $customerType !== 'non-member') throw new Exception('Invalid customer type');
    if ($amount <= 0)                                                  throw new Exception('Amount must be greater than zero');
    if ($paymentMethod !== 'Cash' && $paymentMethod !== 'GCash')       throw new Exception('Invalid payment method');
    if ($paidFor === '')                                               throw new Exception('Paid For is required');
    if ($customerType === 'member'     && $memberRef === '')           throw new Exception('Member ID is required');
    if ($customerType === 'non-member' && $customerName === '')        throw new Exception('Customer name is required');

    require_once __DIR__ . '/../Login/connection.php';
    $db = $pdo;
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);

    $now  = date('Y-m-d H:i:s');
    $date = date('m/d/Y');
    $time = date('h:i:s A');

    $userId = null;
    if ($customerType === 'member' && $memberRef !== '') {
        if (ctype_digit($memberRef)) {
            $userStmt = $db->prepare('SELECT id, username, first_name, last_name FROM users WHERE id = :id LIMIT 1');
            $userStmt->execute([':id' => (int)$memberRef]);
        } else {
            $userStmt = $db->prepare('SELECT id, username, first_name, last_name FROM users WHERE username = :username LIMIT 1');
            $userStmt->execute([':username' => $memberRef]);
        }
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userId = (int)$user['id'];
            if ($customerName === '') {
                $firstName    = trim((string)($user['first_name'] ?? ''));
                $lastName     = trim((string)($user['last_name']  ?? ''));
                $fullName     = trim($firstName . ' ' . $lastName);
                $username     = trim((string)($user['username']   ?? ''));
                $customerName = $fullName !== '' ? $fullName : ($username !== '' ? $username : $memberRef);
            }
        }
        if ($customerName === '') $customerName = $memberRef;
    }

    $status        = 'Confirmed';
    $receiptNumber = 'RCP' . date('Ymd') . '-' . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);

    $desc = "Paid For: " . $paidFor;
    if ($notes !== '') $desc .= " | Notes: " . $notes;

    $insertStmt = $db->prepare('
        INSERT INTO transactions
            (receipt_number, customer_type, user_id, customer_name, amount,
             payment_method, staff_id, status, `desc`, transaction_date)
        VALUES
            (:receipt_number, :customer_type, :user_id, :customer_name, :amount,
             :payment_method, :staff_id, :status, :desc, :transaction_date)
    ');
    $insertStmt->execute([
        ':receipt_number'   => $receiptNumber,
        ':customer_type'    => $customerType,
        ':user_id'          => $userId,
        ':customer_name'    => $customerName !== '' ? $customerName : null,
        ':amount'           => $amount,
        ':payment_method'   => $paymentMethod,
        ':staff_id'         => $staffId,
        ':status'           => $status,
        ':desc'             => $desc,
        ':transaction_date' => $now,
    ]);

    $receipt = [
        'receiptNumber' => $receiptNumber,
        'customerType'  => $customerType,
        'memberId'      => $memberRef    !== '' ? $memberRef    : null,
        'customerName'  => $customerName !== '' ? $customerName : null,
        'amount'        => $amount,
        'method'        => $paymentMethod,
        'paidFor'       => $paidFor,
        'notes'         => $notes,
        'status'        => $status,
        'date'          => $date,
        'time'          => $time,
    ];

    echo json_encode(['success' => true, 'receipt' => $receipt]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}