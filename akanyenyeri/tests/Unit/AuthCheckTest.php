<?php
/**
 * Unit Tests for AuthCheck Class
 * Tests authentication, session management, and security features
 */

use PHPUnit\Framework\TestCase;

class AuthCheckTest extends TestCase
{
    private $auth;
    private $pdo;

    protected function setUp(): void
    {
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear session data
        $_SESSION = [];

        // Get test database connection
        $this->pdo = TestDatabaseHelper::getTestDB();

        // Create AuthCheck instance
        require_once PROJECT_ROOT . '/admin/php/auth_check.php';
        $this->auth = new AuthCheck();

        // Clean database before each test
        TestDatabaseHelper::cleanDatabase();
    }

    protected function tearDown(): void
    {
        // Clear session after each test
        $_SESSION = [];

        // Clear server variables
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test authentication with no session data
     */
    public function testIsAuthenticatedWithNoSession()
    {
        $this->assertFalse($this->auth->isAuthenticated());
    }

    /**
     * Test authentication with invalid session data
     */
    public function testIsAuthenticatedWithInvalidSession()
    {
        $_SESSION['admin_logged_in'] = true;
        // Missing required session fields

        $this->assertFalse($this->auth->isAuthenticated());
    }

    /**
     * Test authentication with valid session data
     */
    public function testIsAuthenticatedWithValidSession()
    {
        // Create test user
        $user = createTestUser(['role' => 'admin']);

        // Mock valid admin session
        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        $this->assertTrue($this->auth->isAuthenticated());
    }

    /**
     * Test session expiration
     */
    public function testSessionExpiration()
    {
        // Create test user
        $user = createTestUser(['role' => 'admin']);

        // Mock session with expired timestamp
        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role'],
            'admin_last_activity' => time() - 7200 // 2 hours ago
        ]);

