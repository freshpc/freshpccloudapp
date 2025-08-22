<?php
declare(strict_types=1);

/*
 * FreshPC Cloud - Front Controller & API
 * - Canonicalizes trailing slashes (/admin/ -> /admin, etc.)
 * - Serves pages: /, /login, /admin, /engineer, /password-help
 * - APIs: auth, clients, users, tasks, task events, photo upload, signature/finish
 */

// ---------- Session & config ----------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',           // cookie blijft host-specifiek; www → apex redirect is aan te raden
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();
header_remove('X-Powered-By');
require_once __DIR__ . '/config.php'; // verwacht DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
require_once __DIR__ . '/lib/minipdf.php';

if (!defined('ADMIN_WORKBON_EMAIL')) {
  define('ADMIN_WORKBON_EMAIL', 'admin@freshpccloud.nl');
}

// Debug fingerprint (mag later weg)
header('X-FPC-Index: 2025-08-18-c');

// ---------- Helpers ----------
function json_out($data, int $code=200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function serveHTML(string $file): void {
  $full = __DIR__ . '/' . ltrim($file, '/');
  if (!is_file($full)) { http_response_code(404); echo "File not found: " . htmlspecialchars($file); exit; }
  $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
  
  // For PHP files, include them to execute the PHP code
  if ($ext === 'php') {
    header('Content-Type: text/html; charset=utf-8');
    include $full;
    exit;
  }
  
  // For static files, serve them directly
  $ctype = 'text/html; charset=utf-8';
  if ($ext === 'css') $ctype = 'text/css';
  if ($ext === 'js')  $ctype = 'application/javascript; charset=utf-8';
  header('Content-Type: '.$ctype);
  readfile($full);
  exit;
}
function end_session_and_redirect(string $to='/login'): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path']??'/', $p['domain']??'', !empty($p['secure']), !empty($p['httponly']));
  }
  session_destroy();
  header('Location: '.$to);
  exit;
}
function api_need_auth(): void { if (!isset($_SESSION['user_id'])) json_out(['error'=>'unauthorized'], 401); }
function api_need_admin(): void { api_need_auth(); if (($_SESSION['role'] ?? '') !== 'admin') json_out(['error'=>'forbidden'], 403); }

if (!function_exists('pdo')) {
  function pdo(): PDO {
    static $pdo=null; if ($pdo instanceof PDO) return $pdo;
    
    if (DB_TYPE === 'pgsql') {
      $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";sslmode=require";
    } else {
      $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]);
    return $pdo;
  }
}
function task_log(PDO $pdo, int $task_id, string $event, string $details=''): void {
  try { $pdo->prepare("INSERT INTO task_events (task_id,event_type,payload) VALUES (?,?,?)")->execute([$task_id,$event,$details]); } catch(Throwable $e){}
}
function client_full_address_by_id(PDO $pdo, ?int $client_id): string {
  if (!$client_id) return '';
  try{
    $st=$pdo->prepare("SELECT COALESCE(address_street,'') f, COALESCE(address_city,'') c, COALESCE(address_postcode,'') pc, COALESCE(address_country,'') co FROM clients WHERE id=? LIMIT 1");
    $st->execute([$client_id]); $c=$st->fetch(); if(!$c) return '';
    $parts=[]; foreach (['f','c','pc','co'] as $k){ $v=trim((string)($c[$k]??'')); if($v!=='') $parts[]=$v; }
    return trim(implode(' ', $parts));
  }catch(Throwable $e){ return ''; }
}

function generate_task_report(PDO $pdo, int $task_id, string $status): void {
  try {
    // Get task details
    $st = $pdo->prepare("
      SELECT t.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
             CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as engineer_name, u.email as engineer_email,
             CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) as admin_name, a.email as admin_email
      FROM tasks t 
      LEFT JOIN clients c ON t.client_id = c.id
      LEFT JOIN users u ON t.assigned_user_id = u.id
      LEFT JOIN users a ON t.created_by = a.id
      WHERE t.id = ?
    ");
    $st->execute([$task_id]);
    $task = $st->fetch();
    if (!$task) return;

    // Create PDF
    $pdf = new MiniPDF();
    $pdf->SetTitle('FreshPC Cloud Task Report #' . $task_id);
    $pdf->SetAuthor('FreshPC Cloud System');
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 16);
    $pdf->Cell(0, 10, 'FreshPC Cloud - Task Report');
    $pdf->Ln(15);
    
    $pdf->SetFont('Arial', 12);
    $pdf->Cell(0, 6, 'Task ID: ' . $task_id);
    $pdf->Ln();
    $pdf->Cell(0, 6, 'Status: ' . ucfirst($status));
    $pdf->Ln();
    $pdf->Cell(0, 6, 'Date: ' . date('Y-m-d H:i:s'));
    $pdf->Ln(10);
    
    $pdf->Cell(0, 6, 'Task Details:');
    $pdf->Ln();
    $pdf->Cell(0, 6, 'Title: ' . ($task['title'] ?? ''));
    $pdf->Ln();
    $pdf->MultiCell(0, 6, 'Description: ' . ($task['description'] ?? ''));
    $pdf->Ln();
    
    if ($task['client_name']) {
      $pdf->Cell(0, 6, 'Client: ' . $task['client_name']);
      $pdf->Ln();
    }
    
    if ($task['engineer_name']) {
      $pdf->Cell(0, 6, 'Engineer: ' . $task['engineer_name']);
      $pdf->Ln();
    }
    
    if ($status === 'rejected' && !empty($task['rejection_reason'])) {
      $pdf->Ln();
      $pdf->Cell(0, 6, 'Rejection Reason:');
      $pdf->Ln();
      $pdf->MultiCell(0, 6, $task['rejection_reason']);
    }
    
    if ($task['work_notes']) {
      $pdf->Ln();
      $pdf->Cell(0, 6, 'Work Notes:');
      $pdf->Ln();
      $pdf->MultiCell(0, 6, $task['work_notes']);
    }
    
    // Save PDF
    $reportDir = __DIR__ . '/uploads/reports';
    if (!is_dir($reportDir)) @mkdir($reportDir, 0775, true);
    $filename = 'task_' . $task_id . '_' . $status . '_' . date('Ymd_His') . '.pdf';
    $filepath = $reportDir . '/' . $filename;
    file_put_contents($filepath, $pdf->Output('S'));
    
    // Send email notification
    send_task_completion_email($task, $status, $filename);
    
  } catch (Throwable $e) {
    error_log("PDF generation failed for task $task_id: " . $e->getMessage());
  }
}

