<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FunctionalFlowTest extends TestCase
{
    private string $dsn;
    private PDO $pdo;
    private string $projectRoot;
    private string $dbHost;
    private string $dbUser;
    private string $dbPass;
    private string $dbName;
    private bool $isMysql = true;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->dbHost = getenv('TEST_DB_HOST') ?: '127.0.0.1';
        $this->dbUser = getenv('TEST_DB_USER') ?: 'root';
        $this->dbPass = getenv('TEST_DB_PASS') ?: '';
        $this->dbName = 'manic_test_' . uniqid();

        try {
            $this->dsn = "mysql:host={$this->dbHost};charset=utf8mb4";
            $rootPdo = new PDO($this->dsn, $this->dbUser, $this->dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $rootPdo->exec("CREATE DATABASE `{$this->dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $this->dsn = "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $this->pdo = new PDO($this->dsn, $this->dbUser, $this->dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $this->isMysql = true;
        } catch (Throwable $e) {
            // Fallback на SQLite, если MySQL недоступен
            $this->isMysql = false;
            $this->dbUser = '';
            $this->dbPass = '';
            $this->dsn = 'sqlite:' . sys_get_temp_dir() . '/manic_db_' . uniqid() . '.sqlite';
            $this->pdo = new PDO($this->dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        if ($this->isMysql) {
            try {
                $rootDsn = "mysql:host={$this->dbHost}";
                $rootPdo = new PDO($rootDsn, $this->dbUser, $this->dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $rootPdo->exec("DROP DATABASE IF EXISTS `{$this->dbName}`");
            } catch (Throwable $e) {
                // ignore cleanup errors
            }
        } else {
            $path = str_replace('sqlite:', '', $this->dsn);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    public function test_пользователь_может_записаться_на_услугу(): void
    {
        $this->seedUser(1, 0);
        $this->seedService(1, 'Комплексный маникюр', 1200.00, 60, null);
        $this->seedMasterSchedule(10, 1, '09:00:00', '18:00:00');
        $this->seedMasterService(10, 1);

        $payload = json_encode([
            'user_id' => 1,
            'yslygi_id' => 1,
            'date_time' => '2025-05-05 10:00:00',
            'id_master' => 10,
        ]);

        $result = $this->runPhpFile(
            'add_booking.php',
            [
                'REQUEST_METHOD' => 'POST',
                'TEST_SESSION_ID' => 'booking_success',
                'TEST_SESSION_USER_ID' => '1',
                'TEST_SESSION_ROLE' => '0',
                'TEST_JSON_INPUT' => $payload,
            ],
            $payload
        );

        $response = json_decode($result['stdout'], true);

        $this->assertSame(0, $result['exit_code'], $result['stderr']);
        $this->assertEquals('success', $response['status'] ?? null, $result['stdout']);
        $this->assertCount(1, $this->pdo->query('SELECT * FROM zapis')->fetchAll(PDO::FETCH_ASSOC));
    }

    public function test_админ_может_создать_услугу(): void
    {
        $this->seedCategory(1, 'Маникюр');

        $post = [
            'create_service' => 1,
            'name' => 'SPA уход',
            'price' => '1500',
            'opisanie' => 'Полный уход',
            'duration_minutes' => 90,
            'id_kategori' => 1,
        ];

        $result = $this->runPhpFile(
            'admin/create_service.php',
            [
                'REQUEST_METHOD' => 'POST',
                'TEST_SESSION_ID' => 'admin_create',
                'TEST_SESSION_USER_ID' => '99',
                'TEST_SESSION_ROLE' => '2',
                'TEST_POST_JSON' => json_encode($post, JSON_UNESCAPED_UNICODE),
            ]
        );

        $services = $this->pdo->query('SELECT name, price, duration_minutes FROM yslygi')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame(0, $result['exit_code'], $result['stderr']);
        $this->assertCount(1, $services);
        $this->assertEquals('SPA уход', $services[0]['name']);
        $this->assertEquals('1500.00', $services[0]['price']);
        $this->assertEquals(90, (int)$services[0]['duration_minutes']);
    }

    public function test_мастер_имеет_доступ_к_своим_слотам(): void
    {
        $this->seedUser(10, 1);
        $this->seedService(1, 'Покрытие гель-лаком', 1000.0, 60, null);
        $this->seedMasterService(10, 1);
        $this->seedMasterSchedule(10, 1, '09:00:00', '12:00:00');

        // Существующая запись на 09:00-10:00
        $this->pdo->prepare('INSERT INTO zapis (user_id, yslygi_id, date_time, id_master) VALUES (?,?,?,?)')
            ->execute([1, 1, '2025-05-05 09:00:00', 10]);

        $get = [
            'master_id' => 10,
            'service_id' => 1,
            'date' => '2025-05-05',
        ];

        $result = $this->runPhpFile(
            'get_available_slots.php',
            [
                'TEST_GET_JSON' => json_encode($get, JSON_UNESCAPED_UNICODE),
            ]
        );

        $response = json_decode($result['stdout'], true);
        $slots = array_column($response['available_slots'] ?? [], 'time');

        $this->assertSame(0, $result['exit_code'], $result['stderr']);
        $this->assertEquals('success', $response['status'] ?? null, $result['stdout']);
        $this->assertEquals(['10:00', '10:15', '10:30', '10:45', '11:00'], $slots);
    }

    public function test_админ_не_может_создать_запись_короче_15_минут(): void
    {
        $post = [
            'create_service' => 1,
            'name' => 'Экспресс маникюр',
            'price' => '800',
            'opisanie' => 'Быстрый вариант',
            'duration_minutes' => 10,
        ];

        $result = $this->runPhpFile(
            'admin/create_service.php',
            [
                'REQUEST_METHOD' => 'POST',
                'TEST_SESSION_ID' => 'admin_validation',
                'TEST_SESSION_USER_ID' => '5',
                'TEST_SESSION_ROLE' => '2',
                'TEST_POST_JSON' => json_encode($post, JSON_UNESCAPED_UNICODE),
            ]
        );

        $services = $this->pdo->query('SELECT COUNT(*) FROM yslygi')->fetchColumn();

        $this->assertSame(0, $result['exit_code'], $result['stderr']);
        $this->assertEquals(0, (int)$services, 'Service must not be created');
        $this->assertStringContainsString('не может быть меньше 15 минут', $result['stdout']);
    }

    public function test_пользователь_не_может_получить_доступ_к_админ_панели(): void
    {
        $result = $this->runPhpFile(
            'admin/create_service.php',
            [
                'REQUEST_METHOD' => 'POST',
                'TEST_SESSION_ID' => 'role_denied',
                'TEST_SESSION_USER_ID' => '7',
                'TEST_SESSION_ROLE' => '1',
            ]
        );

        $this->assertSame(0, $result['exit_code']);
        $this->assertStringContainsString('Доступ запрещен', $result['stdout'] . $result['stderr']);
    }
 public function test_неавторизованный_пользователь_не_может_создать_запись(): void
    {
        $this->seedUser(1, 0);
        $this->seedService(1, 'Маникюр', 1000.0, 60, null);
        $this->seedMasterSchedule(10, 1, '09:00:00', '18:00:00');
        $this->seedMasterService(10, 1);

        $payload = json_encode([
            'user_id' => 1,
            'yslygi_id' => 1,
            'date_time' => '2025-05-05 11:00:00',
            'id_master' => 10,
        ]);

        $result = $this->runPhpFile(
            'add_booking.php',
            [
                'REQUEST_METHOD' => 'POST',
                // Не передаем данные сессии, имитируя неавторизованного пользователя
                'TEST_JSON_INPUT' => $payload,
            ],
            $payload
        );

        $response = json_decode($result['stdout'], true);
        $this->assertEquals('error', $response['status'] ?? null);
        $this->assertStringContainsString('Ошибка авторизации', $response['message'] ?? '');
        // Проверяем, что запись не создалась
        $count = $this->pdo->query('SELECT COUNT(*) FROM zapis')->fetchColumn();
        $this->assertEquals(0, $count, 'Запись не должна была быть создана для неавторизованного пользователя');
    }

    private function createSchema(): void
    {
        if ($this->isMysql) {
            $schema = [
                'CREATE TABLE user (
                    id_user INT AUTO_INCREMENT PRIMARY KEY,
                    id_roli INT,
                    email VARCHAR(255)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE yslygi (
                    id_yslygi INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    price DECIMAL(10,2),
                    opisanie TEXT,
                    foto VARCHAR(255),
                    duration_minutes INT,
                    id_kategori INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE kategori (
                    id_kategori INT PRIMARY KEY,
                    name VARCHAR(255)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE master_schedule (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_master INT,
                    day_of_week INT,
                    start_time TIME,
                    end_time TIME,
                    is_active TINYINT(1)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE master_days_off (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_master INT,
                    date_off DATE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE master_services (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_master INT,
                    id_yslygi INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE zapis (
                    id_zapis INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    yslygi_id INT,
                    date_time DATETIME,
                    id_master INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                'CREATE TABLE favorites (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    yslygi_id INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            ];
        } else {
            $schema = [
                'CREATE TABLE user (
                    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_roli INTEGER,
                    email TEXT
                )',
                'CREATE TABLE yslygi (
                    id_yslygi INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT,
                    price TEXT,
                    opisanie TEXT,
                    foto TEXT,
                    duration_minutes INTEGER,
                    id_kategori INTEGER
                )',
                'CREATE TABLE kategori (
                    id_kategori INTEGER PRIMARY KEY,
                    name TEXT
                )',
                'CREATE TABLE master_schedule (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_master INTEGER,
                    day_of_week INTEGER,
                    start_time TEXT,
                    end_time TEXT,
                    is_active INTEGER
                )',
                'CREATE TABLE master_days_off (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_master INTEGER,
                    date_off TEXT
                )',
                'CREATE TABLE master_services (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_master INTEGER,
                    id_yslygi INTEGER
                )',
                'CREATE TABLE zapis (
                    id_zapis INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    yslygi_id INTEGER,
                    date_time TEXT,
                    id_master INTEGER
                )',
                'CREATE TABLE favorites (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    yslygi_id INTEGER
                )',
            ];
        }

        foreach ($schema as $sql) {
            $this->pdo->exec($sql);
        }
    }

    private function seedUser(int $id, int $role): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO user (id_user, id_roli, email) VALUES (?, ?, ?)');
        $stmt->execute([$id, $role, "user{$id}@example.com"]);
    }

    private function seedService(int $id, string $name, float $price, int $duration, ?int $categoryId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO yslygi (id_yslygi, name, price, opisanie, foto, duration_minutes, id_kategori) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$id, $name, number_format($price, 2, '.', ''), 'Описание', '', $duration, $categoryId]);
    }

    private function seedCategory(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO kategori (id_kategori, name) VALUES (?, ?)');
        $stmt->execute([$id, $name]);
    }

    private function seedMasterSchedule(int $masterId, int $dayOfWeek, string $start, string $end): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO master_schedule (id_master, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$masterId, $dayOfWeek, $start, $end]);
    }

    private function seedMasterService(int $masterId, int $serviceId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO master_services (id_master, id_yslygi) VALUES (?, ?)');
        $stmt->execute([$masterId, $serviceId]);
    }

    private function runPhpFile(string $relativePath, array $env = [], string $input = ''): array
    {
        $absolutePath = $this->projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        $command = ['php', $absolutePath];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = array_merge($_ENV, [
            'DB_DSN' => $this->dsn,
            'DB_USER' => $this->dbUser,
            'DB_PASS' => $this->dbPass,
        ], $env);

        $process = proc_open($command, $descriptors, $pipes, $this->projectRoot, $environment);
        if (!is_resource($process)) {
            return ['stdout' => '', 'stderr' => 'Failed to start process', 'exit_code' => 1];
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exitCode,
        ];
    }

}