        $this->assertFalse($this->auth->isAuthenticated());
    }

    /**
     * Test authentication with inactive user
     */
    public function testAuthenticationWithInactiveUser()
    {
        // Create inactive test user
        $user = createTestUser([
            'role' => 'admin',
            'status' => 'inactive'
        ]);

        // Mock admin session
        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        $this->assertFalse($this->auth->isAuthenticated());
    }

    /**
     * Test role checking functionality
     */
    public function testHasRole()
    {
        // Create test user with editor role
        $user = createTestUser(['role' => 'editor']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        // Editor should have editor role
        $this->assertTrue($this->auth->hasRole('editor'));

        // Editor should not have admin role (admin > editor)
        $this->assertFalse($this->auth->hasRole('admin'));

        // Editor should have author role (editor > author)
        $this->assertTrue($this->auth->hasRole('author'));
    }

    /**
     * Test admin role hierarchy
     */
    public function testAdminRoleHierarchy()
    {
        $user = createTestUser(['role' => 'admin']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => 'admin'
        ]);

        // Admin should have all roles
        $this->assertTrue($this->auth->hasRole('admin'));
        $this->assertTrue($this->auth->hasRole('editor'));
        $this->assertTrue($this->auth->hasRole('author'));
    }

    /**
     * Test permission checking
     */
    public function testCanPerformAction()
    {
        $user = createTestUser(['role' => 'editor']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => 'editor'
        ]);

        // Editor should be able to publish posts
        $this->assertTrue($this->auth->canPerformAction('publish_posts'));

        // Editor should not be able to manage users
        $this->assertFalse($this->auth->canPerformAction('manage_users'));

        // Editor should be able to create posts
        $this->assertTrue($this->auth->canPerformAction('create_posts'));
    }

    /**
     * Test admin permissions
     */
    public function testAdminPermissions()
    {
        $user = createTestUser(['role' => 'admin']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => 'admin'
        ]);

        // Admin should have all permissions
        $this->assertTrue($this->auth->canPerformAction('manage_users'));
        $this->assertTrue($this->auth->canPerformAction('manage_settings'));
        $this->assertTrue($this->auth->canPerformAction('delete_posts'));
        $this->assertTrue($this->auth->canPerformAction('export_data'));
    }

    /**
     * Test CSRF token generation and validation
     */
    public function testCSRFTokenGeneration()
    {
        $token = $this->auth->generateCSRF();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertTrue($this->auth->validateCSRF($token));
    }

    /**
     * Test CSRF token validation with invalid token
     */
    public function testCSRFTokenValidationWithInvalidToken()
    {
        $this->auth->generateCSRF();

        $this->assertFalse($this->auth->validateCSRF('invalid_token'));
        $this->assertFalse($this->auth->validateCSRF(''));
    }

    /**
     * Test getCurrentUser functionality
     */
    public function testGetCurrentUser()
    {
        // Should return null when not authenticated
        $this->assertNull($this->auth->getCurrentUser());

        // Create test user and authenticate
        $user = createTestUser([
            'username' => 'testuser',
            'role' => 'editor',
            'email' => 'test@example.com'
        ]);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role'],
            'admin_email' => $user['email']
        ]);

        $currentUser = $this->auth->getCurrentUser();

        $this->assertNotNull($currentUser);
        $this->assertEquals($user['id'], $currentUser['id']);
        $this->assertEquals($user['username'], $currentUser['username']);
        $this->assertEquals($user['role'], $currentUser['role']);
        $this->assertEquals($user['email'], $currentUser['email']);
    }

    /**
     * Test suspicious activity detection - IP address change
     */
    public function testSuspiciousActivityIPChange()
    {
        $user = createTestUser(['role' => 'admin']);

        // Set initial session with IP
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        // Verify authentication works with original IP
        $this->assertTrue($this->auth->isAuthenticated());

        // Change IP address
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';

        // Should detect suspicious activity and fail authentication
        $this->assertFalse($this->auth->checkSuspiciousActivity());
    }

    /**
     * Test suspicious activity detection - User agent change
     */
    public function testSuspiciousActivityUserAgentChange()
    {
        $user = createTestUser(['role' => 'admin']);

        // Set initial session with user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        // Verify authentication works with original user agent
        $this->assertTrue($this->auth->isAuthenticated());

        // Change user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Different Browser';

        // Should detect suspicious activity and fail authentication
        $this->assertFalse($this->auth->checkSuspiciousActivity());
    }

    /**
     * Test security event logging
     */
    public function testSecurityEventLogging()
    {
        $user = createTestUser(['role' => 'admin']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';

        // Log a security event
        $this->auth->logSecurityEvent('test_event', 'Test security event details');

        // Check if event was logged in database
        $stmt = $this->pdo->prepare("SELECT * FROM security_logs WHERE event_type = 'test_event'");
        $stmt->execute();
        $log = $stmt->fetch();

        $this->assertNotNull($log);
        $this->assertEquals('test_event', $log['event_type']);
        $this->assertEquals($user['id'], $log['user_id']);
        $this->assertEquals('127.0.0.1', $log['ip_address']);
        $this->assertEquals('Test security event details', $log['details']);
    }

    /**
     * Test session destruction
     */
    public function testSessionDestruction()
    {
        $user = createTestUser(['role' => 'admin']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        // Verify session exists
        $this->assertTrue($this->auth->isAuthenticated());

        // Destroy session
        $this->auth->destroySession();

        // Verify session is destroyed
        $this->assertFalse($this->auth->isAuthenticated());
        $this->assertEmpty($_SESSION);
    }

    /**
     * Test role change detection
     */
    public function testRoleChangeDetection()
    {
        $user = createTestUser(['role' => 'editor']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => 'editor'
        ]);

        // Verify initial authentication with editor role
        $this->assertTrue($this->auth->isAuthenticated());
        $this->assertTrue($this->auth->hasRole('editor'));
        $this->assertFalse($this->auth->hasRole('admin'));

        // Change user role in database to admin
        $stmt = $this->pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Verify authentication updates session with new role
        $this->assertTrue($this->auth->isAuthenticated());
        $this->assertEquals('admin', $_SESSION['admin_role']);
        $this->assertTrue($this->auth->hasRole('admin'));
    }

    /**
     * Test user deactivation detection
     */
    public function testUserDeactivationDetection()
    {
        $user = createTestUser(['role' => 'admin', 'status' => 'active']);

        mockAdminSession([
            'admin_user_id' => $user['id'],
            'admin_username' => $user['username'],
            'admin_role' => $user['role']
        ]);

        // Verify initial authentication
        $this->assertTrue($this->auth->isAuthenticated());

        // Deactivate user in database
        $stmt = $this->pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Verify authentication now fails
        $this->assertFalse($this->auth->isAuthenticated());
    }
}