function send_task_completion_email(array $task, string $status, string $pdfFilename): void {
  try {
    $subject = "FreshPC Cloud - Task " . ucfirst($status) . " #" . $task['id'];
    $message = "Task '{$task['title']}' has been marked as $status.\n\n";
    $message .= "Client: " . ($task['client_name'] ?? 'N/A') . "\n";
    $message .= "Engineer: " . ($task['engineer_name'] ?? 'N/A') . "\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    if ($status === 'rejected') {
      $message .= "Rejection Reason: " . ($task['rejection_reason'] ?? 'Not specified') . "\n\n";
    }
    
    $message .= "A detailed PDF report has been generated and attached.\n\n";
    $message .= "FreshPC Cloud System";
    
    $headers = [
      'From: noreply@freshpccloud.nl',
      'Content-Type: text/plain; charset=UTF-8'
    ];
    
    // Send to admin
    if (!empty($task['admin_email'])) {
      mail($task['admin_email'], $subject, $message, implode("\r\n", $headers));
    }
    
    // Send to engineer
    if (!empty($task['engineer_email'])) {
      mail($task['engineer_email'], $subject, $message, implode("\r\n", $headers));
    }
    
  } catch (Throwable $e) {
    error_log("Email notification failed: " . $e->getMessage());
  }
}

function send_activation_email(string $email, string $fullName, string $token, string $tempPassword, string $role): void {
  try {
    $activationUrl = "https://freshpccloud.nl/activate?token=" . urlencode($token);
    $roleText = ($role === 'admin') ? 'Administrator' : 'Field Engineer';
    
    $subject = "FreshPC Cloud - Account Activation Required";
    $message = "Hello $fullName,\n\n";
    $message .= "Your FreshPC Cloud account has been created as a $roleText.\n\n";
    $message .= "To activate your account, please:\n";
    $message .= "1. Visit this link: $activationUrl\n";
    $message .= "2. Use this temporary password to login: $tempPassword\n";
    $message .= "3. Set your new password during activation\n\n";
    $message .= "Your username is: " . explode('@', $email)[0] . "\n\n";
    $message .= "This activation link will expire in 24 hours.\n\n";
    $message .= "Best regards,\n";
    $message .= "FreshPC Cloud Team";
    
    $headers = [
      'From: noreply@freshpccloud.nl',
      'Content-Type: text/plain; charset=UTF-8'
    ];
    
    mail($email, $subject, $message, implode("\r\n", $headers));
    
  } catch (Throwable $e) {
    error_log("Activation email failed: " . $e->getMessage());
  }
}

// ---------- Router ----------
$raw  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$norm = rtrim($raw, '/'); if ($norm === '') $norm = '/';
// Canonical redirect (behalve root)
if ($norm !== $raw) { header('Location: '.$norm, true, 301); exit; }
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
header('X-FPC-Route: '.$norm);

// --- Public pages ---
if ($norm === '/')                                   { serveHTML('index.html'); }
if ($norm === '/login'         && $method==='GET')   { serveHTML('login.php'); }
if ($norm === '/activate'      && $method==='GET')   { serveHTML('activate.html'); }
if ($norm === '/password-help' && $method==='GET')   { serveHTML('password-help.html'); }
// Logout
if ($norm === '/logout')                             { end_session_and_redirect('/login'); }

// --- Test route for debugging ---
if ($norm === '/test') {
  serveHTML('test_routing.php');
}

// --- Protected UIs ---
if ($norm === '/admin') {
  if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '')!=='admin')) { header('Location:/login?role=admin'); exit; }
  serveHTML('admin.php');
}
if ($norm === '/engineer') {
  if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '')!=='field_engineer')) { header('Location:/login?role=engineer'); exit; }
  serveHTML('field-engineer.php');
}

// ---------- API ----------
if (strpos($norm, '/api/') === 0) {
  try{
    $pdo = pdo();

    // --- Auth ---
    if ($norm==='/api/auth/status' && $method==='GET'){
      $u=$_SESSION['user']??null; json_out(['authenticated'=>isset($_SESSION['user_id']),'user'=>$u?:null]);
    }
    if ($norm==='/api/auth/login' && $method==='POST'){
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $login=trim((string)($in['username']??'')); $pass=(string)($in['password']??'');
      if ($login==='') json_out(['error'=>'email required'],400);
      // Fixed: Use email for login but store user ID for stability
      $st=$pdo->prepare("SELECT * FROM users WHERE email=? AND activated=1 LIMIT 1"); $st->execute([$login]); $u=$st->fetch();
      // Check both password fields (your DB has both password and password_hash)
      $stored_password = !empty($u['password']) ? $u['password'] : $u['password_hash'];
      if(!$u || !password_verify($pass, (string)$stored_password)) json_out(['error'=>'invalid credentials'],401);
      session_regenerate_id(true);
      $_SESSION['user_id']=(int)$u['id']; $_SESSION['role']=(string)$u['role']; $_SESSION['email']=(string)$u['email'];
      $_SESSION['user']=['id'=>(int)$u['id'],'email'=>$u['email'],'full_name'=>trim(($u['first_name']??'').' '.($u['last_name']??'')),'role'=>$u['role']];
      json_out(['ok'=>true,'user'=>$_SESSION['user']]);
    }
    if (($norm==='/api/logout' || $norm==='/api/auth/logout') && $method==='POST'){
      $_SESSION=[];
      if(ini_get('session.use_cookies')){
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path']??'/',$p['domain']??'',!empty($p['secure']),!empty($p['httponly']));
      }
      session_destroy();
      json_out(['success'=>true]);
    }

    // --- Clients ---
    if ($norm==='/api/clients' && $method==='GET'){
      api_need_auth(); // zowel admin als engineer mogen lijst lezen (alleen admin wijzigt)
      $st=$pdo->query("SELECT id, name, company_name, first_name, last_name, email, phone, address_street, address_number, address_postcode, address_city, address_country, notes, created_at FROM clients ORDER BY id DESC");
      json_out($st->fetchAll());
    }
    if ($norm==='/api/clients' && $method==='POST'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $fields=['name','company_name','first_name','last_name','email','phone','address_street','address_number','address_postcode','address_city','address_country','notes'];
      $cols=[];$vals=[];$ph=[];
      foreach($fields as $f){ if(array_key_exists($f,$in)){ $cols[]=$f; $vals[]=$in[$f]; $ph[]='?'; } }
      if(!$cols) json_out(['error'=>'no fields'],400);
      $sql="INSERT INTO clients (".implode(',',$cols).") VALUES (".implode(',',$ph).")";
      $pdo->prepare($sql)->execute($vals);
      json_out(['ok'=>true,'id'=>$pdo->lastInsertId()]);
    }
    if ($norm==='/api/clients' && $method==='PUT'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $id=(int)($in['id']??0); if(!$id) json_out(['error'=>'id required'],400);
      unset($in['id']); if(!$in) json_out(['error'=>'no fields'],400);
      $set=[];$vals=[]; foreach($in as $k=>$v){ $set[]="$k=?"; $vals[]=$v; }
      $vals[]=$id;
      $pdo->prepare("UPDATE clients SET ".implode(',',$set)." WHERE id=?")->execute($vals);
      json_out(['ok'=>true]);
    }
    // Single client endpoint (GET /api/client?id=123)
    if ($norm==='/api/client' && $method==='GET'){
      api_need_auth();
      $id=(int)($_GET['id']??0); if(!$id) json_out(['error'=>'id required'],400);
      $st=$pdo->prepare("SELECT * FROM clients WHERE id=? LIMIT 1"); $st->execute([$id]); $c=$st->fetch();
      if(!$c) json_out(['error'=>'not found'],404);
      json_out($c);
    }
    // Update single client (PUT /api/client)
    if ($norm==='/api/client' && $method==='PUT'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $id=(int)($in['id']??0); if(!$id) json_out(['error'=>'id required'],400);
      unset($in['id']); if(!$in) json_out(['error'=>'no fields'],400);
      $set=[];$vals=[]; foreach($in as $k=>$v){ $set[]="$k=?"; $vals[]=$v; }
      $vals[]=$id;
      $pdo->prepare("UPDATE clients SET ".implode(',',$set)." WHERE id=?")->execute($vals);
      json_out(['ok'=>true]);
    }

    // --- Engineers (dropdown) ---
    if ($norm==='/api/engineers' && $method==='GET'){
      api_need_auth();
      $st=$pdo->query("SELECT id, CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as name FROM users WHERE role='field_engineer' AND activated=1 ORDER BY first_name, last_name");
      json_out($st->fetchAll());
    }

    // --- Users (admin) ---
    if ($norm==='/api/users' && $method==='GET'){
      api_need_admin();
      $st=$pdo->query("SELECT id,username,email,CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as full_name,role,created_at,activated FROM users ORDER BY id DESC");
      json_out($st->fetchAll());
    }
    if ($norm==='/api/users' && $method==='POST'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $u=(string)($in['username']??''); $e=(string)($in['email']??''); $fn=(string)($in['full_name']??''); $r=(string)($in['role']??'');
      // Split full_name into first_name and last_name
      $nameParts = explode(' ', trim($fn), 2);
      $firstName = $nameParts[0] ?? '';
      $lastName = $nameParts[1] ?? '';
      if($u===''||$fn===''||$r===''||$e==='') json_out(['error'=>'username, full_name, email and role required'],400);
      
      // Generate activation token and temporary password
      $activationToken = bin2hex(random_bytes(32));
      $tempPassword = bin2hex(random_bytes(8));
      $hp = password_hash($tempPassword, PASSWORD_BCRYPT);
      
      $pdo->prepare("INSERT INTO users (username,email,first_name,last_name,role,password,activation_token,activated) VALUES (?,?,?,?,?,?,?,0)")
         ->execute([$u,$e,$firstName,$lastName,$r,$hp,$activationToken]);
      $userId = $pdo->lastInsertId();
      
      // Send activation email
      send_activation_email($e, $fn, $activationToken, $tempPassword, $r);
      
      json_out(['ok'=>true,'id'=>$userId]);
    }
    if ($norm==='/api/users' && $method==='PUT'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $id=(int)($in['id']??0); if(!$id) json_out(['error'=>'id required'],400);
      unset($in['id']);
      if(isset($in['password']) && $in['password']!==''){ $in['password']=password_hash((string)$in['password'], PASSWORD_BCRYPT); }
      elseif(isset($in['password'])){ unset($in['password']); }
      if(!$in) json_out(['error'=>'no fields'],400);
      $set=[];$vals=[]; foreach($in as $k=>$v){ $set[]="$k=?"; $vals[]=$v; }
      $vals[]=$id;
      $pdo->prepare("UPDATE users SET ".implode(',',$set)." WHERE id=?")->execute($vals);
      json_out(['ok'=>true]);
    }
    if ($norm==='/api/users' && $method==='DELETE'){
      api_need_admin();
      $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
      if (!$id) json_out(['error'=>'id required'], 400);
      
      // Check if user has assigned tasks
      $st = $pdo->prepare("SELECT COUNT(*) as task_count FROM tasks WHERE assigned_user_id = ? AND status IN ('received', 'accepted', 'in_progress')");
      $st->execute([$id]);
      $result = $st->fetch();
      if ($result['task_count'] > 0) {
        json_out(['error'=>'Cannot delete user with active tasks. Please reassign tasks first.'], 400);
      }
      
      // Delete the user
      $st = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $st->execute([$id]);
      
      if ($st->rowCount() > 0) {
        json_out(['ok'=>true]);
      } else {
        json_out(['error'=>'User not found'], 404);
      }
    }
    
    // --- Account Activation ---
    if ($norm==='/api/activate' && $method==='POST'){
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $token=(string)($in['token']??''); $newPassword=(string)($in['password']??'');
      if($token===''||$newPassword==='') json_out(['error'=>'token and password required'],400);
      
      $st=$pdo->prepare("SELECT id,email,CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as full_name FROM users WHERE activation_token=? AND activated=0 LIMIT 1");
      $st->execute([$token]); $user=$st->fetch();
      if(!$user) json_out(['error'=>'invalid or expired token'],401);
      
      $hp = password_hash($newPassword, PASSWORD_BCRYPT);
      $pdo->prepare("UPDATE users SET password=?, activated=1, activation_token=NULL WHERE id=?")
         ->execute([$hp, $user['id']]);
      
      json_out(['ok'=>true,'message'=>'Account activated successfully']);
    }
    
    // --- PDF Reports ---
    if ($norm==='/api/reports/generate' && $method==='POST'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $type=(string)($in['type']??'completed'); $dateFrom=(string)($in['date_from']??'');
      
      $conditions = [];
      $params = [];
      
      if ($type === 'completed') {
        $conditions[] = "t.status = 'done'";
      } elseif ($type === 'rejected') {
        $conditions[] = "t.status = 'rejected'";
      } elseif ($type === 'all_finished') {
        $conditions[] = "(t.status = 'done' OR t.status = 'rejected')";
      }
      
      if ($dateFrom) {
        $conditions[] = "t.updated_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
      }
      
      $whereClause = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
      
      // Build SQL with database-specific concatenation syntax
      if (DB_TYPE === 'pgsql') {
        $nameConcat = "(COALESCE(u.first_name, '') || ' ' || COALESCE(u.last_name, ''))";
      } else {
        $nameConcat = "CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))";
      }
      
      $sql = "
        SELECT t.*, 
               c.name as client_name,
               c.email as client_email,
               c.phone as client_phone,
               CONCAT(COALESCE(c.address_street,''), ' ', COALESCE(c.address_number,''), ', ', COALESCE(c.address_postcode,''), ' ', COALESCE(c.address_city,''), ', ', COALESCE(c.address_country,'')) as client_address,
               $nameConcat as engineer_name,
               u.email as engineer_email
        FROM tasks t 
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN users u ON t.assigned_user_id = u.id
        $whereClause
        ORDER BY t.updated_at DESC, t.id DESC
      ";
      
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $tasks = $st->fetchAll();
      
      // Get company branding info from config
      $companyName = defined('COMPANY_NAME') ? COMPANY_NAME : 'FreshPC Cloud';
      $companyAddress = defined('COMPANY_ADDRESS') ? COMPANY_ADDRESS : '';
      $companyPhone = defined('COMPANY_PHONE') ? COMPANY_PHONE : '';
      $companyEmail = defined('COMPANY_EMAIL') ? COMPANY_EMAIL : '';
      
      // Generate enhanced PDF report
      $pdf = new MiniPDF();
      $pdf->SetTitle($companyName . ' - ' . ucfirst($type) . ' Tasks Report');
      $pdf->SetAuthor($companyName . ' System');
      $pdf->AddPage();
      
      // Company Header
      $pdf->SetFont('Arial', 'B', 18);
      $pdf->Cell(0, 12, $companyName, 0, 1, 'C');
      $pdf->SetFont('Arial', '', 10);
      if ($companyAddress) {
        $pdf->Cell(0, 5, $companyAddress, 0, 1, 'C');
      }
      if ($companyPhone || $companyEmail) {
        $contactLine = '';
        if ($companyPhone) $contactLine .= 'Tel: ' . $companyPhone;
        if ($companyPhone && $companyEmail) $contactLine .= ' | ';
        if ($companyEmail) $contactLine .= 'Email: ' . $companyEmail;
        $pdf->Cell(0, 5, $contactLine, 0, 1, 'C');
      }
      $pdf->Ln(10);
      
      // Report Title
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->Cell(0, 10, ucfirst(str_replace('_', ' ', $type)) . ' Tasks Report', 0, 1);
      $pdf->Ln(5);
      
      // Report Info
      $pdf->SetFont('Arial', '', 12);
      $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'));
      $pdf->Ln();
      if ($dateFrom) {
        $pdf->Cell(0, 6, 'Date from: ' . $dateFrom);
        $pdf->Ln();
      }
      $pdf->Cell(0, 6, 'Total tasks: ' . count($tasks));
      $pdf->Ln(15);
      
      // Add line separator after header
      $pdf->SetDrawColor(200, 200, 200);
      $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
      $pdf->Ln(10);
      
      // Task Details
      foreach ($tasks as $task) {
        // Task Header with border
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'Task #' . $task['id'] . ': ' . ($task['title'] ?? 'Untitled'), 1, 1, 'L', true);
        $pdf->Ln(2);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Client Information
        $pdf->Cell(40, 5, 'Client:', 0, 0, 'L');
        $pdf->Cell(0, 5, ($task['client_name'] ?? 'Unknown'), 0, 1, 'L');
        
        if (!empty($task['client_email'])) {
          $pdf->Cell(40, 5, 'Client Email:', 0, 0, 'L');
          $pdf->Cell(0, 5, $task['client_email'], 0, 1, 'L');
        }
        
        if (!empty($task['client_phone'])) {
          $pdf->Cell(40, 5, 'Client Phone:', 0, 0, 'L');
          $pdf->Cell(0, 5, $task['client_phone'], 0, 1, 'L');
        }
        
        if (!empty($task['client_address'])) {
          $pdf->Cell(40, 5, 'Client Address:', 0, 0, 'L');
          $pdf->MultiCell(0, 5, $task['client_address']);
        }
        
        $pdf->Ln(2);
        
        // Engineer Information  
        $pdf->Cell(40, 5, 'Engineer:', 0, 0, 'L');
        $pdf->Cell(0, 5, (trim($task['engineer_name']) ?: 'Not Assigned'), 0, 1, 'L');
        
        if (!empty($task['engineer_email'])) {
          $pdf->Cell(40, 5, 'Engineer Email:', 0, 0, 'L');
          $pdf->Cell(0, 5, $task['engineer_email'], 0, 1, 'L');
        }
        
        // Task Status and Dates
        $pdf->Cell(40, 5, 'Status:', 0, 0, 'L');
        $pdf->Cell(0, 5, ucfirst($task['status'] ?? 'Unknown'), 0, 1, 'L');
        
        $pdf->Cell(40, 5, 'Priority:', 0, 0, 'L');
        $pdf->Cell(0, 5, ucfirst($task['priority'] ?? 'Medium'), 0, 1, 'L');
        
        if ($task['scheduled_date']) {
          $pdf->Cell(40, 5, 'Scheduled:', 0, 0, 'L');
          $pdf->Cell(0, 5, date('Y-m-d H:i', strtotime($task['scheduled_date'])), 0, 1, 'L');
        }
        
        if ($task['updated_at']) {
          $pdf->Cell(40, 5, 'Last Updated:', 0, 0, 'L');
          $pdf->Cell(0, 5, date('Y-m-d H:i', strtotime($task['updated_at'])), 0, 1, 'L');
        }
        
        if (!empty($task['location'])) {
          $pdf->Cell(40, 5, 'Location:', 0, 0, 'L');
          $pdf->Cell(0, 5, $task['location'], 0, 1, 'L');
        }
        
        $pdf->Ln(3);
        
        // Task Description
        if (!empty($task['description'])) {
          $pdf->SetFont('Arial', 'B', 10);
          $pdf->Cell(0, 5, 'Description:', 0, 1, 'L');
          $pdf->SetFont('Arial', '', 10);
          $pdf->MultiCell(0, 5, $task['description']);
          $pdf->Ln(2);
        }
        
        // Work Details
        if ($task['hours_worked'] > 0) {
          $pdf->Cell(40, 5, 'Hours Worked:', 0, 0, 'L');
          $pdf->Cell(0, 5, number_format($task['hours_worked'], 2) . ' hours', 0, 1, 'L');
        }
        
        if ($task['additional_costs'] > 0) {
          $pdf->Cell(40, 5, 'Additional Costs:', 0, 0, 'L');
          $pdf->Cell(0, 5, '€' . number_format($task['additional_costs'], 2), 0, 1, 'L');
        }
        
        // Add separator line between tasks
        $pdf->Ln(5);
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(8);
      }
      
      // Add company footer
      $pdf->Ln(10);
      $pdf->SetFont('Arial', 'I', 8);
      $pdf->SetTextColor(128, 128, 128);
      $pdf->Cell(0, 5, 'Report generated by ' . $companyName, 0, 1, 'C');
      if ($companyPhone || $companyEmail) {
        $contactLine = '';
        if ($companyPhone) $contactLine .= $companyPhone;
        if ($companyPhone && $companyEmail) $contactLine .= ' | ';
        if ($companyEmail) $contactLine .= $companyEmail;
        $pdf->Cell(0, 5, $contactLine, 0, 1, 'C');
      }
      if (defined('COMPANY_WEBSITE')) {
        $pdf->Cell(0, 5, COMPANY_WEBSITE, 0, 1, 'C');
      }
      
      $reportDir = __DIR__ . '/uploads/reports';
      if (!is_dir($reportDir)) @mkdir($reportDir, 0775, true);
      $filename = $type . '_report_' . date('Ymd_His') . '.pdf';
      $filepath = $reportDir . '/' . $filename;
      file_put_contents($filepath, $pdf->Output('S'));
      
      json_out(['ok'=>true,'filename'=>$filename,'path'=>'/uploads/reports/'.$filename,'tasks'=>$tasks,'count'=>count($tasks)]);
    }

    // --- Current user profile (engineers) ---  
    if ($norm==='/api/me' && $method==='GET'){
      api_need_auth();
      $me=(int)($_SESSION['user_id']??0);
      $st=$pdo->prepare("SELECT id,username,email,CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as full_name,role,phone FROM users WHERE id=? LIMIT 1"); $st->execute([$me]); $u=$st->fetch();
      if(!$u) json_out(['error'=>'user not found'],404);
      json_out($u);
    }
    if ($norm==='/api/me' && $method==='PUT'){
      api_need_auth();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $me=(int)($_SESSION['user_id']??0);
      $allowed=['full_name','email','phone'];
      $set=[];$vals=[];
      foreach($allowed as $k){ if(array_key_exists($k,$in) && trim((string)$in[$k])!==''){ $set[]="$k=?"; $vals[]=$in[$k]; } }
      
      // Handle password change (if current password provided)
      if(isset($in['current_password']) && isset($in['new_password']) && trim($in['current_password'])!=='' && trim($in['new_password'])!==''){
        $st=$pdo->prepare("SELECT password FROM users WHERE id=? LIMIT 1"); $st->execute([$me]); $u=$st->fetch();
        if(!$u || !password_verify($in['current_password'],(string)$u['password'])) json_out(['error'=>'current password incorrect'],400);
        $set[]='password=?'; $vals[]=password_hash($in['new_password'], PASSWORD_BCRYPT);
      }
      
      if(!$set) json_out(['error'=>'no fields to update'],400);
      $vals[]=$me;
      $pdo->prepare("UPDATE users SET ".implode(',',$set)." WHERE id=?")->execute($vals);
      json_out(['ok'=>true]);
    }

    // --- Tasks ---
    if ($norm==='/api/tasks' && $method==='GET'){
      api_need_auth();
      $me=(int)($_SESSION['user_id']??0); $role=(string)($_SESSION['role']??'');
      $sql="SELECT t.*, c.name AS client_name, c.email AS client_email, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS assigned_to_name
            FROM tasks t
            LEFT JOIN clients c ON c.id=t.client_id
            LEFT JOIN users u ON u.id=t.assigned_user_id";
      $args=[];
      if($role!=='admin'){ $sql.=" WHERE t.assigned_user_id=? AND t.status NOT IN ('done','afgerond','rejected')"; $args[]=$me; } else { $sql.=" WHERE t.status NOT IN ('done','afgerond','rejected')"; }
      $sql.=" ORDER BY t.id DESC";
      $st=$pdo->prepare($sql); $st->execute($args); $rows=$st->fetchAll();
      foreach($rows as &$r){
        $r['full_address']=client_full_address_by_id($pdo, isset($r['client_id'])?(int)$r['client_id']:null);
        $q=trim($r['full_address'])!==''?urlencode($r['full_address']):'';
        $r['maps_url']=$q!==''?("https://www.google.com/maps/search/?api=1&query=".$q):null;
        $r['nav_url']=$q!==''?("https://www.google.com/maps/dir/?api=1&destination=".$q):null;
      }
      json_out($rows);
    }

    // API endpoint for history tasks
    if ($norm==='/api/tasks/history' && $method==='GET'){
      api_need_auth();
      $sql="SELECT t.*, c.name AS client_name, c.email AS client_email, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS engineer_name
            FROM tasks t
            LEFT JOIN clients c ON c.id=t.client_id
            LEFT JOIN users u ON u.id=t.assigned_user_id
            WHERE t.status IN ('done','afgerond','rejected')
            ORDER BY t.completed_date DESC, t.updated_at DESC";
      $st=$pdo->prepare($sql); $st->execute(); $rows=$st->fetchAll();
      json_out($rows);
    }
    if ($norm==='/api/tasks' && $method==='POST'){
      api_need_admin();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $title=trim((string)($in['title']??'')); if($title==='') json_out(['error'=>'title required'],400);
      $fields=['title','description','client_id','assigned_user_id','priority','scheduled_date'];
      $cols=[];$vals=[];$ph=[];
      foreach($fields as $f){ if(array_key_exists($f,$in)){ $cols[]=$f; $vals[]=$in[$f]; $ph[]='?'; } }
      $cols[]='created_by'; $vals[]=(int)($_SESSION['user_id']??0); $ph[]='?';
      $sql="INSERT INTO tasks (".implode(',',$cols).") VALUES (".implode(',',$ph).")";
      $pdo->prepare($sql)->execute($vals);
      $id=(int)$pdo->lastInsertId();
      task_log($pdo,$id,'created','via admin');
      json_out(['ok'=>true,'id'=>$id]);
    }
    if ($norm==='/api/task' && $method==='PUT'){
      api_need_auth();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $id=(int)($in['id']??0); if(!$id) json_out(['error'=>'id required'],400);
      $role=(string)($_SESSION['role']??''); $me=(int)($_SESSION['user_id']??0);
      if($role!=='admin'){
        $st=$pdo->prepare("SELECT assigned_user_id FROM tasks WHERE id=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch();
        if(!$row) json_out(['error'=>'not found'],404);
        if((int)$row['assigned_user_id']!==$me) json_out(['error'=>'forbidden'],403);
      }
      $allowed=['status','priority','scheduled_date','materials_used','work_notes','client_name','client_email','rejection_reason'];
      $set=[];$vals=[];
      foreach($allowed as $k){ if(array_key_exists($k,$in)){ $set[]="$k=?"; $vals[]=$in[$k]; } }
      if(isset($in['status']) && $in['status']==='done'){ 
        $set[]='status=?'; $vals[]='done'; 
        $set[]='completed_date=?'; $vals[]=date('Y-m-d H:i:s');
      }
      if(isset($in['status']) && $in['status']==='afgerond'){ 
        $set[]='status=?'; $vals[]='done'; 
        $set[]='completed_date=?'; $vals[]=date('Y-m-d H:i:s');
      }
      if(isset($in['status']) && $in['status']==='rejected'){ 
        $set[]='status=?'; $vals[]='rejected'; 
        $set[]='completed_date=?'; $vals[]=date('Y-m-d H:i:s');
      }
      if(!$set) json_out(['error'=>'no fields'],400);
      $vals[]=$id;
      $pdo->prepare("UPDATE tasks SET ".implode(',',$set)." WHERE id=?")->execute($vals);
      task_log($pdo,$id,'status',$in['status']??'');
      
      // Generate PDF report for completed or rejected tasks
      if(isset($in['status']) && ($in['status']==='done' || $in['status']==='rejected')){
        generate_task_report($pdo, $id, $in['status']);
      }
      
      json_out(['ok'=>true]);
    }

    // --- Photo upload (multipart/form-data) ---
    if ($norm==='/api/task/photo' && $method==='POST'){
      api_need_auth();
      $taskId = (int)($_POST['task_id'] ?? 0);
      if(!$taskId) json_out(['error'=>'task_id required'],400);
      $role=(string)($_SESSION['role']??''); $me=(int)($_SESSION['user_id']??0);
      if($role!=='admin'){
        $st=$pdo->prepare("SELECT assigned_user_id FROM tasks WHERE id=? LIMIT 1"); $st->execute([$taskId]); $row=$st->fetch();
        if(!$row) json_out(['error'=>'not found'],404);
        if((int)$row['assigned_user_id']!==$me) json_out(['error'=>'forbidden'],403);
      }
      if(empty($_FILES['photo']) || $_FILES['photo']['error']!==UPLOAD_ERR_OK) json_out(['error'=>'photo missing'],400);
      $dir = __DIR__ . '/uploads/tasks/'.$taskId.'/photos';
      if(!is_dir($dir)) @mkdir($dir, 0775, true);
      $tmp = $_FILES['photo']['tmp_name'];
      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
      if($ext==='') $ext='jpg';
      $name = 'photo_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
      $dest = $dir.'/'.$name;
      if(!move_uploaded_file($tmp, $dest)) json_out(['error'=>'store failed'],500);
      @chmod($dest,0644);
      $rel = 'uploads/tasks/'.$taskId.'/photos/'.$name;
      task_log($pdo,$taskId,'photo',$rel);
      json_out(['ok'=>true,'path'=>$rel]);
    }

    // --- Signature capture + finish ---
    if ($norm==='/api/task/signature' && $method==='POST'){
      api_need_auth();
      $in=json_decode(file_get_contents('php://input'),true)?:[];
      $id=(int)($in['id']??0); $dataUrl=(string)($in['image']??''); // data:image/png;base64,...
      if(!$id || $dataUrl==='') json_out(['error'=>'id and image required'],400);
      $role=(string)($_SESSION['role']??''); $me=(int)($_SESSION['user_id']??0);
      if($role!=='admin'){
        $st=$pdo->prepare("SELECT assigned_user_id, title, client_id FROM tasks WHERE id=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch();
        if(!$row) json_out(['error'=>'not found'],404);
        if((int)$row['assigned_user_id']!==$me) json_out(['error'=>'forbidden'],403);
      } else {
        $st=$pdo->prepare("SELECT title, client_id FROM tasks WHERE id=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch();
      }
      if(!preg_match('#^data:image/(png|jpeg);base64,#',$dataUrl,$m)) json_out(['error'=>'invalid image'],400);
      $ext = ($m[1]==='jpeg')?'jpg':'png';
      $b64 = substr($dataUrl, strpos($dataUrl, ',')+1);
      $bin = base64_decode($b64, true);
      if($bin===false) json_out(['error'=>'decode failed'],400);
      $baseDir=__DIR__.'/uploads/tasks/'.$id; if(!is_dir($baseDir)) @mkdir($baseDir,0775,true);
      $fname='signature_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext; $full=$baseDir.'/'.$fname;
      if(file_put_contents($full,$bin)===false) json_out(['error'=>'store failed'],500);
      @chmod($full,0644);
      $rel='uploads/tasks/'.$id.'/'.$fname;

      // Bijwerken task (indien kolommen bestaan)
      $cols=$pdo->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN,0);
      $updates=[]; $vals=[];
      if(in_array('signature_path',$cols,true)){ $updates[]='signature_path=?'; $vals[]=$rel; }
      if(in_array('completed_date',$cols,true)){ $updates[]='completed_date=?'; $vals[]=date('Y-m-d H:i:s'); }
      if(in_array('status',$cols,true)){ $updates[]='status=?'; $vals[]='done'; }
      if($updates){ $vals[]=$id; $sql='UPDATE tasks SET '.implode(',',$updates).' WHERE id=?'; $pdo->prepare($sql)->execute($vals); }
      task_log($pdo,$id,'signature',$rel);

      // Werkbon mailen
      $tTitle = (string)($row['title'] ?? ('Taak #'.$id));
      $clientName=''; $clientEmail=''; $addr='';
      if (!empty($row['client_id'])){
        try{
          $cst=$pdo->prepare("SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as name, email, COALESCE(address_street,'') f, COALESCE(address_city,'') c, COALESCE(address_postcode,'') pc, COALESCE(address_country,'') co FROM clients WHERE id=? LIMIT 1");
          $cst->execute([(int)$row['client_id']]); $cl=$cst->fetch();
          if($cl){
            $clientName=(string)($cl['name']??''); $clientEmail=(string)($cl['email']??'');
            $parts=[]; foreach(['f','c','pc','co'] as $k){ $v=trim((string)($cl[$k]??'')); if($v!=='') $parts[]=$v; }
            $addr = trim(implode(' ', $parts)); if($addr==='') $addr=(string)($cl['f']??'');
          }
        }catch(Throwable $e){}
      }
      $lines=[];
      $lines[]="Werkbon – taak afgerond";
      $lines[]="";
      $lines[]="Taak: ".$tTitle." (ID: ".$id.")";
      if($clientName!=='')  $lines[]="Klant: ".$clientName;
      if($clientEmail!=='') $lines[]="Klant e-mail: ".$clientEmail;
      if($addr!=='')        $lines[]="Adres: ".$addr;
      $lines[]="Gereed: ".date('Y-m-d H:i');
      $lines[]="";
      $lines[]="Handtekening: https://".$_SERVER['HTTP_HOST']."/".$rel;
      $lines[]="";
      $lines[]="Overzicht: https://".$_SERVER['HTTP_HOST']."/admin";
      $body = implode("\n", $lines);
      @mail(ADMIN_WORKBON_EMAIL, "Werkbon afgerond – ".$tTitle, $body, "From: noreply@".$_SERVER['HTTP_HOST']."\r\nContent-Type: text/plain; charset=UTF-8");

      json_out(['success'=>true,'ok'=>true,'path'=>$rel,'status'=>'done','message'=>'Task completed and moved to history']);
    }

    // --- Task history ---
    if ($norm==='/api/task/events' && $method==='GET'){
      api_need_auth();
      $id=isset($_GET['id'])?(int)$_GET['id']:0; if(!$id) json_out(['error'=>'id required'],400);
      $role=(string)($_SESSION['role']??''); $me=(int)($_SESSION['user_id']??0);
      if($role!=='admin'){
        $st=$pdo->prepare("SELECT assigned_user_id FROM tasks WHERE id=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch();
        if(!$row) json_out(['error'=>'not found'],404);
        if((int)$row['assigned_user_id']!==$me) json_out(['error'=>'forbidden'],403);
      }
      try{ 
        $st=$pdo->prepare("
          SELECT 
            th.created_at,
            th.action as event,
            CONCAT(COALESCE(th.notes,''), ' (', COALESCE(th.from_status,''), ' → ', COALESCE(th.to_status,''), ')') as details,
            CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as engineer_name
          FROM task_history th
          LEFT JOIN users u ON th.engineer_id = u.id 
          WHERE th.task_id=? 
          ORDER BY th.id DESC
        "); 
        $st->execute([$id]); 
        json_out($st->fetchAll()); 
      }catch(Throwable $e){ json_out(['error'=>'server error','detail'=>$e->getMessage()],500); }
    }

    // Unknown API
    json_out(['error'=>'not found'],404);
  } catch(Throwable $e){
    json_out(['error'=>'server error','detail'=>$e->getMessage()],500);
  }
}

// ---------- Direct file fallbacks (removed - now using .php files) ----------
// These fallbacks have been removed because we converted to .php files for configurable styling

// ---------- 404 ----------
http_response_code(404);
header('X-FPC-Miss: '.$norm);
echo "Not found: ".$norm;
exit;
?>